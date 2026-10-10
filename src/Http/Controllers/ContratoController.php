<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\PlanoPagamento;
use iEducar\Financeiro\Models\ResponsavelFinanceiro;
use iEducar\Financeiro\Services\ContratoService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ContratoController extends Controller
{
    /**
     * @var ContratoService
     */
    protected $contratoService;

    public function __construct(ContratoService $contratoService)
    {
        $this->contratoService = $contratoService;
    }

    /**
     * Listagem de Contratos
     */
    public function index(Request $request)
    {
        $anoLetivo = $request->input('ano_letivo', date('Y'));
        $status = $request->input('status');

        $query = Contrato::with(['responsavel', 'plano']);

        if ($anoLetivo) {
            $query->where('ano_letivo', $anoLetivo);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $contratos = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('financeiro::contratos.index', compact('contratos', 'anoLetivo', 'status'));
    }

    /**
     * Formulário de Criação de Contrato
     */
    public function create()
    {
        $anoAtual = date('Y');
        $planos = PlanoPagamento::where('ano_letivo', $anoAtual)->where('ativo', true)->get();
        $responsaveis = ResponsavelFinanceiro::where('ativo', true)->orderBy('nome')->limit(100)->get();

        return view('financeiro::contratos.create', compact('planos', 'responsaveis', 'anoAtual'));
    }

    /**
     * Gravação e Geração dos Títulos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'aluno_id' => 'required|integer',
            'matricula_id' => 'required|integer',
            'ano_letivo' => 'required|integer',
            'plano_id' => 'required|integer',
            'responsavel_financeiro_id' => 'nullable|integer',
            'dia_vencimento' => 'required|integer|min:1|max:31',
            'valor_total_descontos' => 'nullable|numeric|min:0',
        ]);

        try {
            $contrato = $this->contratoService->criarContratoEParcelas($validated);

            return redirect()->route('financeiro.contratos.show', $contrato->id)
                ->with('success', 'Contrato financeiro e parcelas gerados com sucesso.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Exibição do Contrato e Grade de Parcelas
     */
    public function show($id)
    {
        $contrato = Contrato::with(['responsavel', 'plano', 'titulos.cobrancaGateway'])->findOrFail($id);

        return view('financeiro::contratos.show', compact('contrato'));
    }

    /**
     * Cancelamento de Contrato
     */
    public function cancelar(Request $request, $id)
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        $contrato = Contrato::findOrFail($id);

        $this->contratoService->cancelarContrato($contrato, $request->input('motivo'));

        return back()->with('success', 'Contrato financeiro cancelado e parcelas futuras suspensas com sucesso.');
    }
}
