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

        try {
            $inicioMes = Carbon::now()->startOfMonth();
            $fimMes = Carbon::now()->endOfMonth();

            // 1. Recebido no mês
            $recebidoMes = Pagamento::whereBetween('data_pagamento', [$inicioMes, $fimMes])
                ->where('status', 'confirmado')
                ->sum('valor_pago');

            // 2. A receber no mês
            $aReceberMes = Titulo::whereBetween('data_vencimento', [$inicioMes, $fimMes])
                ->whereIn('status', ['aberto', 'parcial'])
                ->sum('valor_saldo');

            // 3. Inadimplência total
            $titulosVencidos = Titulo::vencidos()->get();
            $totalInadimplente = $titulosVencidos->sum('valor_saldo');
            $titulosVencidosCount = $titulosVencidos->count();

            // 4. Taxa de inadimplência
            $totalFaturadoMes = Titulo::whereBetween('data_vencimento', [$inicioMes, $fimMes])->sum('valor_nominal');
            $taxaInadimplencia = $totalFaturadoMes > 0 ? min(100, round(($totalInadimplente / $totalFaturadoMes) * 100, 1)) : 0.0;

            $kpis = [
                'recebido_mes' => (float) $recebidoMes,
                'a_receber_mes' => (float) $aReceberMes,
                'total_inadimplente' => (float) $totalInadimplente,
                'titulos_vencidos_count' => (int) $titulosVencidosCount,
                'taxa_inadimplencia' => (float) $taxaInadimplencia,
            ];

            // 5. Títulos recentes
            $titulosRecentes = Titulo::with(['responsavel'])
                ->orderBy('data_vencimento', 'asc')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            // Em caso de tabela recém-migrada ou banco vazio, mantém defaults
        }

        return view('financeiro::dashboard.index', compact('kpis', 'titulosRecentes'));
    }
}

