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
        try {
            /** @var \iEducar\Financeiro\Services\ContratoService $contratoService */
            $contratoService = app(\iEducar\Financeiro\Services\ContratoService::class);
            $gerados = $contratoService->regenerarTitulosFaltantes((int) $id);

            if ($gerados === 0) {
                return redirect()->back()->with('info', 'Todos os títulos já existem para este contrato.');
            }

            return redirect()->route('financeiro.contratos.show', $id)
                ->with('success', "Foram gerados com sucesso {$gerados} novo(s) título(s) para o contrato.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Visualização e Impressão de Carnê Escolar do Contrato (Somente Leitura).
     *
     * Invariante de Segurança (FIN-003):
     *  - Nunca cria cobranças ou parcelamentos externos durante a impressão.
     *  - Se já houver carnê gerado no Asaas, tenta obter o PDF ou URL pública.
     *  - Se não houver, renderiza a view HTML para impressão manual.
     */
    public function imprimirCarne($id)
    {
        $contrato = Contrato::with([
            'responsavel',
            'titulos.cobrancaGateway',
            'titulos.responsavel',
        ])->findOrFail($id);

        $titulos = $contrato->titulos()
            ->whereIn('status', ['aberto', 'parcial', 'vencido'])
            ->orderBy('data_vencimento')
            ->get();

        if ($titulos->isEmpty()) {
            $titulos = $contrato->titulos()->orderBy('data_vencimento')->get();
        }

        // Verifica se já existe um installment ativo no Asaas para este contrato
        $cobrancaExistente = \iEducar\Financeiro\Models\CobrancaGateway::where('contrato_id', $contrato->id)
            ->whereNotNull('installment_id')
            ->first();

        if ($cobrancaExistente && !empty($cobrancaExistente->installment_id)) {
            try {
                /** @var \iEducar\Financeiro\Gateways\GatewayManager $manager */
                $manager = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
                /** @var \iEducar\Financeiro\Gateways\Drivers\AsaasGateway $gateway */
                $gateway = $manager->driver('asaas');

                $pdfContent = $gateway->baixarCarnePdf($cobrancaExistente->installment_id);

                if (!empty($pdfContent)) {
                    $filename = 'carne-' . $contrato->numero_contrato . '.pdf';
                    return response($pdfContent, 200, [
                        'Content-Type'        => 'application/pdf',
                        'Content-Disposition' => "inline; filename=\"{$filename}\"",
                        'Cache-Control'       => 'no-store, no-cache',
                    ]);
                }

                if (!empty($cobrancaExistente->carne_url)) {
                    return redirect()->away($cobrancaExistente->carne_url);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Falha ao baixar PDF do carnê Asaas existente', [
                    'contrato_id' => $contrato->id,
                    'erro'        => $e->getMessage(),
                ]);
            }
        }

        // Renderização segura da view HTML para impressão (sem mutação externa)
        return view('financeiro::titulos.carne', compact('titulos', 'contrato'));
    }

    /**
     * Emissão explícita de Carnê Escolar no Asaas (Operação autenticada de escrita via POST).
     *
     * POST /financeiro/contratos/{id}/emitir-carne-asaas
     */
    public function emitirCarneAsaas(Request $request, $id)
    {
        $contrato = Contrato::with([
            'responsavel',
            'titulos.cobrancaGateway',
            'titulos.responsavel',
        ])->findOrFail($id);

        $titulos = $contrato->titulos()
            ->whereIn('status', ['aberto', 'parcial', 'vencido'])
            ->orderBy('data_vencimento')
            ->get();

        if ($titulos->isEmpty()) {
            return redirect()->back()->with('error', 'Não há títulos em aberto para emitir carnê.');
        }

        // Idempotência: se já existe installment_id, reutiliza sem duplicar
        $cobrancaExistente = \iEducar\Financeiro\Models\CobrancaGateway::where('contrato_id', $contrato->id)
            ->whereNotNull('installment_id')
            ->first();

        if ($cobrancaExistente) {
            return redirect()->route('financeiro.contratos.imprimir-carne', $contrato->id)
                ->with('info', 'Carnê já emitido anteriormente para este contrato.');
        }

        try {
            /** @var \iEducar\Financeiro\Gateways\GatewayManager $manager */
            $manager = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
            /** @var \iEducar\Financeiro\Gateways\Drivers\AsaasGateway $gateway */
            $gateway = $manager->driver('asaas');

            $resultado = $gateway->criarInstallment($contrato, $titulos->all());
            $installmentId = $resultado['installment_id'];

            \iEducar\Financeiro\Models\CobrancaGateway::create([
                'contrato_id'      => $contrato->id,
                'titulo_id'        => $titulos->first()->id,
                'gateway'          => 'asaas',
                'installment_id'   => $installmentId,
                'carne_url'        => $resultado['carne_url'] ?? null,
                'status_externo'   => 'PENDING',
                'payload_resposta' => $resultado['payload_resposta'] ?? [],
            ]);

            return redirect()->route('financeiro.contratos.imprimir-carne', $contrato->id)
                ->with('success', 'Carnê gerado com sucesso no Asaas!');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao emitir carnê no Asaas', [
                'contrato_id' => $contrato->id,
                'erro'        => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Falha ao emitir carnê no Asaas: ' . $e->getMessage());
        }
    }

    /**
     * Redireciona para visualização do carnê externo Asaas quando disponível.
     */
    public function carneAsaas($id)
    {
        $contrato = Contrato::with('cobrancasGateway')->findOrFail($id);

        $cobrancaCarne = $contrato->cobrancasGateway
            ->where('gateway', 'asaas')
            ->whereNotNull('installment_id')
            ->sortByDesc('id')
            ->first();

        if ($cobrancaCarne && !empty($cobrancaCarne->carne_url)) {
            return redirect()->away($cobrancaCarne->carne_url);
        }

        return redirect()->route('financeiro.contratos.imprimir-carne', $contrato->id)
            ->with('info', 'Carnê Asaas não possui link externo direto. Redirecionado para impressão local.');
    }
}
