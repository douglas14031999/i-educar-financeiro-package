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
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $kpis = [
            'recebido_mes' => 0.00,
            'a_receber_mes' => 0.00,
            'total_inadimplente' => 0.00,
            'titulos_vencidos_count' => 0,
            'taxa_inadimplencia' => 0.0,
        ];

        $titulosRecentes = [];

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
                ->sum('valor_saldo');
        } catch (\Throwable $e) {
            \Log::warning('Dashboard: Erro ao calcular a_receber_mes: ' . $e->getMessage());
        }

        // 3. Inadimplência total (títulos vencidos acumulados)
        try {
            $titulosVencidos = Titulo::vencidos()->get();
            $kpis['total_inadimplente'] = (float) $titulosVencidos->sum('valor_saldo');
            $kpis['titulos_vencidos_count'] = (int) $titulosVencidos->count();
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

        // 5. Títulos recentes com movimentação operacional
        try {
            $titulosRecentes = Titulo::with(['responsavel'])
                ->orderBy('data_vencimento', 'desc')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            \Log::warning('Dashboard: Erro ao buscar titulosRecentes: ' . $e->getMessage());
        }

        return view('financeiro::dashboard.index', compact('kpis', 'titulosRecentes'));
    }
}

