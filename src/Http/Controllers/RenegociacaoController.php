<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Models\Renegociacao;
use iEducar\Financeiro\Models\ResponsavelFinanceiro;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\RenegociacaoService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RenegociacaoController extends Controller
{
    /**
     * Tela inicial de acordos e seleção de títulos
     */
    public function index(Request $request)
    {
        $responsavelId = $request->input('responsavel_id');
        $responsaveis = ResponsavelFinanceiro::orderBy('nome')->get();
        $titulosPendentes = collect();
        $acordosRecentes = Renegociacao::with(['responsavel'])->orderBy('id', 'desc')->limit(10)->get();

        if ($responsavelId) {
            $titulosPendentes = Titulo::where('responsavel_financeiro_id', $responsavelId)
                ->whereIn('status', ['aberto', 'parcial', 'vencido'])
                ->orderBy('data_vencimento')
                ->get();
        }

        return view('financeiro::renegociacao.index', compact('responsaveis', 'responsavelId', 'titulosPendentes', 'acordosRecentes'));
    }

    /**
     * Simulação de condições de acordo
     */
    public function simular(Request $request, RenegociacaoService $service)
    {
        $request->validate([
            'titulos_ids' => 'required|array|min:1',
            'desconto_valor' => 'nullable|numeric|min:0',
            'qtd_parcelas' => 'required|integer|min:1|max:24',
            'primeiro_vencimento' => 'required|date',
        ]);

        try {
            $simulacao = $service->simular(
                $request->input('titulos_ids'),
                (float) $request->input('desconto_valor', 0.0),
                (int) $request->input('qtd_parcelas', 1),
                $request->input('primeiro_vencimento')
            );

            return view('financeiro::renegociacao.simulacao', compact('simulacao'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erro ao simular acordo: ' . $e->getMessage());
        }
    }

    /**
     * Confirmação e geração do acordo de renegociação
     */
    public function confirmar(Request $request, RenegociacaoService $service)
    {
        $request->validate([
            'titulos_ids' => 'required|array|min:1',
            'desconto_valor' => 'nullable|numeric|min:0',
            'qtd_parcelas' => 'required|integer|min:1|max:24',
            'primeiro_vencimento' => 'required|date',
        ]);

        try {
            $renegociacao = $service->confirmar(
                $request->input('titulos_ids'),
                (float) $request->input('desconto_valor', 0.0),
                (int) $request->input('qtd_parcelas', 1),
                $request->input('primeiro_vencimento'),
                auth()->id() ?? 1
            );

            return redirect()->route('financeiro.renegociacao.termo-pdf', $renegociacao->id)
                ->with('success', "Acordo {$renegociacao->numero_acordo} firmado com sucesso! Imprima o termo de confissão de dívida.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Falha ao confirmar acordo: ' . $e->getMessage());
        }
    }

    /**
     * Emissão e Impressão do Termo de Confissão de Dívida
     */
    public function imprimirTermo($id)
    {
        $acordo = Renegociacao::with(['responsavel', 'titulosOriginais', 'novasParcelas'])->findOrFail($id);

        return view('financeiro::renegociacao.termo', compact('acordo'));
    }
}
