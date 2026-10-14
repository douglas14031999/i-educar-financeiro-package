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
            'escola_id' => 'nullable|integer',
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

    /**
     * A27 — Gera (ou regenera) os títulos de um contrato existente.
     * Handler que estava roteado mas não existia, causando fatal error.
     *
     * Casos de uso:
     *  - Contrato criado sem plano (sem itens) que depois recebeu itens.
     *  - Reprocessamento após correção de dia de vencimento.
     *
     * Só gera títulos para os meses que ainda NÃO possuem título no contrato (idempotente).
     */
    public function gerarTitulos(Request $request, $id)
    {
        $contrato = Contrato::with(['plano.itens', 'titulos'])->findOrFail($id);

        if ($contrato->status === 'cancelado') {
            return redirect()->back()->with('error', 'Não é possível gerar títulos para um contrato cancelado.');
        }

        $parcelasExistentes = $contrato->titulos->pluck('numero_parcela')->unique()->toArray();
        $plano = $contrato->plano;

        if (!$plano || $plano->itens->isEmpty()) {
            return redirect()->back()->with('error', 'O plano de pagamento associado não possui itens configurados.');
        }

        $gerados = 0;

        foreach ($plano->itens as $item) {
            if (in_array($item->numero_parcela, $parcelasExistentes)) {
                continue; // Idempotente: pula parcelas já existentes
            }

            $anoLetivo  = $contrato->ano_letivo;
            $mesComp    = str_pad($item->mes_competencia, 2, '0', STR_PAD_LEFT);
            $ultimoDia  = \Carbon\Carbon::create($anoLetivo, $item->mes_competencia, 1)->endOfMonth()->day;
            $diaEfetivo = min($contrato->dia_vencimento, $ultimoDia);

            \iEducar\Financeiro\Models\Titulo::create([
                'contrato_id'               => $contrato->id,
                'aluno_id'                  => $contrato->aluno_id,
                'responsavel_financeiro_id' => $contrato->responsavel_financeiro_id,
                'escola_id'                 => $contrato->escola_id,
                'tipo'                      => $item->tipo_item,
                'numero_parcela'            => $item->numero_parcela,
                'total_parcelas'            => $plano->itens->where('numero_parcela', '>', 0)->count(),
                'competencia'               => "{$anoLetivo}-{$mesComp}",
                'data_vencimento'           => \Carbon\Carbon::create($anoLetivo, $item->mes_competencia, $diaEfetivo),
                'valor_nominal'             => (float) $item->valor,
                'valor_desconto'            => 0.00,
                'valor_multa'               => 0.00,
                'valor_juros'               => 0.00,
                'valor_pago'                => 0.00,
                'valor_saldo'               => (float) $item->valor,
                'status'                    => 'aberto',
                'observacoes'               => sprintf('%s - %s', $contrato->numero_contrato, $item->descricao),
            ]);

            $gerados++;
        }

        if ($gerados === 0) {
            return redirect()->back()->with('info', 'Todos os títulos já existem para este contrato.');
        }

        return redirect()->route('financeiro.contratos.show', $contrato->id)
            ->with('success', "{$gerados} título(s) gerado(s) com sucesso para o contrato {$contrato->numero_contrato}.");
    }

    /**
     * Emissão de Carnê Escolar do Contrato
     */
    public function imprimirCarne($id)
    {
        $contrato = Contrato::with(['titulos.responsavel', 'titulos.cobrancaGateway'])->findOrFail($id);
        $titulos = $contrato->titulos()->orderBy('data_vencimento')->get();

        return view('financeiro::titulos.carne', compact('titulos', 'contrato'));
    }
}
