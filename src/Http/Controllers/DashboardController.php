<?php

namespace iEducar\Financeiro\Http\Controllers;

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

        return view('financeiro::dashboard.index', compact('kpis', 'titulosRecentes'));
    }
}
