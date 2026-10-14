<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\BaixaTituloService;
use iEducar\Financeiro\Services\DescontoService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class BaixaManualController extends Controller
{
    /**
     * @var BaixaTituloService
     */
    protected $baixaService;

    /**
     * @var DescontoService
     */
    protected $descontoService;

    public function __construct(BaixaTituloService $baixaService, DescontoService $descontoService)
    {
        $this->baixaService = $baixaService;
        $this->descontoService = $descontoService;
    }

    /**
     * Tela de Baixa no Balcão (Caixa / Secretaria)
     */
    public function create(Request $request, $titulo_id = null)
    {
        $tituloId = $titulo_id ?? $request->input('titulo_id');
        $titulo = null;

        if ($tituloId) {
            $titulo = Titulo::with(['contrato', 'responsavel'])->findOrFail($tituloId);
        }

        // Títulos recentes em aberto para seleção rápida
        $titulosAbertos = Titulo::with('responsavel')
            ->whereIn('status', ['aberto', 'parcial', 'vencido'])
            ->orderBy('data_vencimento')
            ->limit(50)
            ->get();

        return view('financeiro::baixas.create', compact('titulo', 'titulosAbertos'));
    }

    /**
     * Efetiva a baixa manual
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo_id' => 'required|integer',
            'valor_pago' => 'required|numeric|min:0.01',
            'forma_pagamento' => 'required|string|max:40',
            'data_pagamento' => 'required|date',
            'valor_desconto_concedido' => 'nullable|numeric|min:0',
            'valor_juros' => 'nullable|numeric|min:0',
            'valor_multa' => 'nullable|numeric|min:0',
        ]);

        $titulo = Titulo::findOrFail($validated['titulo_id']);

        // A28 — Usuário autenticado obrigatório; sem fallback para ID 1
        if (!Auth::check()) {
            return back()->with('error', 'Sessão expirada. Faça login novamente para registrar pagamentos.');
        }
        $validated['usuario_id'] = Auth::id();
        $validated['origem_baixa'] = 'manual';

        try {
            $pagamento = $this->baixaService->realizarBaixa($titulo, $validated);

            return redirect()->route('financeiro.titulos.recibo-pdf', [
                'id' => $titulo->id,
                'pagamento_id' => $pagamento->id,
            ])->with('success', 'Baixa registrada com sucesso! Recibo gerado.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Estorna um pagamento
     */
    public function estornar(Request $request, $pagamento_id)
    {
        $request->validate([
            'motivo_estorno' => 'required|string|max:500',
        ]);

        $pagamento = Pagamento::with('titulo')->findOrFail($pagamento_id);

        try {
            $this->baixaService->estornarBaixa(
                $pagamento,
                $request->input('motivo_estorno'),
                Auth::id() // A28 — null é aceito e registrado em estornado_por_usuario_id
            );

            return back()->with('success', 'Pagamento estornado e saldo restaurado com sucesso.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
