<?php

namespace iEducar\Financeiro\Http\Controllers;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    /**
     * Exibe o painel geral de indicadores financeiros
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $kpis = [
            'recebido_mes' => 0.00,
            'a_receber_mes' => 0.00,
            'total_inadimplente' => 0.00,
            'titulos_vencidos_count' => 0,
            'taxa_inadimplencia' => 0.0,
        ];

        $counts = [
            'todos' => 0,
            'pendentes' => 0,
            'atrasados' => 0,
            'pagos' => 0,
        ];

        $titulosRecentes = collect();
        $filtro = $request->input('filtro', 'todos');

        $hoje = Carbon::today()->toDateString();
        $inicioMes = Carbon::now()->startOfMonth()->toDateString();
        $fimMes = Carbon::now()->endOfMonth()->toDateString();

        // 1. Recebido no mês (liquidações do mês não estornadas)
        try {
            $kpis['recebido_mes'] = (float) Pagamento::whereBetween('data_pagamento', [$inicioMes, $fimMes])
                ->where(function ($q) {
                    $q->where('estornado', false)->orWhereNull('estornado');
                })
                ->sum('valor_pago');
        } catch (\Throwable $e) {
            \Log::warning('Dashboard: Erro ao calcular recebido_mes: ' . $e->getMessage());
        }

        // 2. A receber no mês (parcelas a vencer ou em aberto com vencimento neste mês)
        try {
            $kpis['a_receber_mes'] = (float) Titulo::whereBetween('data_vencimento', [$inicioMes, $fimMes])
                ->whereIn('status', ['aberto', 'parcial'])
                ->where('valor_saldo', '>', 0.005)
                ->sum('valor_saldo');
        } catch (\Throwable $e) {
            \Log::warning('Dashboard: Erro ao calcular a_receber_mes: ' . $e->getMessage());
        }

        // 3. Inadimplência total (títulos vencidos acumulados com agregação direta no banco)
        try {
            $kpis['total_inadimplente'] = (float) Titulo::vencidos()->where('valor_saldo', '>', 0.005)->sum('valor_saldo');
            $kpis['titulos_vencidos_count'] = (int) Titulo::vencidos()->where('valor_saldo', '>', 0.005)->count();
        } catch (\Throwable $e) {
            \Log::warning('Dashboard: Erro ao calcular inadimplencia: ' . $e->getMessage());
        }

        // 4. Taxa de inadimplência (% do saldo vencido sobre o total faturado)
        try {
            $totalFaturado = Titulo::where('data_vencimento', '<=', $fimMes)
                ->where('status', '!=', 'cancelado')
                ->sum('valor_nominal');

            if ($totalFaturado > 0) {
                $kpis['taxa_inadimplencia'] = min(100.0, round(($kpis['total_inadimplente'] / $totalFaturado) * 100, 1));
            } else {
                $kpis['taxa_inadimplencia'] = 0.0;
            }
        } catch (\Throwable $e) {
            \Log::warning('Dashboard: Erro ao calcular taxa_inadimplencia: ' . $e->getMessage());
        }

        // 5. Contadores dinâmicos para as abas/botões de filtro rápido
        try {
            $counts['atrasados'] = (int) Titulo::vencidos()->where('valor_saldo', '>', 0.005)->count();
            $counts['pagos'] = (int) Titulo::where(function ($q) {
                $q->where('status', 'pago')->orWhere('valor_saldo', '<=', 0.005);
            })->whereNotIn('status', ['cancelado', 'renegociado'])->count();
            $counts['pendentes'] = (int) Titulo::whereIn('status', ['aberto', 'parcial'])
                ->where('data_vencimento', '>=', $hoje)
                ->where('valor_saldo', '>', 0.005)
                ->count();
            $counts['todos'] = (int) Titulo::whereNotIn('status', ['cancelado'])->count();
        } catch (\Throwable $e) {
            \Log::warning('Dashboard: Erro ao calcular contadores de filtros: ' . $e->getMessage());
        }

        // 6. Listagem ordenada e priorizada de cobranças operacionais (via SQL limits sem memory blowup)
        try {
            if ($filtro === 'atrasados') {
                $titulosRecentes = Titulo::with(['responsavel'])
                    ->vencidos()
                    ->where('valor_saldo', '>', 0.005)
                    ->orderBy('data_vencimento', 'asc')
                    ->limit(50)
                    ->get();
            } elseif ($filtro === 'pagos') {
                $titulosRecentes = Titulo::with(['responsavel'])
                    ->where(function ($q) {
                        $q->where('status', 'pago')->orWhere('valor_saldo', '<=', 0.005);
                    })
                    ->whereNotIn('status', ['cancelado', 'renegociado'])
                    ->orderBy('updated_at', 'desc')
                    ->orderBy('data_vencimento', 'desc')
                    ->limit(50)
                    ->get();
            } elseif ($filtro === 'pendentes') {
                $titulosRecentes = Titulo::with(['responsavel'])
                    ->whereIn('status', ['aberto', 'parcial'])
                    ->where('data_vencimento', '>=', $hoje)
                    ->where('valor_saldo', '>', 0.005)
                    ->orderBy('data_vencimento', 'asc')
                    ->limit(50)
                    ->get();
            } else {
                // Modo 'todos': Ordenação inteligente por prioridade operacional via SQL
                // 1º: Atrasados (vencidos em aberto)
                // 2º: A vencer (próximos vencimentos)
                // 3º: Liquidados / Pagos
                // 4º: Renegociados
                $titulosRecentes = Titulo::with(['responsavel'])
                    ->whereNotIn('status', ['cancelado'])
                    ->orderByRaw("
                        CASE 
                            WHEN status IN ('aberto', 'parcial') AND data_vencimento < '{$hoje}' AND valor_saldo > 0.005 THEN 1
                            WHEN status IN ('aberto', 'parcial') AND data_vencimento >= '{$hoje}' AND valor_saldo > 0.005 THEN 2
                            WHEN status = 'pago' OR valor_saldo <= 0.005 THEN 3
                            WHEN status = 'renegociado' THEN 4
                            ELSE 5
                        END ASC,
                        data_vencimento ASC
                    ")
                    ->limit(50)
                    ->get();
            }
        } catch (\Throwable $e) {
            \Log::warning('Dashboard: Erro ao buscar titulosRecentes: ' . $e->getMessage());
        }

        \iEducar\Financeiro\Support\AlunoLookup::carregarParaTitulos($titulosRecentes);

        return view('financeiro::dashboard.index', compact('kpis', 'titulosRecentes', 'counts', 'filtro'));
    }
}

