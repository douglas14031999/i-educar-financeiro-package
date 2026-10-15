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
use iEducar\Financeiro\Support\AlunoLookup;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

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
     * Painel de Gestão de Inadimplência e Aging List (TASK-8.4 / BUG-028)
     */
    public function inadimplencia(Request $request, InadimplenciaCheckService $service)
    {
        $escolaId = $request->input('escola_id');
        $alunoId = $request->input('aluno_id');
        $ano = $request->input('ano');

        $aging = $service->obterRelatorioAging($escolaId ? (int) $escolaId : null, $ano ? (int) $ano : null);

        $resultadoAluno = null;
        if ($alunoId) {
            $resultadoAluno = $service->verificarAluno((int) $alunoId, $escolaId ? (int) $escolaId : null);
        }

        if ($request->input('exportar') === 'csv') {
            return $this->exportarInadimplenciaCsv($aging, $ano);
        }

        return view('financeiro::relatorios.inadimplencia', compact('aging', 'resultadoAluno', 'alunoId', 'escolaId', 'ano'));
    }

    /**
     * Relatório de Fluxo de Caixa (Realizado vs Previsto)
     */
    public function fluxoCaixa(Request $request)
    {
        $ano = (int) $request->input('ano', date('Y'));
        $escolaId = $request->input('escola_id');

        $receitasRealizadas = [];
        $receitasPrevistas = [];
        $despesasRealizadas = [];
        $despesasPrevistas = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $inicioMes = Carbon::create($ano, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fimMes = Carbon::create($ano, $mes, 1)->endOfMonth()->format('Y-m-d');

            // 1. Receitas Realizadas (liquidadas em caixa no mês)
            $queryRec = Pagamento::where('estornado', false)
                ->whereBetween('data_pagamento', [$inicioMes, $fimMes]);
            if ($escolaId) {
                $queryRec->whereHas('titulo', fn($q) => $q->where('escola_id', $escolaId));
            }
            $receitasRealizadas[$mes] = (float) $queryRec->sum('valor_pago');

            // 2. Receitas Previstas (títulos faturados com vencimento no mês)
            $queryPrevRec = Titulo::where('status', '!=', 'cancelado')
                ->whereBetween('data_vencimento', [$inicioMes, $fimMes]);
            if ($escolaId) {
                $queryPrevRec->where('escola_id', $escolaId);
            }
            $receitasPrevistas[$mes] = (float) $queryPrevRec->sum(DB::raw('COALESCE(valor_nominal, 0) - COALESCE(valor_desconto, 0)'));

            // 3. Despesas Realizadas (pagas em caixa no mês)
            $queryDesp = Lancamento::where('tipo', 'despesa')
                ->where('status', 'pago')
                ->whereBetween('data_pagamento', [$inicioMes, $fimMes]);
            if ($escolaId) {
                $queryDesp->where('escola_id', $escolaId);
            }
            $despesasRealizadas[$mes] = (float) $queryDesp->sum('valor');

            // 4. Despesas Previstas (com vencimento no mês)
            $queryPrevDesp = Lancamento::where('tipo', 'despesa')
                ->whereNotIn('status', ['cancelado'])
                ->whereBetween('data_vencimento', [$inicioMes, $fimMes]);
            if ($escolaId) {
                $queryPrevDesp->where('escola_id', $escolaId);
            }
            $despesasPrevistas[$mes] = (float) $queryPrevDesp->sum('valor');
        }

        $totalReceitasAno = array_sum($receitasRealizadas);
        $totalDespesasAno = array_sum($despesasRealizadas);
        $saldoOperacionalAno = round($totalReceitasAno - $totalDespesasAno, 2);

        $totalReceitasPrevistas = array_sum($receitasPrevistas);
        $totalDespesasPrevistas = array_sum($despesasPrevistas);
        $saldoPrevistoAno = round($totalReceitasPrevistas - $totalDespesasPrevistas, 2);

        $receitasPorMes = $receitasRealizadas;
        $despesasPorMes = $despesasRealizadas;

        if ($request->input('exportar') === 'csv') {
            return $this->exportarFluxoCaixaCsv($ano, $receitasRealizadas, $receitasPrevistas, $despesasRealizadas, $despesasPrevistas);
        }

        return view('financeiro::relatorios.fluxo-caixa', compact(
            'ano',
            'receitasPorMes',
            'despesasPorMes',
            'totalReceitasAno',
            'totalDespesasAno',
            'saldoOperacionalAno',
            'receitasPrevistas',
            'despesasPrevistas',
            'totalReceitasPrevistas',
            'totalDespesasPrevistas',
            'saldoPrevistoAno'
        ));
    }

    /**
     * DRE - Demonstração do Resultado do Exercício Escolar (BUG-021)
     */
    public function dre(Request $request)
    {
        $ano = (int) $request->input('ano', date('Y'));
        $regime = $request->input('regime', 'caixa'); // 'caixa' ou 'competencia'
        $escolaId = $request->input('escola_id');

        $dataInicio = "{$ano}-01-01";
        $dataFim = "{$ano}-12-31";

        if ($regime === 'competencia') {
            // Regime de Competência (por data de faturamento/vencimento)
            $queryTitulos = Titulo::where('status', '!=', 'cancelado')
                ->whereBetween('data_vencimento', [$dataInicio, $dataFim]);
            if ($escolaId) {
                $queryTitulos->where('escola_id', $escolaId);
            }

            $receitaBruta = (float) $queryTitulos->sum('valor_nominal');
            $deducoes = (float) $queryTitulos->sum('valor_desconto');
            $receitaLiquida = round($receitaBruta - $deducoes, 2);
            $totalJurosMulta = 0.00; // Na competência faturada não incide juros prévios

            $queryDespesas = Lancamento::where('tipo', 'despesa')
                ->whereNotIn('status', ['cancelado'])
                ->whereBetween('data_vencimento', [$dataInicio, $dataFim]);
            if ($escolaId) {
                $queryDespesas->where('escola_id', $escolaId);
            }

            $despesasPorCategoria = $queryDespesas->with('planoConta')
                ->selectRaw('plano_conta_id, sum(valor) as total')
                ->groupBy('plano_conta_id')
                ->get();
        } else {
            // Regime de Caixa (Efetivo recebimento e pagamento)
            // BUG-021: Receita Bruta = sum(valor_pago + valor_desconto_concedido)
            // Deduções = sum(valor_desconto_concedido)
            // Receita Líquida = sum(valor_pago)
            $queryPagamentos = Pagamento::where('estornado', false)
                ->whereBetween('data_pagamento', [$dataInicio, $dataFim]);
            if ($escolaId) {
                $queryPagamentos->whereHas('titulo', fn($q) => $q->where('escola_id', $escolaId));
            }

            $receitaBruta = (float) $queryPagamentos->sum(DB::raw('COALESCE(valor_pago, 0) + COALESCE(valor_desconto_concedido, 0)'));
            $deducoes = (float) $queryPagamentos->sum('valor_desconto_concedido');
            $receitaLiquida = round($receitaBruta - $deducoes, 2);

            $totalJurosMulta = (float) $queryPagamentos->sum(DB::raw('COALESCE(valor_juros, 0) + COALESCE(valor_multa, 0)'));

            $queryDespesas = Lancamento::where('tipo', 'despesa')
                ->where('status', 'pago')
                ->whereBetween('data_pagamento', [$dataInicio, $dataFim]);
            if ($escolaId) {
                $queryDespesas->where('escola_id', $escolaId);
            }

            $despesasPorCategoria = $queryDespesas->with('planoConta')
                ->selectRaw('plano_conta_id, sum(valor) as total')
                ->groupBy('plano_conta_id')
                ->get();
        }

        $totalDespesasOperacionais = (float) $despesasPorCategoria->sum('total');
        $resultadoLiquido = round($receitaLiquida - $totalDespesasOperacionais, 2);

        if ($request->input('exportar') === 'csv') {
            return $this->exportarDreCsv($ano, $regime, $receitaBruta, $deducoes, $receitaLiquida, $totalJurosMulta, $despesasPorCategoria, $totalDespesasOperacionais, $resultadoLiquido);
        }

        return view('financeiro::relatorios.dre', compact(
            'ano',
            'regime',
            'receitaBruta',
            'deducoes',
            'receitaLiquida',
            'totalJurosMulta',
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
        $anoBase = (int) $request->input('ano_base', date('Y') - 1);
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

            // Pre-carrega alunos via AlunoLookup
            $alunoIds = $pagamentos->pluck('titulo.aluno_id')->filter()->unique()->toArray();
            AlunoLookup::nomes($alunoIds);

            // Agrupa por aluno
            foreach ($pagamentos as $pag) {
                $alunoId = $pag->titulo->aluno_id;
                if (!isset($pagamentosAgrupados[$alunoId])) {
                    $pagamentosAgrupados[$alunoId] = [
                        'aluno_id' => $alunoId,
                        'aluno_nome' => AlunoLookup::nome($alunoId),
                        'total' => 0.0,
                        'itens' => [],
                    ];
                }
                $pagamentosAgrupados[$alunoId]['total'] += (float) $pag->valor_pago;
                $pagamentosAgrupados[$alunoId]['itens'][] = $pag;
                $totalDeclarado += (float) $pag->valor_pago;
            }

            if ($request->input('exportar') === 'csv') {
                return $this->exportarIrpfCsv($responsavel, $anoBase, $pagamentosAgrupados, $totalDeclarado);
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
        $ano = (int) $request->input('ano', date('Y') - 1);
        $responsavelId = $request->input('responsavel_id');

        $responsavel = null;
        $quitadoIntegralmente = false;
        $titulosAno = collect();
        $codigoAutenticidade = null;

        if ($responsavelId) {
            $responsavel = ResponsavelFinanceiro::findOrFail($responsavelId);

            $titulosAno = Titulo::where('responsavel_financeiro_id', $responsavelId)
                ->whereYear('data_vencimento', $ano)
                ->where('status', '!=', 'cancelado')
                ->orderBy('data_vencimento')
                ->get();

            AlunoLookup::carregarParaTitulos($titulosAno, $ano);

            // Lei 12.007/2009: Todos os títulos do ano devem estar quitados
            $pendencias = $titulosAno->filter(function ($t) {
                return !$t->isLiquidado();
            });

            $quitadoIntegralmente = $titulosAno->isNotEmpty() && $pendencias->isEmpty();

            if ($quitadoIntegralmente) {
                $codigoAutenticidade = hash('sha256', 'LEI12007' . $responsavel->id . $ano . config('app.key'));
            }

            if ($request->input('exportar') === 'csv') {
                return $this->exportarQuitacaoCsv($responsavel, $ano, $titulosAno, $quitadoIntegralmente);
            }
        }

        $responsaveis = ResponsavelFinanceiro::orderBy('nome')->get();

        return view('financeiro::relatorios.quitacao-anual', compact(
            'ano',
            'responsavel',
            'responsaveis',
            'titulosAno',
            'quitadoIntegralmente',
            'codigoAutenticidade'
        ));
    }

    /**
     * Rota de verificação pública de autenticidade documental (BUG-038)
     */
    public function verificarDocumento(string $hash)
    {
        $hashLimpo = trim($hash);

        // 1. Tenta localizar por pagamento (comprovante_hash)
        $pagamento = Pagamento::with(['titulo.responsavel'])
            ->where('comprovante_hash', $hashLimpo)
            ->first();

        if ($pagamento) {
            return response()->json([
                'valido' => true,
                'tipo' => 'recibo_pagamento',
                'id_pagamento' => $pagamento->id,
                'data_pagamento' => $pagamento->data_pagamento ? Carbon::parse($pagamento->data_pagamento)->format('d/m/Y') : null,
                'valor_pago' => (float) $pagamento->valor_pago,
                'forma_pagamento' => $pagamento->forma_pagamento,
                'responsavel' => $pagamento->titulo?->responsavel?->nome ?? '-',
                'competencia' => $pagamento->titulo?->competencia ?? '-',
                'estornado' => (bool) $pagamento->estornado,
            ]);
        }

        return response()->json([
            'valido' => false,
            'mensagem' => 'Documento não localizado ou código verificador inválido.',
        ], 404);
    }

    /**
     * Exportação de DRE em formato CSV
     */
    protected function exportarDreCsv(int $ano, string $regime, float $bruta, float $deducoes, float $liquida, float $jurosMulta, $despesas, float $totalDespesas, float $resultado)
    {
        $linhas = [
            ['DRE - Demonstração do Resultado do Exercício Escolar', 'Exercício ' . $ano, 'Regime: ' . strtoupper($regime)],
            [],
            ['Conta Contábil / Descrição', 'Valor (R$)'],
            ['(+) RECEITA BRUTA OPERACIONAL', number_format($bruta, 2, ',', '.')],
            ['(-) DEDUÇÕES DA RECEITA (Descontos e Bolsas)', number_format($deducoes, 2, ',', '.')],
            ['(=) RECEITA LÍQUIDA OPERACIONAL', number_format($liquida, 2, ',', '.')],
            ['(+) JUROS E MULTAS RECEBIDOS', number_format($jurosMulta, 2, ',', '.')],
            [],
            ['(-) DESPESAS OPERACIONAIS', ''],
        ];

        foreach ($despesas as $d) {
            $nomeCategoria = $d->planoConta?->nome ?? ('Categoria #' . $d->plano_conta_id);
            $linhas[] = ['   - ' . $nomeCategoria, number_format((float) $d->total, 2, ',', '.')];
        }

        $linhas[] = ['TOTAL DAS DESPESAS OPERACIONAIS', number_format($totalDespesas, 2, ',', '.')];
        $linhas[] = [];
        $linhas[] = ['(=) RESULTADO LÍQUIDO DO EXERCÍCIO (SUPERÁVIT / DÉFICIT)', number_format($resultado, 2, ',', '.')];

        return $this->downloadCsv("dre-{$ano}-{$regime}.csv", $linhas);
    }

    /**
     * Exportação de Fluxo de Caixa em CSV
     */
    protected function exportarFluxoCaixaCsv(int $ano, array $recReal, array $recPrev, array $despReal, array $despPrev)
    {
        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $linhas = [
            ['Mês', 'Receita Prevista (R$)', 'Receita Realizada (R$)', 'Despesa Prevista (R$)', 'Despesa Realizada (R$)', 'Saldo Operacional (R$)'],
        ];

        for ($m = 1; $m <= 12; $m++) {
            $saldoMes = round(($recReal[$m] ?? 0) - ($despReal[$m] ?? 0), 2);
            $linhas[] = [
                $meses[$m - 1],
                number_format($recPrev[$m] ?? 0, 2, ',', '.'),
                number_format($recReal[$m] ?? 0, 2, ',', '.'),
                number_format($despPrev[$m] ?? 0, 2, ',', '.'),
                number_format($despReal[$m] ?? 0, 2, ',', '.'),
                number_format($saldoMes, 2, ',', '.'),
            ];
        }

        $linhas[] = [
            'TOTAL CONSOLIDADO',
            number_format(array_sum($recPrev), 2, ',', '.'),
            number_format(array_sum($recReal), 2, ',', '.'),
            number_format(array_sum($despPrev), 2, ',', '.'),
            number_format(array_sum($despReal), 2, ',', '.'),
            number_format(round(array_sum($recReal) - array_sum($despReal), 2), 2, ',', '.'),
        ];

        return $this->downloadCsv("fluxo-caixa-{$ano}.csv", $linhas);
    }

    /**
     * Exportação de Inadimplência em CSV
     */
    protected function exportarInadimplenciaCsv(array $aging, ?string $ano = null)
    {
        $linhas = [
            ['Relatório de Inadimplência e Aging List', 'Total Geral: R$ ' . number_format($aging['total_geral'], 2, ',', '.')],
            [],
            ['Faixa de Atraso', 'Qtd Títulos', 'Total em Aberto (R$)'],
        ];

        foreach ($aging['faixas'] as $f) {
            $linhas[] = [
                $f['nome'],
                $f['qtd'],
                number_format($f['total'], 2, ',', '.'),
            ];
        }

        $linhas[] = [];
        $linhas[] = ['ID Título', 'Competência', 'Vencimento', 'Dias Atraso', 'Responsável', 'Aluno', 'Saldo Devedor (R$)'];

        foreach ($aging['faixas'] as $f) {
            foreach ($f['titulos'] as $t) {
                $linhas[] = [
                    $t->id,
                    $t->competencia,
                    $t->data_vencimento ? Carbon::parse($t->data_vencimento)->format('d/m/Y') : '-',
                    $t->dias_atraso,
                    $t->responsavel?->nome ?? '-',
                    $t->aluno_nome ?? '-',
                    number_format((float) $t->valor_saldo, 2, ',', '.'),
                ];
            }
        }

        return $this->downloadCsv('inadimplencia-' . ($ano ?: date('Y')) . '.csv', $linhas);
    }

    /**
     * Exportação de IRPF em CSV
     */
    protected function exportarIrpfCsv($responsavel, int $anoBase, array $pagamentosAgrupados, float $total)
    {
        $linhas = [
            ['Informe de Rendimentos / Pagamentos Escolares (IRPF)', 'Ano-Base: ' . $anoBase],
            ['Responsável Financeiro: ' . $responsavel->nome, 'CPF: ' . $responsavel->cpf_cnpj_formatado],
            [],
            ['Aluno', 'Qtd Pagamentos', 'Total Declarado (R$)'],
        ];

        foreach ($pagamentosAgrupados as $agrupado) {
            $linhas[] = [
                $agrupado['aluno_nome'] ?? ('Aluno #' . $agrupado['aluno_id']),
                count($agrupado['itens']),
                number_format($agrupado['total'], 2, ',', '.'),
            ];
        }

        $linhas[] = [];
        $linhas[] = ['TOTAL GERAL DECLARADO', '', number_format($total, 2, ',', '.')];

        return $this->downloadCsv("irpf-{$anoBase}-resp-{$responsavel->id}.csv", $linhas);
    }

    /**
     * Exportação de Quitação Anual em CSV
     */
    protected function exportarQuitacaoCsv($responsavel, int $ano, $titulos, bool $quitado)
    {
        $linhas = [
            ['Declaração de Quitação Anual de Débitos (Lei 12.007/09)', 'Exercício ' . $ano],
            ['Responsável: ' . $responsavel->nome, 'CPF: ' . $responsavel->cpf_cnpj_formatado],
            ['Status de Quitação: ' . ($quitado ? 'QUITADO INTEGRALMENTE' : 'COM DÉBITOS PENDENTES')],
            [],
            ['Título ID', 'Competência', 'Vencimento', 'Valor Nominal (R$)', 'Situação'],
        ];

        foreach ($titulos as $t) {
            $linhas[] = [
                $t->id,
                $t->competencia,
                $t->data_vencimento ? Carbon::parse($t->data_vencimento)->format('d/m/Y') : '-',
                number_format((float) $t->valor_nominal, 2, ',', '.'),
                $t->isLiquidado() ? 'Quitado' : 'Em Aberto',
            ];
        }

        return $this->downloadCsv("quitacao-{$ano}-resp-{$responsavel->id}.csv", $linhas);
    }

    /**
     * Helper para download de arquivo CSV com codificação UTF-8 e delimitador brasileiro
     */
    protected function downloadCsv(string $filename, array $rows)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            // Byte Order Mark (BOM) UTF-8 para compatibilidade perfeita com Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        }, 200, $headers);
    }
}
