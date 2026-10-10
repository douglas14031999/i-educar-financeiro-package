<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use iEducar\Financeiro\Models\AlunoResponsavel;
use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\PlanoPagamento;
use iEducar\Financeiro\Models\ResponsavelFinanceiro;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ContratoService
{
    /**
     * Cria um novo contrato financeiro e gera suas parcelas respeitando rateios de pais
     *
     * @param array $dados
     * @return Contrato
     */
    public function criarContratoEParcelas(array $dados): Contrato
    {
        return DB::transaction(function () use ($dados) {
            $alunoId = (int) $dados['aluno_id'];
            $planoId = (int) $dados['plano_id'];
            $anoLetivo = (int) $dados['ano_letivo'];
            $escolaId = (int) ($dados['escola_id'] ?? 1);
            $diaVencimento = (int) ($dados['dia_vencimento'] ?? 10);

            $plano = PlanoPagamento::with('itens')->findOrFail($planoId);

            // Identifica responsáveis financeiros do aluno
            $vinculos = AlunoResponsavel::with('responsavel')
                ->where('aluno_id', $alunoId)
                ->get();

            if ($vinculos->isEmpty()) {
                // Se ainda não houver vínculo cadastrado, requer id de responsável principal direto
                if (empty($dados['responsavel_financeiro_id'])) {
                    throw new InvalidArgumentException('O aluno não possui responsável financeiro cadastrado.');
                }
                $responsavelPrincipalId = (int) $dados['responsavel_financeiro_id'];
                $vinculos = collect([
                    (object) [
                        'responsavel_id' => $responsavelPrincipalId,
                        'percentual_rateio' => 100.00,
                        'responsavel_principal' => true,
                    ]
                ]);
            } else {
                $responsavelPrincipalId = $vinculos->firstWhere('responsavel_principal', true)->responsavel_id
                    ?? $vinculos->first()->responsavel_id;
            }

            $numeroContrato = Contrato::gerarNumeroContrato($anoLetivo);
            $dataInicio = Carbon::parse($dados['data_inicio'] ?? "{$anoLetivo}-01-01");
            $dataTermino = Carbon::parse($dados['data_termino'] ?? "{$anoLetivo}-12-31");

            $descontoTotal = (float) ($dados['valor_total_descontos'] ?? 0.00);
            $valorLiquido = max(0, $plano->valor_total - $descontoTotal);

            $contrato = Contrato::create([
                'numero_contrato' => $numeroContrato,
                'matricula_id' => $dados['matricula_id'],
                'aluno_id' => $alunoId,
                'responsavel_financeiro_id' => $responsavelPrincipalId,
                'plano_id' => $plano->id,
                'ano_letivo' => $anoLetivo,
                'data_inicio' => $dataInicio,
                'data_termino' => $dataTermino,
                'valor_total_bruto' => $plano->valor_total,
                'valor_total_descontos' => $descontoTotal,
                'valor_total_liquido' => $valorLiquido,
                'dia_vencimento' => $diaVencimento,
                'status' => 'ativo',
            ]);

            // Geração dos títulos para cada item do plano
            $totalParcelas = $plano->itens->where('numero_parcela', '>', 0)->count();

            foreach ($plano->itens as $item) {
                $mesComp = str_pad($item->mes_competencia, 2, '0', STR_PAD_LEFT);
                $competencia = "{$anoLetivo}-{$mesComp}";

                // Ajusta dia de vencimento para o mês
                $diaItem = $dados['dia_vencimento'] ?? $item->dia_vencimento_padrao;
                $ultimoDiaMes = Carbon::create($anoLetivo, $item->mes_competencia, 1)->endOfMonth()->day;
                $diaEfetivo = min($diaItem, $ultimoDiaMes);
                $dataVencimento = Carbon::create($anoLetivo, $item->mes_competencia, $diaEfetivo);

                // Rateio entre os responsáveis cadastrados
                foreach ($vinculos as $vinculo) {
                    $proporcao = ($vinculo->percentual_rateio / 100);
                    $valorItemProporcional = round($item->valor * $proporcao, 2);

                    Titulo::create([
                        'contrato_id' => $contrato->id,
                        'aluno_id' => $alunoId,
                        'responsavel_financeiro_id' => $vinculo->responsavel_id,
                        'escola_id' => $escolaId,
                        'tipo' => $item->tipo_item,
                        'numero_parcela' => $item->numero_parcela,
                        'total_parcelas' => $totalParcelas ?: 1,
                        'competencia' => $competencia,
                        'data_vencimento' => $dataVencimento,
                        'valor_nominal' => $valorItemProporcional,
                        'valor_desconto' => 0.00,
                        'valor_multa' => 0.00,
                        'valor_juros' => 0.00,
                        'valor_pago' => 0.00,
                        'valor_saldo' => $valorItemProporcional,
                        'status' => 'aberto',
                        'observacoes' => sprintf('%s - %s', $contrato->numero_contrato, $item->descricao),
                    ]);
                }
            }

            return $contrato;
        });
    }

    /**
     * Aplica cancelamento de contrato conforme política aprovada no Socratic Gate:
     * Cancela apenas parcelas futuras não vencidas; mantém parcelas vencidas em cobrança.
     *
     * @param Contrato $contrato
     * @param string $motivo
     * @return void
     */
    public function cancelarContrato(Contrato $contrato, string $motivo): void
    {
        DB::transaction(function () use ($contrato, $motivo) {
            $contrato->update([
                'status' => 'cancelado',
                'motivo_cancelamento' => $motivo,
            ]);

            $hoje = Carbon::today();

            // Cancela parcelas futuras ainda não liquidadas
            Titulo::where('contrato_id', $contrato->id)
                ->where('status', 'aberto')
                ->where('data_vencimento', '>', $hoje)
                ->update([
                    'status' => 'cancelado',
                    'observacoes' => DB::raw("COALESCE(observacoes, '') || ' [Cancelado por rescisão contratual]'"),
                ]);
        });
    }
}
