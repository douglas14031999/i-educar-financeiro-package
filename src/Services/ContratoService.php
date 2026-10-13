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
            // A03 / A28 — escola_id obrigatório; sem fallback para ID 1
            // ----------------------------------------------------------------
            $escolaId = !empty($dados['escola_id']) ? (int) $dados['escola_id'] : null;

            if (!$escolaId && $matriculaId) {
                $matriculaRow = DB::table('pmieducar.matricula')
                    ->where('cod_matricula', $matriculaId)
                    ->select('ref_ref_cod_escola', 'ref_cod_aluno')
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

                $escolaId = $matriculaRow->ref_ref_cod_escola
                    ? (int) $matriculaRow->ref_ref_cod_escola
                    : null;
            }

            if (!$escolaId) {
                throw new InvalidArgumentException(
                    'Não foi possível determinar a escola a partir da matrícula informada. ' .
                    'Informe escola_id explicitamente.'
                );
            }

            // ----------------------------------------------------------------
            // Identifica responsáveis financeiros do aluno
            // ----------------------------------------------------------------
            $vinculos = AlunoResponsavel::with('responsavel')
                ->where('aluno_id', $alunoId)
                ->get();

            if ($vinculos->isEmpty()) {
                if (empty($dados['responsavel_financeiro_id'])) {
                    throw new InvalidArgumentException(
                        'O aluno não possui responsável financeiro cadastrado.'
                    );
                }
                $responsavelPrincipalId = (int) $dados['responsavel_financeiro_id'];
                $vinculos = collect([
                    (object) [
                        'responsavel_id'        => $responsavelPrincipalId,
                        'percentual_rateio'     => 100.00,
                        'responsavel_principal' => true,
                    ]
                ]);
            } else {
                $responsavelPrincipalId =
                    $vinculos->firstWhere('responsavel_principal', true)?->responsavel_id
                    ?? $vinculos->first()->responsavel_id;
            }

            // A03 — Soma de rateios deve ser exatamente 100%
            $somaRateio = (float) $vinculos->sum('percentual_rateio');
            if (abs($somaRateio - 100.00) > 0.001) {
                throw new InvalidArgumentException(
                    sprintf(
                        'A soma dos rateios deve ser exatamente 100%%. Soma atual: %.4f%%.',
                        $somaRateio
                    )
                );
            }

            $plano = PlanoPagamento::with('itens')->findOrFail($planoId);

            $descontoTotal = (float) ($dados['valor_total_descontos'] ?? 0.00);
            $valorBruto    = (float) $plano->valor_total;
            $valorLiquido  = max(0.0, round($valorBruto - $descontoTotal, 2));

            // A01 — Taxa de desconto proporcional aplicada a cada item
            $taxaDesconto = ($valorBruto > 0) ? ($descontoTotal / $valorBruto) : 0.0;

            // -------------------------------------------------------------------
            // A29 — número do contrato: retry até 5x para escapar de gaps
            // Sequências PostgreSQL não fazem rollback: um nextval consumido em
            // uma transação abortada gera um "buraco" que causa 23505 na retentativa.
            // O loop consome até 5 nextvals até encontrar um número livre.
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
            ]);

            $totalParcelas = $plano->itens->where('numero_parcela', '>', 0)->count();

            foreach ($plano->itens as $item) {
                $mesComp     = str_pad($item->mes_competencia, 2, '0', STR_PAD_LEFT);
                $competencia = "{$anoLetivo}-{$mesComp}";

                // Trata vencimento em dias 29/30/31 sem overflow de mês (T20)
                $ultimoDiaMes   = Carbon::create($anoLetivo, $item->mes_competencia, 1)->endOfMonth()->day;
                $diaEfetivo     = min($diaVencimento, $ultimoDiaMes);
                $dataVencimento = Carbon::create($anoLetivo, $item->mes_competencia, $diaEfetivo);

                // A01 — Valor líquido do item após desconto proporcional
                $valorItemBruto   = (float) $item->valor;
                $descontoItem     = round($valorItemBruto * $taxaDesconto, 2);
                $valorItemLiquido = round($valorItemBruto - $descontoItem, 2);

                // A02 — Distribui com resíduo de centavo (Largest Remainder)
                $valoresRateio = $this->distribuirComResiduo($valorItemLiquido, $vinculos);

                foreach ($vinculos as $idx => $vinculo) {
                    $valorVinculo = $valoresRateio[$idx];

                    // RN22 — Percentual zero não gera cobrança
                    if ($valorVinculo <= 0) {
                        continue;
                    }

                    Titulo::create([
                        'contrato_id'               => $contrato->id,
                        'aluno_id'                  => $alunoId,
                        'responsavel_financeiro_id' => $vinculo->responsavel_id,
                        'escola_id'                 => $escolaId,
                        'tipo'                      => $item->tipo_item,
                        'numero_parcela'            => $item->numero_parcela,
                        'total_parcelas'            => $totalParcelas ?: 1,
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
                            $contrato->numero_contrato,
                            $item->descricao
                        ),
                    ]);
                }
            }

            return $contrato;
        });
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
     * Cancela o contrato conforme política aprovada:
     * cancela apenas parcelas futuras não vencidas; mantém vencidas em cobrança.
     *
     * A24 — Após cancelamento local, enfileira Job para cancelar cobranças no Asaas.
     * O Job é assíncrono: sem timeout na request do usuário + retentativas automáticas.
     *
     * @param  Contrato $contrato
     * @param  string   $motivo
     * @return void
     */
    public function cancelarContrato(Contrato $contrato, string $motivo): void
    {
        // Coleta IDs de cobranças ativas ANTES do update (para enfileirar após commit)
        $titulosComCobranca = Titulo::where('contrato_id', $contrato->id)
            ->where('status', 'aberto')
            ->where('data_vencimento', '>', Carbon::today())
            ->with('cobrancaGateway')
            ->get();

        DB::transaction(function () use ($contrato, $motivo, $titulosComCobranca) {
            $contrato->update([
                'status'              => 'cancelado',
                'motivo_cancelamento' => $motivo,
            ]);

            Titulo::where('contrato_id', $contrato->id)
                ->where('status', 'aberto')
                ->where('data_vencimento', '>', Carbon::today())
                ->update([
                    'status'      => 'cancelado',
                    'observacoes' => DB::raw(
                        "COALESCE(observacoes, '') || ' [Cancelado por rescisão contratual]'"
                    ),
                ]);
        });

        // A24 — Enfileira cancelamento externo para cada cobrança ativa no Asaas.
        // Fora da transação: falha no gateway não reverte o cancelamento local.
        foreach ($titulosComCobranca as $titulo) {
            $cobranca = $titulo->cobrancaGateway;
            if ($cobranca && !in_array($cobranca->status_externo, ['CANCELLED', 'DELETED', 'REFUNDED', 'RECEIVED', 'CONFIRMED'])) {
                CancelarCobrancaGatewayJob::dispatch($cobranca->id, $motivo);
            }
        }
    }
}
