<?php

namespace iEducar\Financeiro\Http\Controllers;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\Lancamento;
use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\PlanoConta;
use iEducar\Financeiro\Models\ResponsavelFinanceiro;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\InadimplenciaCheckService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RelatorioFinanceiroController extends Controller
{
    /**
     * Hub Central de Relatórios Financeiros
     */
    public function index()
    {
        return view('financeiro::relatorios.index');
    }

    /**
     * Painel de Gestão de Inadimplência e Aging List (TASK-8.4)
     */
    public function inadimplencia(Request $request, InadimplenciaCheckService $service)
    {
        $escolaId = $request->input('escola_id');
        $alunoId = $request->input('aluno_id');

        $aging = $service->obterRelatorioAging($escolaId);

        $resultadoAluno = null;
        if ($alunoId) {
            $resultadoAluno = $service->verificarAluno((int) $alunoId);
        }

        return view('financeiro::relatorios.inadimplencia', compact('aging', 'resultadoAluno', 'alunoId', 'escolaId'));
    }

    /**
     * Relatório de Fluxo de Caixa (Realizado vs Previsto)
     */
    public function fluxoCaixa(Request $request)
    {
        $ano = $request->input('ano', date('Y'));

        // Receitas Realizadas (Pagamentos efetivos)
        $receitasPorMes = [];
        $despesasPorMes = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $inicioMes = Carbon::create($ano, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fimMes = Carbon::create($ano, $mes, 1)->endOfMonth()->format('Y-m-d');

            $rec = Pagamento::where('estornado', false)
                ->whereBetween('data_pagamento', [$inicioMes, $fimMes])
                ->sum('valor_pago');

            $desp = Lancamento::where('tipo', 'despesa')
                ->where('status', 'pago')
                ->whereBetween('data_pagamento', [$inicioMes, $fimMes])
                ->sum('valor');

            $receitasPorMes[$mes] = (float) $rec;
            $despesasPorMes[$mes] = (float) $desp;
        }

        $totalReceitasAno = array_sum($receitasPorMes);
        $totalDespesasAno = array_sum($despesasPorMes);
        $saldoOperacionalAno = $totalReceitasAno - $totalDespesasAno;

        return view('financeiro::relatorios.fluxo-caixa', compact(
            'ano',
            'receitasPorMes',
            'despesasPorMes',
            'totalReceitasAno',
            'totalDespesasAno',
            'saldoOperacionalAno'
        ));
    }

    /**
     * DRE - Demonstração do Resultado do Exercício Escolar
     */
    public function dre(Request $request)
    {
        $ano        = $request->input('ano', date('Y'));
        $dataInicio = "{$ano}-01-01";
        $dataFim    = "{$ano}-12-31";

        // FIN-013 — Receita Bruta REALIZADA: valor efetivamente liquidado antes das deduções.
        // Fórmula contábil: Receita Bruta = valor_pago + deduções (descontos concedidos).
        // Isso reconcilia a Receita Líquida exatamente com o Caixa Efetivo (valor_pago) sem dupla contagem de encargos.
        $receitaBruta = Pagamento::where('estornado', false)
            ->whereBetween('data_pagamento', [$dataInicio, $dataFim])
            ->sum(\Illuminate\Support\Facades\DB::raw('valor_pago + valor_desconto_concedido'));
        $receitaBruta = (float) $receitaBruta;

        // Deduções: descontos concedidos nos pagamentos reais
        $deducoes = (float) Pagamento::where('estornado', false)
            ->whereBetween('data_pagamento', [$dataInicio, $dataFim])
            ->sum('valor_desconto_concedido');

        // Receita Líquida Operacional (= Caixa Líquido Arrecadado de Receitas)
        $receitaLiquida = round($receitaBruta - $deducoes, 2);

        // Despesas Operacionais por Categoria do Plano de Contas
        $despesasPorCategoria = Lancamento::where('tipo', 'despesa')
            ->where('status', 'pago')
            ->whereBetween('data_pagamento', [$dataInicio, $dataFim])
            ->with('planoConta')
            ->selectRaw('plano_conta_id, sum(valor) as total')
            ->groupBy('plano_conta_id')
            ->get();

        $totalDespesasOperacionais = (float) $despesasPorCategoria->sum('total');

        // Resultado Líquido do Exercício (Lucro / Superávit ou Déficit)
        $resultadoLiquido = round($receitaLiquida - $totalDespesasOperacionais, 2);

        return view('financeiro::relatorios.dre', compact(
            'ano',
            'receitaBruta',
            'deducoes',
            'receitaLiquida',
            'despesasPorCategoria',
            'totalDespesasOperacionais',
            'resultadoLiquido'
        ));
    }

    /**
     * Declaração de Pagamentos para IRPF (Imposto de Renda Pessoa Física)
     */
    public function informeIRPF(Request $request)
    {
        $anoBase = $request->input('ano_base', date('Y') - 1);
        $responsavelId = $request->input('responsavel_id');

        $responsavel = null;
        $pagamentosAgrupados = [];
        $totalDeclarado = 0.0;

        if ($responsavelId) {
            $responsavel = ResponsavelFinanceiro::findOrFail($responsavelId);

            $pagamentos = Pagamento::with(['titulo'])
                ->where('estornado', false)
                ->whereYear('data_pagamento', $anoBase)
                ->whereHas('titulo', function ($q) use ($responsavelId) {
                    $q->where('responsavel_financeiro_id', $responsavelId);
                })
                ->get();

            // Agrupa por aluno
            foreach ($pagamentos as $pag) {
                $alunoId = $pag->titulo->aluno_id;
                if (!isset($pagamentosAgrupados[$alunoId])) {
                    $pagamentosAgrupados[$alunoId] = [
                        'aluno_id' => $alunoId,
                        'total' => 0.0,
                        'itens' => [],
                    ];
                }
                $pagamentosAgrupados[$alunoId]['total'] += (float) $pag->valor_pago;
                $pagamentosAgrupados[$alunoId]['itens'][] = $pag;
                $totalDeclarado += (float) $pag->valor_pago;
            }
        }

        $responsaveis = ResponsavelFinanceiro::orderBy('nome')->get();

        return view('financeiro::relatorios.informe-irpf', compact(
            'anoBase',
            'responsavel',
            'responsaveis',
            'pagamentosAgrupados',
            'totalDeclarado'
        ));
    }

    /**
     * Declaração Anual de Quitação de Débitos (Lei Federal nº 12.007/2009)
     */
    public function quitacaoAnual(Request $request)
    {
        $ano = $request->input('ano', date('Y') - 1);
        $responsavelId = $request->input('responsavel_id');

        $responsavel = null;
        $quitadoIntegralmente = false;
        $titulosAno = collect();

        if ($responsavelId) {
            $responsavel = ResponsavelFinanceiro::findOrFail($responsavelId);

            $titulosAno = Titulo::where('responsavel_financeiro_id', $responsavelId)
                ->whereYear('data_vencimento', $ano)
                ->where('status', '!=', 'cancelado')
                ->orderBy('data_vencimento')
                ->get();

            // Lei 12.007/2009: Todos os títulos do ano devem estar quitados
            $pendencias = $titulosAno->filter(function ($t) {
                return !$t->isLiquidado();
            });

            $quitadoIntegralmente = $titulosAno->isNotEmpty() && $pendencias->isEmpty();
        }

        $responsaveis = ResponsavelFinanceiro::orderBy('nome')->get();

        return view('financeiro::relatorios.quitacao-anual', compact(
            'ano',
            'responsavel',
            'responsaveis',
            'titulosAno',
            'quitadoIntegralmente'
        ));
    }
}
