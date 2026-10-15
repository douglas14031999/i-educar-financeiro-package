<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Support\AlunoLookup;

class InadimplenciaCheckService
{
    /**
     * Verifica se o aluno possui débitos em aberto para fins de rematrícula (Lei 9.870/99)
     *
     * @param int $alunoId
     * @param int|null $escolaId
     * @param int $diasTolerancia
     * @return array
     */
    public function verificarAluno(int $alunoId, ?int $escolaId = null, int $diasTolerancia = 0): array
    {
        $query = Titulo::vencidos()
            ->with(['responsavel'])
            ->where('aluno_id', $alunoId)
            ->where('valor_saldo', '>', 0.005);

        if ($escolaId) {
            $query->where('escola_id', $escolaId);
        }

        $titulosAtrasados = $query->orderBy('data_vencimento')->get();

        // Se houver tolerância configurada, filtra os títulos cujo atraso é superior à tolerância
        if ($diasTolerancia > 0) {
            $titulosAtrasados = $titulosAtrasados->filter(function ($t) use ($diasTolerancia) {
                return $t->dias_atraso > $diasTolerancia;
            })->values();
        }

        AlunoLookup::carregarParaTitulos($titulosAtrasados);

        $totalDebito = (float) $titulosAtrasados->sum('valor_saldo');
        $qtdTitulos = $titulosAtrasados->count();
        $inadimplente = $qtdTitulos > 0;

        return [
            'aluno_id' => $alunoId,
            'aluno_nome' => AlunoLookup::nome($alunoId),
            'inadimplente' => $inadimplente,
            'pode_rematricular' => !$inadimplente,
            'total_debito' => $totalDebito,
            'qtd_titulos_vencidos' => $qtdTitulos,
            'maior_atraso_dias' => $inadimplente ? (int) $titulosAtrasados->max('dias_atraso') : 0,
            'fundamentacao_legal' => $inadimplente 
                ? 'De acordo com o Art. 5º da Lei Federal nº 9.870/1999, a instituição de ensino tem o direito legal de recusar a renovação de matrícula (rematrícula) de alunos inadimplentes, sendo vedadas penalidades pedagógicas durante o ano letivo em curso.'
                : 'Aluno em situação financeira regular perante a instituição.',
            'titulos' => $titulosAtrasados,
        ];
    }

    /**
     * Verifica débitos acumulados de um responsável financeiro específico
     *
     * @param int $responsavelId
     * @return array
     */
    public function verificarResponsavel(int $responsavelId): array
    {
        // A33 / BUG-028: Remove with(['aluno']) inexistente e usa Titulo::vencidos() com saldo > 0
        $titulosAtrasados = Titulo::vencidos()
            ->with(['responsavel'])
            ->where('responsavel_financeiro_id', $responsavelId)
            ->where('valor_saldo', '>', 0.005)
            ->orderBy('data_vencimento')
            ->get();

        AlunoLookup::carregarParaTitulos($titulosAtrasados);

        $totalDebito = (float) $titulosAtrasados->sum('valor_saldo');

        return [
            'responsavel_id' => $responsavelId,
            'inadimplente' => $titulosAtrasados->isNotEmpty(),
            'total_debito' => $totalDebito,
            'qtd_titulos_vencidos' => $titulosAtrasados->count(),
            'titulos' => $titulosAtrasados,
        ];
    }

    /**
     * Calcula o relatório consolidado de Inadimplência por Faixas de Atraso (Aging List)
     *
     * Faixas:
     * - 1 a 30 dias
     * - 31 a 60 dias
     * - 61 a 90 dias
     * - Mais de 90 dias (PCLD - Perdas Estimadas com Créditos de Liquidação Duvidosa)
     *
     * @param int|null $escolaId
     * @param int|null $ano
     * @return array
     */
    public function obterRelatorioAging(?int $escolaId = null, ?int $ano = null): array
    {
        $query = Titulo::vencidos()
            ->with(['responsavel'])
            ->where('valor_saldo', '>', 0.005);

        if ($escolaId) {
            $query->where('escola_id', $escolaId);
        }

        if ($ano) {
            $query->whereHas('contrato', function ($q) use ($ano) {
                $q->where('ano_letivo', $ano);
            });
        }

        $titulosVencidos = $query->orderBy('data_vencimento')->get();
        AlunoLookup::carregarParaTitulos($titulosVencidos, $ano);

        $faixas = [
            'faixa_1_30' => ['nome' => '1 a 30 dias', 'total' => 0.0, 'qtd' => 0, 'titulos' => []],
            'faixa_31_60' => ['nome' => '31 a 60 dias', 'total' => 0.0, 'qtd' => 0, 'titulos' => []],
            'faixa_61_90' => ['nome' => '61 a 90 dias', 'total' => 0.0, 'qtd' => 0, 'titulos' => []],
            'faixa_acima_90' => ['nome' => 'Mais de 90 dias (PCLD)', 'total' => 0.0, 'qtd' => 0, 'titulos' => []],
        ];

        $totalGeralInadimplente = 0.0;

        foreach ($titulosVencidos as $titulo) {
            $diasAtraso = $titulo->dias_atraso;
            $saldo = (float) $titulo->valor_saldo;
            $totalGeralInadimplente += $saldo;

            if ($diasAtraso <= 30) {
                $faixas['faixa_1_30']['total'] += $saldo;
                $faixas['faixa_1_30']['qtd']++;
                $faixas['faixa_1_30']['titulos'][] = $titulo;
            } elseif ($diasAtraso <= 60) {
                $faixas['faixa_31_60']['total'] += $saldo;
                $faixas['faixa_31_60']['qtd']++;
                $faixas['faixa_31_60']['titulos'][] = $titulo;
            } elseif ($diasAtraso <= 90) {
                $faixas['faixa_61_90']['total'] += $saldo;
                $faixas['faixa_61_90']['qtd']++;
                $faixas['faixa_61_90']['titulos'][] = $titulo;
            } else {
                $faixas['faixa_acima_90']['total'] += $saldo;
                $faixas['faixa_acima_90']['qtd']++;
                $faixas['faixa_acima_90']['titulos'][] = $titulo;
            }
        }

        return [
            'total_geral' => round($totalGeralInadimplente, 2),
            'total_titulos' => $titulosVencidos->count(),
            'faixas' => $faixas,
        ];
    }
}
