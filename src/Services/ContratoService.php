<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use iEducar\Financeiro\Jobs\CancelarCobrancaGatewayJob;
use iEducar\Financeiro\Models\AlunoResponsavel;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\PlanoPagamento;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ContratoService
{
    /**
     * Cria um novo contrato financeiro e gera suas parcelas respeitando rateios de responsáveis.
     *
     * Correções aplicadas:
     *  - A01: desconto contratual distribuído proporcionalmente a cada item antes de criar títulos.
     *  - A02: resíduo de centavo distribuído pelo algoritmo Largest Remainder (determinístico).
     *  - A03: validação da soma de rateios (= 100%), da matrícula e da escola antes de persistir.
     *  - A28: escola_id=1 substituído por exceção explícita (sem fallback silencioso).
     *
     * @param  array    $dados
     * @return Contrato
     */
    public function criarContratoEParcelas(array $dados): Contrato
    {
        return DB::transaction(function () use ($dados) {
            $alunoId       = (int) $dados['aluno_id'];
            $matriculaId   = (int) $dados['matricula_id'];
            $planoId       = (int) $dados['plano_id'];
            $anoLetivo     = (int) $dados['ano_letivo'];
            $diaVencimento = (int) ($dados['dia_vencimento'] ?? 10);

            // ----------------------------------------------------------------
            // FIN-006 — SEMPRE valida matrícula, aluno, escola e ano letivo
            // ----------------------------------------------------------------
            $escolaId = !empty($dados['escola_id']) ? (int) $dados['escola_id'] : null;

            if ($matriculaId) {
                $matriculaRow = DB::table('pmieducar.matricula')
                    ->where('cod_matricula', $matriculaId)
                    ->select('ref_ref_cod_escola', 'ref_cod_aluno', 'ano')
                    ->first();

                if (!$matriculaRow) {
                    throw new InvalidArgumentException(
                        "Matrícula #{$matriculaId} não encontrada."
                    );
                }

                if ((int) $matriculaRow->ref_cod_aluno !== $alunoId) {
                    throw new InvalidArgumentException(
                        "A matrícula #{$matriculaId} não pertence ao aluno #{$alunoId}."
                    );
                }

                $matriculaEscolaId = isset($matriculaRow->ref_ref_cod_escola)
                    ? (int) $matriculaRow->ref_ref_cod_escola
                    : null;

                if ($escolaId && $matriculaEscolaId && $escolaId !== $matriculaEscolaId) {
                    throw new InvalidArgumentException(
                        "A matrícula #{$matriculaId} pertence à escola #{$matriculaEscolaId}, divergente da escola #{$escolaId} informada."
                    );
                }

                if (!$escolaId && $matriculaEscolaId) {
                    $escolaId = $matriculaEscolaId;
                }

                if (isset($matriculaRow->ano) && (int) $matriculaRow->ano !== $anoLetivo) {
                    throw new InvalidArgumentException(
                        "A matrícula #{$matriculaId} é do ano letivo {$matriculaRow->ano}, incompatível com o ano {$anoLetivo} do contrato."
                    );
                }

                // BUG-023: Bloqueio de duplicidade de contratos ativos para a mesma matrícula no ano letivo
                $contratoExistente = Contrato::where('matricula_id', $matriculaId)
                    ->where('ano_letivo', $anoLetivo)
                    ->where('status', '!=', 'cancelado')
                    ->exists();

                if ($contratoExistente) {
                    throw new InvalidArgumentException(
                        "Já existe um contrato ativo para a matrícula #{$matriculaId} no ano letivo {$anoLetivo}."
                    );
                }
            }

            if (!$escolaId) {
                throw new InvalidArgumentException(
                    'Não foi possível determinar a escola a partir da matrícula informada. ' .
                    'Informe escola_id explicitamente.'
                );
            }

            // ----------------------------------------------------------------
            // Identifica responsáveis financeiros do aluno ou Rateio
            // ----------------------------------------------------------------
            $aplicarRateio = !empty($dados['aplicar_rateio']) && !empty($dados['rateio_responsaveis']);
            $rateioConfig  = $aplicarRateio ? (array) $dados['rateio_responsaveis'] : [];

            if ($aplicarRateio) {
                // Validação de soma de rateios = 100%
                $somaPct = 0.0;
                foreach ($rateioConfig as $rc) {
                    $somaPct += (float) ($rc['percentual'] ?? 0);
                }
                if (abs($somaPct - 100.00) > 0.01) {
                    throw new InvalidArgumentException(sprintf(
                        'A soma dos percentuais de rateio deve ser exatamente 100,00%%. Soma atual: %.2f%%.',
                        $somaPct
                    ));
                }

                $vinculos = collect();
                foreach ($rateioConfig as $idx => $rc) {
                    $respId = (int) $rc['responsavel_id'];
                    $pct = (float) $rc['percentual'];
                    $formaResp = strtolower(trim((string) ($rc['forma_pagamento'] ?? ($dados['forma_pagamento'] ?? 'carne'))));
                    $isPrincipal = ($idx === 0);

                    // Atualiza ou cria vínculo na tabela financeiro.aluno_responsavel
                    AlunoResponsavel::updateOrCreate(
                        ['aluno_id' => $alunoId, 'responsavel_id' => $respId],
                        [
                            'parentesco'            => $rc['parentesco'] ?? 'Responsável',
                            'percentual_rateio'     => $pct,
                            'responsavel_principal' => $isPrincipal,
                        ]
                    );

                    $qtdResp = !empty($rc['qtd_parcelas']) ? (int) $rc['qtd_parcelas'] : $qtdParcelasPadrao;
                    if (in_array($formaResp, ['pix', 'boleto', 'dinheiro'])) {
                        $qtdResp = 1;
                    }
                    $dataVencCustom = !empty($rc['data_vencimento']) ? Carbon::parse($rc['data_vencimento']) : null;
                    $repassarJuros  = !empty($rc['repassar_juros']);
                    $taxaRepasse    = (float) ($rc['valor_taxa_repasse'] ?? 0.0);

                    $vinculos->push((object) [
                        'responsavel_id'        => $respId,
                        'percentual_rateio'     => $pct,
                        'forma_pagamento'       => $formaResp,
                        'dia_vencimento'        => !empty($rc['dia_vencimento']) ? (int) $rc['dia_vencimento'] : $diaVencimento,
                        'qtd_parcelas'          => $qtdResp,
                        'data_vencimento'       => $dataVencCustom,
                        'repassar_juros'        => $repassarJuros,
                        'valor_taxa_repasse'    => $taxaRepasse,
                        'responsavel_principal' => $isPrincipal,
                    ]);
                }

                $responsavelPrincipalId = (int) $rateioConfig[0]['responsavel_id'];
            } else {
                // Fluxo padrão sem rateio (1 responsável titular único)
                $respId = !empty($dados['responsavel_financeiro_id']) ? (int) $dados['responsavel_financeiro_id'] : null;
                if (!$respId) {
                    $vinculoExistente = AlunoResponsavel::where('aluno_id', $alunoId)->first();
                    $respId = $vinculoExistente?->responsavel_id;
                }

                if (!$respId) {
                    throw new InvalidArgumentException('O aluno não possui responsável financeiro cadastrado.');
                }

                $responsavelPrincipalId = $respId;
                $formaUnica = strtolower(trim((string) ($dados['forma_pagamento'] ?? 'carne')));
                $repassarJurosUnico = !empty($dados['repassar_juros']);
                $taxaRepasseUnico   = (float) ($dados['valor_taxa_repasse'] ?? 0.0);

                // Garante vínculo na base
                AlunoResponsavel::updateOrCreate(
                    ['aluno_id' => $alunoId, 'responsavel_id' => $respId],
                    [
                        'percentual_rateio'     => 100.00,
                        'responsavel_principal' => true,
                    ]
                );

                $vinculos = collect([
                    (object) [
                        'responsavel_id'        => $responsavelPrincipalId,
                        'percentual_rateio'     => 100.00,
                        'forma_pagamento'       => $formaUnica,
                        'dia_vencimento'        => $diaVencimento,
                        'qtd_parcelas'          => $qtdParcelasPadrao,
                        'repassar_juros'        => $repassarJurosUnico,
                        'valor_taxa_repasse'    => $taxaRepasseUnico,
                        'responsavel_principal' => true,
                    ]
                ]);
            }

            $plano = PlanoPagamento::with('itens')->findOrFail($planoId);

            if ($plano->escola_id && (int) $plano->escola_id !== $escolaId) {
                throw new InvalidArgumentException(
                    "O plano de pagamento selecionado pertence à escola #{$plano->escola_id}, divergente da escola #{$escolaId} do contrato."
                );
            }

            $descontoTotal = (float) ($dados['valor_total_descontos'] ?? ($dados['valor_desconto'] ?? 0.00));
            $valorBruto    = (float) $plano->valor_total;

            if ($descontoTotal < 0.00 || $descontoTotal > $valorBruto) {
                throw new InvalidArgumentException(
                    sprintf(
                        'O valor de desconto total (R$ %.2f) deve ser entre R$ 0,00 e o valor bruto do contrato (R$ %.2f).',
                        $descontoTotal,
                        $valorBruto
                    )
                );
            }

            $valorLiquido  = max(0.0, round($valorBruto - $descontoTotal, 2));

            // A01 — Taxa de desconto proporcional aplicada a cada item
            $taxaDesconto = ($valorBruto > 0) ? ($descontoTotal / $valorBruto) : 0.0;

            // -------------------------------------------------------------------
            // A29 — número do contrato: retry até 5x para escapar de gaps
            // -------------------------------------------------------------------
            $numeroContrato = null;
            for ($tentativa = 0; $tentativa < 5; $tentativa++) {
                $candidato = Contrato::gerarNumeroContrato($anoLetivo);
                $jaExiste  = Contrato::withTrashed()
                    ->where('numero_contrato', $candidato)
                    ->exists();
                if (!$jaExiste) {
                    $numeroContrato = $candidato;
                    break;
                }
            }
            if ($numeroContrato === null) {
                throw new \RuntimeException(
                    'Não foi possível gerar um número de contrato único após 5 tentativas.'
                );
            }

            $dataInicio     = Carbon::parse($dados['data_inicio'] ?? "{$anoLetivo}-01-01");
            $dataTermino    = Carbon::parse($dados['data_termino'] ?? "{$anoLetivo}-12-31");

            if ($dataInicio->gt($dataTermino)) {
                throw new InvalidArgumentException(
                    "A data de início ({$dataInicio->format('d/m/Y')}) não pode ser posterior à data de término ({$dataTermino->format('d/m/Y')})."
                );
            }

            $planoSnapshot = [
                'id'          => $plano->id,
                'nome'        => $plano->nome,
                'valor_total' => (float) $plano->valor_total,
                'rateio'      => $aplicarRateio ? $rateioConfig : null,
                'itens'       => $plano->itens->map(fn($item) => [
                    'tipo_item'       => $item->tipo_item,
                    'numero_parcela'  => (int) $item->numero_parcela,
                    'mes_competencia' => (int) $item->mes_competencia,
                    'valor'           => (float) $item->valor,
                    'descricao'       => $item->descricao,
                ])->toArray(),
            ];

            $formaMaster = $aplicarRateio ? 'rateio' : ($dados['forma_pagamento'] ?? null);

            $contrato = Contrato::create([
                'numero_contrato'           => $numeroContrato,
                'matricula_id'              => $matriculaId,
                'aluno_id'                  => $alunoId,
                'responsavel_financeiro_id' => $responsavelPrincipalId,
                'plano_id'                  => $plano->id,
                'ano_letivo'                => $anoLetivo,
                'data_inicio'               => $dataInicio,
                'data_termino'              => $dataTermino,
                'valor_total_bruto'         => $valorBruto,
                'valor_total_descontos'     => $descontoTotal,
                'valor_total_liquido'       => $valorLiquido,
                'dia_vencimento'            => $diaVencimento,
                'escola_id'                 => $escolaId,
                'status'                    => 'ativo',
                'forma_pagamento'           => $formaMaster,
                'plano_snapshot'            => $planoSnapshot,
            ]);

            $titulosCriados = [];
            $qtdParcelasPadrao = max(1, (int) ($dados['qtd_parcelas'] ?? 12));

            // Distribuição exata do valor líquido total entre os vínculos (Largest Remainder)
            $valoresRateioTotal = $this->distribuirComResiduo($valorLiquido, $vinculos);

            foreach ($vinculos as $idx => $vinculo) {
                $valorVinculoTotal = $valoresRateioTotal[$idx];
                if (!empty($vinculo->repassar_juros) && !empty($vinculo->valor_taxa_repasse)) {
                    $valorVinculoTotal = round($valorVinculoTotal + (float) $vinculo->valor_taxa_repasse, 2);
                }
                if ($valorVinculoTotal <= 0) {
                    continue;
                }

                $formaDoResp = strtolower(trim((string) ($vinculo->forma_pagamento ?: 'carne')));
                $diaDoResp   = (int) ($vinculo->dia_vencimento ?: $diaVencimento);

                if ($formaDoResp === 'pix') {
                    // Parcela única instantânea via Pix
                    $titulosCriados[] = Titulo::create([
                        'contrato_id'               => $contrato->id,
                        'aluno_id'                  => $alunoId,
                        'responsavel_financeiro_id' => $vinculo->responsavel_id,
                        'escola_id'                 => $escolaId,
                        'tipo'                      => 'mensalidade',
                        'numero_parcela'            => 1,
                        'total_parcelas'            => 1,
                        'competencia'               => Carbon::today()->format('Y-m'),
                        'data_vencimento'           => Carbon::today(),
                        'valor_nominal'             => $valorVinculoTotal,
                        'valor_desconto'            => 0.00,
                        'valor_multa'               => 0.00,
                        'valor_juros'               => 0.00,
                        'valor_pago'                => 0.00,
                        'valor_saldo'               => $valorVinculoTotal,
                        'status'                    => 'aberto',
                        'observacoes'               => sprintf(
                            '%s - Pagamento Instantâneo via Pix (%s%%)',
                            $contrato->numero_contrato,
                            number_format($vinculo->percentual_rateio, 1)
                        ),
                    ]);
                } elseif ($formaDoResp === 'boleto') {
                    // Boleto à Vista (1x) vence em 2 dias após a emissão
                    $dataVencBoleto = $vinculo->data_vencimento ?? Carbon::today()->addDays(2);
                    $titulosCriados[] = Titulo::create([
                        'contrato_id'               => $contrato->id,
                        'aluno_id'                  => $alunoId,
                        'responsavel_financeiro_id' => $vinculo->responsavel_id,
                        'escola_id'                 => $escolaId,
                        'tipo'                      => 'mensalidade',
                        'numero_parcela'            => 1,
                        'total_parcelas'            => 1,
                        'competencia'               => Carbon::today()->format('Y-m'),
                        'data_vencimento'           => $dataVencBoleto,
                        'valor_nominal'             => $valorVinculoTotal,
                        'valor_desconto'            => 0.00,
                        'valor_multa'               => 0.00,
                        'valor_juros'               => 0.00,
                        'valor_pago'                => 0.00,
                        'valor_saldo'               => $valorVinculoTotal,
                        'status'                    => 'aberto',
                        'observacoes'               => sprintf(
                            '%s - Boleto Bancário à Vista (%s%%)',
                            $contrato->numero_contrato,
                            number_format($vinculo->percentual_rateio, 1)
                        ),
                    ]);
                } else {
                    // Parcelado em N vezes (Carnê Asaas, Cartão de Crédito Link, Cartão Presencial POS ou Dinheiro)
                    $qtdParcelasResp = $vinculo->qtd_parcelas ?? $qtdParcelasPadrao;
                    if ($formaDoResp === 'dinheiro' && empty($dados['qtd_parcelas']) && empty($vinculo->qtd_parcelas)) {
                        $qtdParcelasResp = 1;
                    }

                    $valoresParcelas = $this->dividirValorEmParcelas($valorVinculoTotal, $qtdParcelasResp);

                    $hoje = Carbon::today();
                    $mesOffset = ($hoje->day > $diaDoResp) ? 1 : 0;
                    $dataPrimeira = $hoje->copy()->startOfMonth()->addMonths($mesOffset);

                    for ($i = 1; $i <= $qtdParcelasResp; $i++) {
                        $dataRef = $dataPrimeira->copy()->addMonths($i - 1);
                        $anoParcela = $dataRef->year;
                        $mesParcela = $dataRef->month;
                        $competencia = sprintf('%04d-%02d', $anoParcela, $mesParcela);

                        $ultimoDiaMes = $dataRef->copy()->endOfMonth()->day;
                        $diaEfetivo = min($diaDoResp, $ultimoDiaMes);
                        $dataVencimento = Carbon::create($anoParcela, $mesParcela, $diaEfetivo);

                        $valorParcela = $valoresParcelas[$i - 1];
                        if ($valorParcela <= 0) {
                            continue;
                        }

                        $titulosCriados[] = Titulo::create([
                            'contrato_id'               => $contrato->id,
                            'aluno_id'                  => $alunoId,
                            'responsavel_financeiro_id' => $vinculo->responsavel_id,
                            'escola_id'                 => $escolaId,
                            'tipo'                      => 'mensalidade',
                            'numero_parcela'            => $i,
                            'total_parcelas'            => $qtdParcelasResp,
                            'competencia'               => $competencia,
                            'data_vencimento'           => $dataVencimento,
                            'valor_nominal'             => $valorParcela,
                            'valor_desconto'            => 0.00,
                            'valor_multa'               => 0.00,
                            'valor_juros'               => 0.00,
                            'valor_pago'                => 0.00,
                            'valor_saldo'               => $valorParcela,
                            'status'                    => 'aberto',
                            'observacoes'               => sprintf(
                                '%s - Parcela %02d/%02d (%s%%)',
                                $contrato->numero_contrato,
                                $i,
                                $qtdParcelasResp,
                                number_format($vinculo->percentual_rateio, 1)
                            ),
                        ]);
                    }
                }
            }

            // Orquestra a modalidade de pagamento escolhida para cada responsável
            $this->processarModalidadePagamentoMultiplos($contrato, $titulosCriados, $vinculos, $dados);

            return $contrato;
        });
    }

    /**
     * Orquestra a modalidade de pagamento escolhida para cada responsável em rateio ou titular único.
     */
    public function processarModalidadePagamentoMultiplos(Contrato $contrato, array $titulos, iterable $vinculos, array $dados): void
    {
        $titulosPorResp = collect($titulos)->groupBy('responsavel_financeiro_id');
        $vinculosCol = collect($vinculos)->keyBy('responsavel_id');

        foreach ($titulosPorResp as $respId => $titulosDoResp) {
            $vinculo = $vinculosCol->get($respId);
            $formaDoResp = $vinculo?->forma_pagamento ?? strtolower(trim((string) ($dados['forma_pagamento'] ?? 'carne')));
            $this->processarModalidadePorResponsavel($contrato, $titulosDoResp->all(), $formaDoResp, $dados);
        }

        // Atualiza status final do contrato
        $todosTitulos = $contrato->titulos()->get();
        if ($todosTitulos->isNotEmpty() && $todosTitulos->every(fn($t) => in_array($t->status, ['pago', 'liquidado']))) {
            $contrato->update([
                'status'     => 'quitado',
                'quitado_em' => Carbon::now(),
            ]);
        } elseif ($todosTitulos->contains(fn($t) => $t->status === 'aberto')) {
            $temPixOuBoletoAguardando = $todosTitulos->contains(fn($t) => in_array($t->forma_pagamento, ['pix', 'boleto']));
            $contrato->update([
                'status' => $temPixOuBoletoAguardando ? 'aguardando_pagamento' : 'ativo',
            ]);
        }
    }

    /**
     * Processa a modalidade de pagamento específica de um grupo de títulos de um responsável.
     */
    public function processarModalidadePorResponsavel(Contrato $contrato, array $titulos, string $formaPagamento, array $dados): void
    {
        $formaPagamento = strtolower(trim($formaPagamento));
        if (empty($formaPagamento) || empty($titulos)) {
            return;
        }

        switch ($formaPagamento) {
            case 'dinheiro':
                $baixaService = app(BaixaTituloService::class);
                foreach ($titulos as $titulo) {
                    $baixaService->realizarBaixa($titulo, [
                        'valor_pago'      => (float) $titulo->valor_saldo,
                        'forma_pagamento' => 'dinheiro',
                        'data_pagamento'  => Carbon::today(),
                        'origem_baixa'    => 'balcao',
                        'usuario_id'      => auth()->id() ?? null,
                    ]);
                }
                break;

            case 'cartao_presencial':
                $baixaService = app(BaixaTituloService::class);
                foreach ($titulos as $titulo) {
                    $baixaService->realizarBaixa($titulo, [
                        'valor_pago'      => (float) $titulo->valor_saldo,
                        'forma_pagamento' => 'cartao_presencial',
                        'data_pagamento'  => Carbon::today(),
                        'origem_baixa'    => 'balcao_pos',
                        'usuario_id'      => auth()->id() ?? null,
                    ]);
                }
                break;

            case 'cartao_credito':
                try {
                    $gateway = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
                    $maxParcelas = (int) ($dados['qtd_parcelas'] ?? ($dados['cartao_parcelas'] ?? 12));
                    $valorTotalResp = array_sum(array_map(fn($t) => (float) $t->valor_nominal, $titulos));
                    $res = $gateway->criarPaymentLink($contrato, (float) $valorTotalResp, $maxParcelas, 5);
                    if (!empty($titulos)) {
                        CobrancaGateway::create([
                            'titulo_id'         => $titulos[0]->id,
                            'contrato_id'       => $contrato->id,
                            'gateway'           => 'asaas',
                            'tipo_cobranca'     => 'payment_link',
                            'gateway_charge_id' => $res['payment_link_id'] ?? 'LINK-' . $contrato->id . '-' . $titulos[0]->responsavel_financeiro_id,
                            'link_pagamento'    => $res['payment_link_url'] ?? null,
                            'status_externo'    => 'ACTIVE',
                            'ativa'             => true,
                            'expira_em'         => $res['expira_em'] ?? Carbon::now()->addDays(7),
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("ContratoService: Falha ao gerar payment link Asaas: " . $e->getMessage());
                }
                break;

            case 'pix':
                if (!empty($titulos)) {
                    try {
                        $gateway = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
                        $res = $gateway->criarCobranca($titulos[0], ['billingType' => 'PIX']);
                        $titulos[0]->cobrancasGateway()->updateOrCreate(
                            ['titulo_id' => $titulos[0]->id, 'gateway_charge_id' => $res['gateway_charge_id']],
                            [
                                'contrato_id'    => $contrato->id,
                                'gateway'        => 'asaas',
                                'tipo_cobranca'  => 'pix',
                                'status_externo' => $res['status_externo'] ?? 'PENDING',
                                'pix_copia_cola' => $res['pix_copia_cola'] ?? null,
                                'qr_code_base64' => $res['pix_qr_code_base64'] ?? null,
                                'link_pagamento' => $res['link_boleto_pdf'] ?? null,
                                'ativa'          => true,
                                'expira_em'      => Carbon::now()->addHours(24),
                            ]
                        );
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("ContratoService: Falha ao emitir Pix Asaas: " . $e->getMessage());
                    }
                }
                break;

            case 'boleto':
                if (!empty($titulos)) {
                    try {
                        $gateway = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
                        $gateway->criarCobranca($titulos[0], ['billingType' => 'BOLETO']);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("ContratoService: Falha ao emitir Boleto Asaas: " . $e->getMessage());
                    }
                }
                break;

            case 'carne':
                if (count($titulos) >= 2) {
                    try {
                        $carneService = app(CarneService::class);
                        $carneService->gerar($contrato, $titulos, auth()->id() ?? null);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("ContratoService: Falha ao gerar carnê Asaas: " . $e->getMessage(), [
                            'contrato_id' => $contrato->id,
                            'trace'       => $e->getTraceAsString(),
                        ]);
                    }
                }
                break;
        }
    }

    /**
     * Orquestra as modalidades de pagamento (mantido para compatibilidade).
     */
    public function processarModalidadePagamento(Contrato $contrato, array $titulos, array $dados): void
    {
        $formaPagamento = strtolower(trim((string) ($dados['forma_pagamento'] ?? '')));
        $this->processarModalidadePorResponsavel($contrato, $titulos, $formaPagamento, $dados);
    }


    /**
     * Distribui um valor monetário entre vínculos pelo algoritmo Largest Remainder.
     * Garante que a soma dos valores seja exatamente igual ao total sem perda de centavo.
     *
     * Exemplo: R$100,01 ÷ 2 responsáveis (50/50) → [R$50,01, R$50,00]
     *
     * @param  float     $total    Valor total a distribuir
     * @param  iterable  $vinculos Coleção com atributo `percentual_rateio`
     * @return array<int, float>   Mapa [idx => valor em reais]
     */
    private function distribuirComResiduo(float $total, iterable $vinculos): array
    {
        $vinculos      = collect($vinculos)->values();
        $totalCentavos = (int) round($total * 100);

        $bases     = [];
        $decimais  = [];
        $somaBases = 0;

        foreach ($vinculos as $idx => $vinculo) {
            $exato        = $totalCentavos * ((float) $vinculo->percentual_rateio / 100);
            $base         = (int) floor($exato);
            $bases[$idx]  = $base;
            $decimais[$idx] = $exato - $base;
            $somaBases   += $base;
        }

        // Distribui centavos residuais aos vínculos com maior parte decimal
        $residuo = $totalCentavos - $somaBases;
        arsort($decimais);

        foreach (array_keys($decimais) as $idx) {
            if ($residuo <= 0) {
                break;
            }
            $bases[$idx]++;
            $residuo--;
        }

        return array_map(fn(int $centavos): float => round($centavos / 100, 2), $bases);
    }

    /**
     * Divide um valor total em N parcelas distribuindo centavos residuais nas primeiras parcelas.
     * Garante que a soma das parcelas seja rigorosamente igual ao total.
     * Exemplo: R$ 100,00 ÷ 3 parcelas → [33.34, 33.33, 33.33]
     *
     * @param  float $total
     * @param  int   $qtdParcelas
     * @return array<int, float>
     */
    private function dividirValorEmParcelas(float $total, int $qtdParcelas): array
    {
        if ($qtdParcelas <= 1) {
            return [round($total, 2)];
        }

        $totalCentavos = (int) round($total * 100);
        $baseCentavos = intdiv($totalCentavos, $qtdParcelas);
        $residuo = $totalCentavos % $qtdParcelas;

        $parcelas = [];
        for ($i = 0; $i < $qtdParcelas; $i++) {
            $centavos = $baseCentavos + ($i < $residuo ? 1 : 0);
            $parcelas[] = round($centavos / 100, 2);
        }

        return $parcelas;
    }

    /**
     * Cancela o contrato conforme política aprovada:
     * cancela apenas parcelas futuras não vencidas; mantém vencidas em cobrança.
     *
     * A24 — Após cancelamento local, enfileira Job para cancelar cobranças no Asaas.
     * BUG-023: Validação de já cancelado, lockForUpdate e registro de cancelado_em/cancelado_por.
     *
     * @param  Contrato $contrato
     * @param  string   $motivo
     * @return void
     */
    public function cancelarContrato(Contrato $contrato, string $motivo): void
    {
        $contratoId = $contrato->id;

        $titulosComCobranca = DB::transaction(function () use ($contratoId, $motivo) {
            /** @var Contrato $contratoLocked */
            $contratoLocked = Contrato::lockForUpdate()->findOrFail($contratoId);

            if ($contratoLocked->status === 'cancelado') {
                throw new InvalidArgumentException('Este contrato já se encontra cancelado.');
            }

            $contratoLocked->update([
                'status'              => 'cancelado',
                'motivo_cancelamento' => $motivo,
                'cancelado_em'        => Carbon::now(),
                'cancelado_por'       => auth()->id() ?? null,
            ]);

            // Cancela títulos futuros ainda abertos ou parciais
            Titulo::where('contrato_id', $contratoLocked->id)
                ->whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '>', Carbon::today())
                ->update([
                    'status'      => 'cancelado',
                    'observacoes' => DB::raw(
                        "COALESCE(observacoes, '') || ' [Cancelado por rescisão contratual]'"
                    ),
                ]);

            // Cancela carnês de gateway ativos deste contrato
            \iEducar\Financeiro\Models\CarneGateway::where('contrato_id', $contratoLocked->id)
                ->where('status', 'ativo')
                ->update([
                    'status'        => 'cancelado',
                    'cancelado_em'  => Carbon::now(),
                    'cancelado_por' => auth()->id() ?? null,
                ]);

            // Invalida cobranças locais ativas vinculadas aos títulos cancelados
            $canceladosIds = Titulo::where('contrato_id', $contratoLocked->id)
                ->where('status', 'cancelado')
                ->pluck('id');

            CobrancaGateway::whereIn('titulo_id', $canceladosIds)
                ->where('ativa', true)
                ->update([
                    'ativa'        => false,
                    'cancelada_em' => Carbon::now(),
                ]);

            return Titulo::where('contrato_id', $contratoLocked->id)
                ->where('status', 'cancelado')
                ->where('data_vencimento', '>', Carbon::today())
                ->with('cobrancaGateway')
                ->get();
        });

        // A24 — Enfileira cancelamento externo para cada cobrança ativa no Asaas.
        foreach ($titulosComCobranca as $titulo) {
            $cobranca = $titulo->cobrancaGateway;
            if ($cobranca && !in_array($cobranca->status_externo, ['CANCELLED', 'DELETED', 'REFUNDED', 'RECEIVED', 'CONFIRMED'])) {
                CancelarCobrancaGatewayJob::dispatch($cobranca->id, $motivo);
            }
        }
    }

    /**
     * Alias para regenerarTitulosFaltantes (compatibilidade PRD BUG-023).
     */
    public function gerarTitulosFaltantes(int|Contrato $contrato): int
    {
        return $this->regenerarTitulosFaltantes($contrato);
    }

    /**
     * Regenera parcelas faltantes de um contrato preservando as condições financeiras
     * originais (rateio de responsáveis, snapshot do plano e taxa de desconto contratual).
     * (FIN-005 / BUG-023)
     *
     * @param int|Contrato $contrato
     * @return int Quantidade de novos títulos gerados
     */
    public function regenerarTitulosFaltantes(int|Contrato $contrato): int
    {
        $contratoId = $contrato instanceof Contrato ? $contrato->id : $contrato;

        return DB::transaction(function () use ($contratoId) {
            /** @var Contrato $contratoLocked */
            $contratoLocked = Contrato::lockForUpdate()->with(['plano.itens'])->findOrFail($contratoId);

            if ($contratoLocked->status === 'cancelado') {
                throw new InvalidArgumentException('Não é possível gerar títulos para um contrato cancelado.');
            }

            // Prioriza itens do snapshot contratual se existirem; caso contrário, recorre ao plano atual
            $itensSnapshot = $contratoLocked->plano_snapshot['itens'] ?? null;
            if (!empty($itensSnapshot)) {
                $itens = collect($itensSnapshot)->map(function ($it) {
                    return (object) [
                        'tipo_item'       => $it['tipo_item'] ?? 'mensalidade',
                        'numero_parcela'  => (int) ($it['numero_parcela'] ?? 1),
                        'mes_competencia' => (int) ($it['mes_competencia'] ?? 1),
                        'valor'           => (float) ($it['valor'] ?? 0.0),
                        'descricao'       => $it['descricao'] ?? 'Mensalidade Escolar',
                    ];
                });
            } else {
                $plano = $contratoLocked->plano;
                if (!$plano || $plano->itens->isEmpty()) {
                    throw new InvalidArgumentException('O plano de pagamento associado não possui itens configurados.');
                }
                $itens = $plano->itens;
            }

            $alunoId = $contratoLocked->aluno_id;
            $escolaId = $contratoLocked->escola_id;
            $anoLetivo = $contratoLocked->ano_letivo;
            $diaVencimento = $contratoLocked->dia_vencimento;

            // Identifica responsáveis financeiros do aluno
            $vinculos = AlunoResponsavel::with('responsavel')
                ->where('aluno_id', $alunoId)
                ->get();

            if ($vinculos->isEmpty()) {
                $vinculos = collect([
                    (object) [
                        'responsavel_id'        => $contratoLocked->responsavel_financeiro_id,
                        'percentual_rateio'     => 100.00,
                        'responsavel_principal' => true,
                    ]
                ]);
            }

            // Preserva as condições financeiras contratadas (taxa de desconto original)
            $valorBruto    = (float) $contratoLocked->valor_total_bruto;
            $descontoTotal = (float) $contratoLocked->valor_total_descontos;
            $taxaDesconto  = ($valorBruto > 0) ? ($descontoTotal / $valorBruto) : 0.0;

            $totalParcelas = $itens->where('numero_parcela', '>', 0)->count() ?: 1;

            // Bloqueia e consulta títulos já existentes para este contrato
            $titulosExistentes = Titulo::where('contrato_id', $contratoLocked->id)
                ->lockForUpdate()
                ->get();

            // Contratos Pix possuem cota única (1/1); se já existir título, não regenera parcelas do plano
            if ($contratoLocked->forma_pagamento === 'pix' && $titulosExistentes->isNotEmpty()) {
                return 0;
            }

            $gerados = 0;

            foreach ($itens as $item) {
                $anosAdicionais = ($item->numero_parcela > 0) ? intdiv($item->numero_parcela - 1, 12) : 0;
                $anoEfetivo     = $anoLetivo + $anosAdicionais;
                $mesComp        = str_pad($item->mes_competencia, 2, '0', STR_PAD_LEFT);
                $competencia    = "{$anoEfetivo}-{$mesComp}";

                $ultimoDiaMes   = Carbon::create($anoEfetivo, $item->mes_competencia, 1)->endOfMonth()->day;
                $diaEfetivo     = min($diaVencimento, $ultimoDiaMes);
                $dataVencimento = Carbon::create($anoEfetivo, $item->mes_competencia, $diaEfetivo);

                $valorItemBruto   = (float) $item->valor;
                $descontoItem     = round($valorItemBruto * $taxaDesconto, 2);
                $valorItemLiquido = round($valorItemBruto - $descontoItem, 2);

                $valoresRateio = $this->distribuirComResiduo($valorItemLiquido, $vinculos);

                foreach ($vinculos as $idx => $vinculo) {
                    $valorVinculo = $valoresRateio[$idx];
                    if ($valorVinculo <= 0) {
                        continue;
                    }

                    // Verifica se já existe título para esta parcela, responsável e tipo
                    $existe = $titulosExistentes->first(function ($t) use ($item, $vinculo) {
                        return (int)$t->numero_parcela === (int)$item->numero_parcela
                            && (int)$t->responsavel_financeiro_id === (int)$vinculo->responsavel_id
                            && $t->tipo === $item->tipo_item;
                    });

                    if ($existe) {
                        continue;
                    }

                    Titulo::create([
                        'contrato_id'               => $contratoLocked->id,
                        'aluno_id'                  => $alunoId,
                        'responsavel_financeiro_id' => $vinculo->responsavel_id,
                        'escola_id'                 => $escolaId,
                        'tipo'                      => $item->tipo_item,
                        'numero_parcela'            => $item->numero_parcela,
                        'total_parcelas'            => $totalParcelas,
                        'competencia'               => $competencia,
                        'data_vencimento'           => $dataVencimento,
                        'valor_nominal'             => $valorVinculo,
                        'valor_desconto'            => 0.00,
                        'valor_multa'               => 0.00,
                        'valor_juros'               => 0.00,
                        'valor_pago'                => 0.00,
                        'valor_saldo'               => $valorVinculo,
                        'status'                    => 'aberto',
                        'observacoes'               => sprintf(
                            '%s - %s',
                            $contratoLocked->numero_contrato,
                            $item->descricao
                        ),
                    ]);

                    $gerados++;
                }
            }

            return $gerados;
        });
    }
}
