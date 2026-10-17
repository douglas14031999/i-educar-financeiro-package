<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Models\Titulo;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TituloController extends Controller
{
    /**
     * Listagem de Títulos / Mensalidades
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $competencia = $request->input('competencia');
        $busca = $request->input('busca');
        $responsavelId = $request->input('responsavel_id');
        $contratoId = $request->input('contrato_id');
        $alunoId = $request->input('aluno_id');
        $ano = $request->input('ano');
        $turmaId = $request->input('turma_id');

        $query = Titulo::with(['responsavel', 'cobrancaGateway', 'contrato']);

        if ($responsavelId) {
            $query->where('responsavel_financeiro_id', $responsavelId);
        }

        if ($contratoId) {
            $query->where('contrato_id', $contratoId);
        }

        if ($alunoId) {
            $query->where('aluno_id', $alunoId);
        }

        if ($ano) {
            $query->whereHas('contrato', function ($cq) use ($ano) {
                $cq->where('ano_letivo', (int) $ano);
            });
        }

        if ($turmaId) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('pmieducar.matricula') && \Illuminate\Support\Facades\Schema::hasTable('pmieducar.matricula_turma')) {
                    $query->whereIn('aluno_id', function ($sub) use ($turmaId) {
                        $sub->select('matricula.aluno_id')
                            ->from('pmieducar.matricula')
                            ->join('pmieducar.matricula_turma', 'matricula.cod_matricula', '=', 'matricula_turma.ref_cod_matricula')
                            ->where('matricula_turma.ref_cod_turma', (int) $turmaId)
                            ->where('matricula.ativo', 1);
                    });
                }
            } catch (\Throwable $e) {}
        }

        $modo = $request->input('modo', 'contrato'); // 'contrato' (agrupado por aluno) ou 'lista'

        // Filtragem por status: por padrão oculta renegociados (saldo R$ 0,00) para manter a tela limpa
        if ($status && $status !== 'todos') {
            if ($status === 'vencido') {
                $query->vencidos();
            } elseif ($status === 'pago') {
                $query->where(function ($q) {
                    $q->where('status', 'pago')->orWhere('valor_saldo', '<=', 0.005);
                })->whereNotIn('status', ['cancelado', 'renegociado']);
            } elseif ($status === 'renegociado') {
                $query->where('status', 'renegociado');
            } else {
                $query->where('status', $status);
            }
        } elseif ($status !== 'todos') {
            // Padrão: títulos ativos
            $query->where('status', '!=', 'renegociado');
        }

        if ($competencia) {
            $query->where('competencia', $competencia);
        }

        if ($busca) {
            $query->where(function ($q) use ($busca) {
                $cpfLimpo = preg_replace('/\D/', '', $busca);

                $q->whereHas('responsavel', function ($rq) use ($busca, $cpfLimpo) {
                    $rq->where('nome', 'ilike', "%{$busca}%");
                    if (!empty($cpfLimpo)) {
                        $rq->orWhere('cpf_cnpj', 'like', "%{$cpfLimpo}%");
                    }
                })->orWhere('observacoes', 'ilike', "%{$busca}%");
            });
        }

        // Modo Contrato (Agrupado por Aluno/Contrato com acordeão retrátil)
        $contratosPaginados = null;
        if ($modo === 'contrato') {
            $contratosQuery = \iEducar\Financeiro\Models\Contrato::with([
                'responsavel',
                'titulos' => function ($tq) use ($status) {
                    $tq->with('cobrancaGateway')->orderBy('numero_parcela')->orderBy('data_vencimento');
                    if ($status && $status !== 'todos') {
                        if ($status === 'vencido') {
                            $tq->vencidos();
                        } elseif ($status === 'pago') {
                            $tq->where(function ($q) {
                                $q->where('status', 'pago')->orWhere('valor_saldo', '<=', 0.005);
                            })->whereNotIn('status', ['cancelado', 'renegociado']);
                        } elseif ($status === 'renegociado') {
                            $tq->where('status', 'renegociado');
                        } else {
                            $tq->where('status', $status);
                        }
                    } elseif ($status !== 'todos') {
                        $tq->where('status', '!=', 'renegociado');
                    }
                }
            ]);

            if ($responsavelId) {
                $contratosQuery->where('responsavel_financeiro_id', $responsavelId);
            }
            if ($contratoId) {
                $contratosQuery->where('id', $contratoId);
            }
            if ($alunoId) {
                $contratosQuery->where('aluno_id', $alunoId);
            }
            if ($ano) {
                $contratosQuery->where('ano_letivo', (int) $ano);
            }
            if ($busca) {
                $cpfLimpo = preg_replace('/\D/', '', $busca);
                $contratosQuery->where(function ($q) use ($busca, $cpfLimpo) {
                    $q->where('numero_contrato', 'ilike', "%{$busca}%")
                      ->orWhereHas('responsavel', function ($rq) use ($busca, $cpfLimpo) {
                          $rq->where('nome', 'ilike', "%{$busca}%");
                          if (!empty($cpfLimpo)) {
                              $rq->orWhere('cpf_cnpj', 'like', "%{$cpfLimpo}%");
                          }
                      });
                });
            }

            // Exibir apenas contratos que possuam títulos correspondentes aos filtros
            $contratosQuery->whereHas('titulos', function ($tq) use ($status) {
                if ($status && $status !== 'todos') {
                    if ($status === 'vencido') {
                        $tq->vencidos();
                    } elseif ($status === 'pago') {
                        $tq->where(function ($q) {
                            $q->where('status', 'pago')->orWhere('valor_saldo', '<=', 0.005);
                        })->whereNotIn('status', ['cancelado', 'renegociado']);
                    } elseif ($status === 'renegociado') {
                        $tq->where('status', 'renegociado');
                    } else {
                        $tq->where('status', $status);
                    }
                } elseif ($status !== 'todos') {
                    $tq->where('status', '!=', 'renegociado');
                }
            });

            $contratosPaginados = $contratosQuery->orderBy('id', 'desc')->paginate(15)->withQueryString();

            // Carrega lookup de nomes de alunos para os contratos
            $alunosIds = [];
            foreach ($contratosPaginados as $c) {
                $alunoDoContrato = $c->aluno_id ?? $c->titulos->first()?->aluno_id;
                if ($alunoDoContrato) {
                    $alunosIds[] = $alunoDoContrato;
                }
                foreach ($c->titulos as $t) {
                    if ($t->aluno_id) $alunosIds[] = $t->aluno_id;
                }
            }
            \iEducar\Financeiro\Support\AlunoLookup::nomes(array_unique($alunosIds));
            \iEducar\Financeiro\Support\AlunoLookup::turmas(array_unique($alunosIds), $ano ? (int) $ano : null);
        }

        $titulos = $query->orderBy('data_vencimento')->paginate(20)->withQueryString();
        \iEducar\Financeiro\Support\AlunoLookup::carregarParaTitulos($titulos, $ano ? (int) $ano : null);

        $responsavelFiltro = $responsavelId ? \iEducar\Financeiro\Models\ResponsavelFinanceiro::find($responsavelId) : null;
        $contratoFiltro = $contratoId ? \iEducar\Financeiro\Models\Contrato::with(['titulos.cobrancaGateway'])->find($contratoId) : null;

        $formaContrato = strtolower(trim((string) ($contratoFiltro?->forma_pagamento ?? '')));
        $todosTitulosPagos = $titulos->isNotEmpty() && $titulos->every(fn($t) => in_array($t->status, ['pago', 'liquidado']) || (float)$t->valor_saldo <= 0.005);
        $contratoSemAcoesLote = $contratoFiltro && (
            $formaContrato === 'pix' ||
            $formaContrato === 'dinheiro' ||
            $formaContrato === 'cartao_presencial' ||
            in_array($contratoFiltro->status, ['quitado', 'cancelado']) ||
            $todosTitulosPagos
        );
        $temAcoesEmLote = !$contratoSemAcoesLote;

        if ($request->ajax() || $request->header('X-Partial') || $request->query('ajax')) {
            return view('financeiro::titulos.partials.tabela-container', compact(
                'titulos',
                'contratosPaginados',
                'modo',
                'status',
                'competencia',
                'busca',
                'responsavelId',
                'contratoId',
                'alunoId',
                'ano',
                'turmaId',
                'responsavelFiltro',
                'contratoFiltro',
                'temAcoesEmLote'
            ));
        }

        return view('financeiro::titulos.index', compact(
            'titulos',
            'contratosPaginados',
            'modo',
            'status',
            'competencia',
            'busca',
            'responsavelId',
            'contratoId',
            'alunoId',
            'ano',
            'turmaId',
            'responsavelFiltro',
            'contratoFiltro',
            'temAcoesEmLote'
        ));
    }

    /**
     * Detalhes de um Título
     */
    public function show($id)
    {
        $titulo = Titulo::with(['contrato', 'responsavel', 'pagamentos', 'cobrancaGateway'])->findOrFail($id);

        return view('financeiro::titulos.show', compact('titulo'));
    }

    /**
     * Emissão e Visualização do Recibo Oficial para Impressão
     */
    public function imprimirRecibo(Request $request, $id)
    {
        $titulo = Titulo::with(['contrato', 'responsavel', 'pagamentos'])->findOrFail($id);

        $pagamentoId = $request->query('pagamento_id');
        if ($pagamentoId) {
            $ultimoPagamento = $titulo->pagamentos()->where('id', $pagamentoId)->firstOrFail();
        } else {
            $ultimoPagamento = $titulo->pagamentos()->where('estornado', false)->latest('id')->first()
                ?? $titulo->pagamentos()->latest('id')->first();
        }

        return view('financeiro::titulos.recibo', compact('titulo', 'ultimoPagamento'));
    }

    /**
     * Visualização do Recibo Oficial direcionado diretamente pelo ID do Pagamento
     */
    public function imprimirReciboPorPagamento($pagamentoId)
    {
        $pagamento = \iEducar\Financeiro\Models\Pagamento::with(['titulo.contrato', 'titulo.responsavel'])->findOrFail($pagamentoId);
        $titulo = $pagamento->titulo;
        $ultimoPagamento = $pagamento;

        return view('financeiro::titulos.recibo', compact('titulo', 'ultimoPagamento'));
    }

    /**
     * Emite cobrança individual no Asaas (Boleto híbrido + Pix)
     */
    public function emitirAsaas($id)
    {
        $titulo = Titulo::with(['responsavel'])->findOrFail($id);

        if (!$titulo->isElegivelEmissaoGateway()) {
            return redirect()->back()->with('error', sprintf(
                'Este título não está elegível para emissão de cobrança no Asaas (status: %s).',
                $titulo->status
            ));
        }

        try {
            /** @var \iEducar\Financeiro\Gateways\GatewayManager $manager */
            $manager = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
            $dados = $manager->driver('asaas')->criarCobranca($titulo);

            return redirect()->route('financeiro.titulos.show', $titulo->id)
                ->with('success', 'Cobrança Asaas (Boleto/Pix) gerada com sucesso! Linha digitável e Pix disponíveis.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erro ao emitir cobrança no Asaas: ' . $e->getMessage());
        }
    }

    /**
     * Dispara emissão em lote de títulos (por seleção de IDs ou por competência)
     */
    public function emitirLoteAsaas(Request $request)
    {
        $ids = $request->input('titulo_ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_filter(array_map('intval', (array) $ids));

        // Se foram passados IDs específicos de títulos
        if (!empty($ids)) {
            // Para lotes com mais de 10 títulos, enfileira para segundo plano (BUG-001 / BUG-002)
            if (count($ids) > 10) {
                \iEducar\Financeiro\Jobs\GerarCobrancasGatewayLoteJob::dispatch($ids, null, null, auth()->id());
                return redirect()->back()->with('success', sprintf(
                    'Emissão em lote de %d títulos agendada com sucesso. As cobranças estão sendo geradas em segundo plano.',
                    count($ids)
                ));
            }

            /** @var \iEducar\Financeiro\Gateways\Drivers\AsaasGateway $gateway */
            $gateway = app(\iEducar\Financeiro\Gateways\GatewayManager::class)->driver('asaas');

            $titulos = Titulo::with(['cobrancaGateway', 'responsavel'])
                ->whereIn('id', $ids)
                ->whereIn('status', ['aberto', 'parcial', 'vencido'])
                ->get();

            $sucessos = 0;
            $erros = 0;
            $ignorados = 0;

            foreach ($titulos as $titulo) {
                try {
                    // Ignora títulos que já possuem cobrança ativa
                    if ($titulo->cobrancaGateway && !empty($titulo->cobrancaGateway->gateway_charge_id) && $titulo->cobrancaGateway->ativa) {
                        $ignorados++;
                        continue;
                    }

                    if (!$titulo->responsavel || empty($titulo->responsavel->cpf_cnpj)) {
                        $erros++;
                        continue;
                    }

                    $gateway->criarCobranca($titulo);
                    $sucessos++;
                } catch (\Throwable $e) {
                    $erros++;
                    \Illuminate\Support\Facades\Log::error("Erro ao emitir cobrança para título #{$titulo->id}: " . $e->getMessage());
                }
            }

            $mensagens = [];
            if ($sucessos > 0) $mensagens[] = "{$sucessos} cobrança(s) emitida(s) com sucesso.";
            if ($ignorados > 0) $mensagens[] = "{$ignorados} já possuíam cobrança ativa.";
            if ($erros > 0) $mensagens[] = "{$erros} título(s) falharam ou sem CPF.";

            $tipoAlerta = $erros > 0 ? 'warning' : 'success';
            return redirect()->back()->with($tipoAlerta, implode(' ', $mensagens) ?: 'Nenhum título processado.');
        }

        // Se foi passada uma competência global
        $request->validate([
            'competencia' => 'required|string',
            'escola_id' => 'nullable|integer',
        ]);

        $competencia = $request->input('competencia');
        $escolaId = $request->input('escola_id');

        \iEducar\Financeiro\Jobs\GerarCobrancasGatewayLoteJob::dispatch([], $competencia, $escolaId, auth()->id());

        return redirect()->back()->with('success', "Processamento em lote agendado para a competência {$competencia}. Os boletos e Pix estão sendo gerados em segundo plano.");
    }

    /**
     * Sincroniza o status do título consultando diretamente o Asaas
     */
    public function sincronizarStatus($id)
    {
        $titulo = Titulo::with(['cobrancaGateway'])->findOrFail($id);

        if (!$titulo->cobrancaGateway || !$titulo->cobrancaGateway->gateway_charge_id) {
            return redirect()->back()->with('error', 'Este título não possui registro ativo de cobrança no Asaas.');
        }

        try {
            /** @var \iEducar\Financeiro\Gateways\GatewayManager $manager */
            $manager = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
            $dados = $manager->driver('asaas')->consultarCobranca($titulo->cobrancaGateway->gateway_charge_id);

            if (!empty($dados)) {
                $statusExterno = $dados['status'] ?? null;
                $updateData = [
                    'status_externo' => $statusExterno,
                    'payload_resposta' => $dados,
                ];

                if (!empty($dados['identificationField'])) {
                    $updateData['linha_digitavel'] = $dados['identificationField'];
                }
                if (!empty($dados['barCode'])) {
                    $updateData['codigo_barras'] = $dados['barCode'];
                }
                if (!empty($dados['pixQrCode'])) {
                    $updateData['pix_qr_code_base64'] = $dados['pixQrCode'];
                }
                if (!empty($dados['pixCopiaCola'])) {
                    $updateData['pix_copia_cola'] = $dados['pixCopiaCola'];
                }

                $titulo->cobrancaGateway->update($updateData);

                // Se no gateway já consta como recebido mas local ainda não foi baixado
                if (in_array($statusExterno, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH']) && !$titulo->isLiquidado()) {
                    /** @var \iEducar\Financeiro\Services\BaixaTituloService $baixaService */
                    $baixaService = app(\iEducar\Financeiro\Services\BaixaTituloService::class);

                    $billingType = strtoupper($dados['billingType'] ?? 'BOLETO');
                    $forma = match ($billingType) {
                        'PIX' => 'pix',
                        'CREDIT_CARD' => 'cartao_credito',
                        'DEBIT_CARD' => 'cartao_debito',
                        'TRANSFER', 'DEPOSIT' => 'transferencia',
                        default => 'boleto',
                    };

                    $baixaService->realizarBaixa($titulo, [
                        'valor_pago' => (float) ($dados['value'] ?? $titulo->valor_saldo),
                        'forma_pagamento' => $forma,
                        'origem_baixa' => 'sincronizacao_gateway',
                        'data_pagamento' => \Carbon\Carbon::parse($dados['paymentDate'] ?? ($dados['clientPaymentDate'] ?? now())),
                        'observacoes' => "Baixa confirmada via sincronização manual com Asaas (ID {$titulo->cobrancaGateway->gateway_charge_id})"
                    ]);
                }

                return redirect()->back()->with('success', "Status sincronizado com sucesso: {$statusExterno}.");
            }

            return redirect()->back()->with('warning', 'Nenhum dado retornado pelo Asaas.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erro na sincronização com Asaas: ' . $e->getMessage());
        }
    }

    /**
     * Sincroniza em lote o status de cobranças selecionadas no Asaas
     */
    public function sincronizarLoteAsaas(Request $request)
    {
        $tituloIds = $request->input('titulo_ids', []);

        if (is_string($tituloIds)) {
            $tituloIds = explode(',', $tituloIds);
        }

        $tituloIds = array_filter(array_map('intval', (array) $tituloIds));

        if (empty($tituloIds)) {
            return redirect()->back()->with('error', 'Selecione ao menos um título para sincronizar.');
        }

        /** @var \iEducar\Financeiro\Gateways\GatewayManager $manager */
        $manager = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
        $gateway = $manager->driver('asaas');
        /** @var \iEducar\Financeiro\Services\BaixaTituloService $baixaService */
        $baixaService = app(\iEducar\Financeiro\Services\BaixaTituloService::class);

        $titulos = Titulo::with(['cobrancaGateway'])
            ->whereIn('id', $tituloIds)
            ->get();

        $sucessos = 0;
        $baixados = 0;
        $erros = 0;
        $semCobranca = 0;

        foreach ($titulos as $titulo) {
            if (!$titulo->cobrancaGateway || empty($titulo->cobrancaGateway->gateway_charge_id)) {
                $semCobranca++;
                continue;
            }

            try {
                $chargeId = $titulo->cobrancaGateway->gateway_charge_id;
                $dados = $gateway->consultarCobranca($chargeId);

                if (!empty($dados)) {
                    $statusExterno = $dados['status'] ?? null;
                    $updateData = [
                        'status_externo' => $statusExterno,
                        'payload_resposta' => $dados,
                    ];

                    if (!empty($dados['identificationField'])) {
                        $updateData['linha_digitavel'] = $dados['identificationField'];
                    }
                    if (!empty($dados['barCode'])) {
                        $updateData['codigo_barras'] = $dados['barCode'];
                    }
                    if (!empty($dados['pixQrCode'])) {
                        $updateData['pix_qr_code_base64'] = $dados['pixQrCode'];
                    }
                    if (!empty($dados['pixCopiaCola'])) {
                        $updateData['pix_copia_cola'] = $dados['pixCopiaCola'];
                    }

                    $titulo->cobrancaGateway->update($updateData);

                    if (in_array($statusExterno, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH']) && !$titulo->isLiquidado()) {
                        $baixaService->realizarBaixa($titulo, [
                            'valor_pago' => (float) ($dados['value'] ?? $titulo->valor_saldo),
                            'forma_pagamento' => ($dados['billingType'] ?? 'BOLETO') === 'PIX' ? 'pix' : 'boleto',
                            'origem_baixa' => 'sincronizacao_gateway',
                            'data_pagamento' => $dados['paymentDate'] ?? date('Y-m-d'),
                            'observacoes' => "Baixa confirmada via sincronização em lote com Asaas (ID {$chargeId})"
                        ]);
                        $baixados++;
                    }

                    $sucessos++;
                }
            } catch (\Throwable $e) {
                $erros++;
                \Illuminate\Support\Facades\Log::error("Erro na sincronização em lote do título #{$titulo->id}: " . $e->getMessage());
            }
        }

        $mensagens = [];
        if ($sucessos > 0) $mensagens[] = "{$sucessos} cobrança(s) sincronizada(s).";
        if ($baixados > 0) $mensagens[] = "{$baixados} pagamento(s) baixado(s).";
        if ($semCobranca > 0) $mensagens[] = "{$semCobranca} título(s) sem cobrança ativa no Asaas.";
        if ($erros > 0) $mensagens[] = "{$erros} falha(s) durante a consulta.";

        $tipo = $erros > 0 ? 'warning' : 'success';
        return redirect()->back()->with($tipo, implode(' ', $mensagens) ?: 'Nenhum título processado.');
    }

    /**
     * Prepara link de cobrança para disparo via WhatsApp pela secretaria (BUG-033)
     */
    public function notificarWhatsApp($id)
    {
        $titulo = Titulo::with(['responsavel', 'cobrancaGateway'])->findOrFail($id);

        if (!$titulo->responsavel || empty($titulo->responsavel->telefone_celular)) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'O responsável financeiro não possui telefone celular cadastrado para envio de WhatsApp.',
                ], 422);
            }
            return redirect()->back()->with('error', 'O responsável financeiro não possui telefone celular cadastrado para envio de WhatsApp.');
        }

        $telefone = preg_replace('/\D/', '', $titulo->responsavel->telefone_celular ?? '');
        if (strlen($telefone) < 10) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'O número de celular cadastrado é inválido para envio de WhatsApp.',
                ], 422);
            }
            return redirect()->back()->with('error', 'O número de celular cadastrado é inválido para envio de WhatsApp.');
        }

        // Adiciona DDI 55 se não tiver
        if (strlen($telefone) === 10 || strlen($telefone) === 11) {
            $telefone = '55' . $telefone;
        }

        $linkBoleto = $titulo->cobrancaGateway->link_pagamento ?? $titulo->cobrancaGateway->link_boleto_pdf ?? url("/financeiro/titulos/{$titulo->id}");
        $vencimento = \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y');
        $valor = number_format($titulo->valor_saldo, 2, ',', '.');

        $mensagem = "Olá, {$titulo->responsavel->nome}! Informamos a mensalidade escolar referente a {$titulo->competencia} (Vencimento: {$vencimento}) no valor de R$ {$valor}. Acesse a 2ª via ou pague com Pix pelo link: {$linkBoleto}";

        $url = 'https://api.whatsapp.com/send?phone=' . $telefone . '&text=' . urlencode($mensagem);

        \Illuminate\Support\Facades\Log::info("WhatsApp gerado manualmente para título #{$titulo->id} (Destinatário: {$telefone})", [
            'usuario_id' => auth()->id(),
            'titulo_id'  => $titulo->id,
            'telefone'   => $telefone,
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'url' => $url,
                'mensagem' => $mensagem,
                'telefone' => $telefone,
            ]);
        }

        return redirect()->away($url);
    }

    /**
     * Emissão e Impressão de Carnê Escolar (FIN-004)
     *
     * Preserva estritamente a seleção enviada por IDs.
     * Gera e obtém o carnê oficial diretamente do Asaas (PDF com QR Code Pix e Linha Digitável)
     * com fallback inteligente para títulos individuais ou renderização local.
     */
    public function imprimirCarne(Request $request)
    {
        $ids = $request->input('titulo_ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        $ids = array_filter(array_map('intval', (array) $ids));
        $contratoId = $request->input('contrato_id');

        if (empty($ids) && empty($contratoId)) {
            return redirect()->back()->with('error', 'Nenhum título ou contrato foi selecionado para impressão de carnê.');
        }

        $query = Titulo::with(['responsavel', 'cobrancaGateway', 'contrato']);

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query->where('contrato_id', $contratoId);
        }

        $titulos = $query->orderBy('data_vencimento')->get();

        if ($titulos->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhum título encontrado para os parâmetros selecionados.');
        }

        // Títulos cancelados são inelegíveis para compor carnê
        $inelegiveis = $titulos->filter(fn($t) => $t->status === 'cancelado');
        if ($inelegiveis->count() === $titulos->count()) {
            return redirect()->back()->with('error', 'Todos os títulos selecionados estão cancelados ou inelegíveis para impressão de carnê.');
        }

        // 1. Identificar contrato associado (por parâmetro explícito ou unânime entre os títulos)
        $contrato = null;
        if (!empty($contratoId)) {
            $contrato = \iEducar\Financeiro\Models\Contrato::with(['responsavel', 'titulos.cobrancaGateway', 'titulos.responsavel'])->find($contratoId);
        } elseif ($titulos->isNotEmpty()) {
            $contratoIds = $titulos->pluck('contrato_id')->filter()->unique();
            if ($contratoIds->count() === 1) {
                $contrato = \iEducar\Financeiro\Models\Contrato::with(['responsavel', 'titulos.cobrancaGateway', 'titulos.responsavel'])->find($contratoIds->first());
            }
        }

        if ($contrato) {
            // Se o contrato tiver forma de pagamento diferente de 'carne', redireciona para a respectiva opção
            if (!empty($contrato->forma_pagamento) && $contrato->forma_pagamento !== 'carne') {
                if ($contrato->forma_pagamento === 'boleto' && $titulos->count() === 1 && $titulos->first()->cobrancaGateway?->link_boleto_pdf) {
                    return redirect()->away($titulos->first()->cobrancaGateway->link_boleto_pdf);
                }
                return redirect()->route('financeiro.contratos.checkout', $contrato->id)
                    ->with('aviso', 'Este contrato possui modalidade ' . ucfirst(str_replace('_', ' ', $contrato->forma_pagamento)) . ' e não possui emissão de Carnê.');
            }

            // Se foi pedido sem IDs específicos e tem link externo direto salvo
            if (empty($ids) && !empty($contrato->carne_asaas_url)) {
                return redirect()->away($contrato->carne_asaas_url);
            }

            // A. Verifica se já existe um carnê (installment) ativo no Asaas para este contrato
            $carne = \iEducar\Financeiro\Models\CarneGateway::where('contrato_id', $contrato->id)
                ->where('status', 'ativo')
                ->first();

            $cobrancaExistente = \iEducar\Financeiro\Models\CobrancaGateway::where('contrato_id', $contrato->id)
                ->whereNotNull('installment_id')
                ->first();

            $installmentId = $carne?->installment_id ?? $cobrancaExistente?->installment_id;

            // B. Se ainda não há installment registrado no Asaas, gera sob demanda apenas se a modalidade for carnê
            if (empty($installmentId) && ($contrato->forma_pagamento === 'carne' || (empty($contrato->forma_pagamento) && count($titulos) >= 2))) {
                try {
                    /** @var \iEducar\Financeiro\Services\CarneService $carneService */
                    $carneService = app(\iEducar\Financeiro\Services\CarneService::class);
                    $novoCarne = $carneService->gerar($contrato, $titulos->all(), auth()->id() ?? null);
                    $installmentId = $novoCarne->installment_id;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Erro ao gerar carnê sob demanda no Asaas via TituloController', [
                        'contrato_id' => $contrato->id,
                        'erro'        => $e->getMessage(),
                    ]);
                }
            }

            // C. Obtém o PDF oficial do carnê gerado pelo Asaas (todas as lâminas com QR Code Pix e Linha Digitável)
            if (!empty($installmentId)) {
                try {
                    /** @var \iEducar\Financeiro\Gateways\GatewayManager $manager */
                    $manager = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
                    /** @var \iEducar\Financeiro\Gateways\Drivers\AsaasGateway $gateway */
                    $gateway = $manager->driver('asaas');

                    $pdfContent = $gateway->baixarCarnePdf($installmentId);

                    if (!empty($pdfContent)) {
                        $filename = 'carne-asaas-' . $contrato->numero_contrato . '.pdf';
                        return response($pdfContent, 200, [
                            'Content-Type'        => 'application/pdf',
                            'Content-Disposition' => "inline; filename=\"{$filename}\"",
                            'Cache-Control'       => 'no-store, no-cache',
                        ]);
                    }

                    $carneUrl = $gateway->gerarCarneUrl($installmentId);
                    if (!empty($carneUrl)) {
                        return redirect()->away($carneUrl);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Falha ao obter PDF do carnê Asaas via TituloController', [
                        'contrato_id' => $contrato->id,
                        'erro'        => $e->getMessage(),
                    ]);
                }
            }
        }

        // Se for um único título selecionado com boleto direto gerado no gateway
        if ($titulos->count() === 1) {
            $cobrancaUnica = $titulos->first()->cobrancaGateway;
            if ($cobrancaUnica && !empty($cobrancaUnica->link_boleto_pdf)) {
                return redirect()->away($cobrancaUnica->link_boleto_pdf);
            }
        }

        // Caso contrário, renderiza rigorosamente apenas os títulos selecionados na view do carnê
        return view('financeiro::titulos.carne', compact('titulos', 'contrato'));
    }
}

