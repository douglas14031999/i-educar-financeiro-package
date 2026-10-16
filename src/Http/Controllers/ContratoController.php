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

        \iEducar\Financeiro\Support\AlunoLookup::nomes($contratos->pluck('aluno_id')->toArray());

        return view('financeiro::contratos.index', compact('contratos', 'anoLetivo', 'status'));
    }

    /**
     * Formulário de Criação de Contrato
     */
    public function create(Request $request)
    {
        $anoAtual = date('Y');
        // Carrega os planos ativos do ano letivo com escola_id e curso_id para filtragem contextual
        $planos = PlanoPagamento::where('ano_letivo', $anoAtual)
            ->where('ativo', true)
            ->orderBy('escola_id')
            ->orderBy('nome')
            ->get();
        $responsaveis = ResponsavelFinanceiro::where('ativo', true)->orderBy('nome')->limit(100)->get();

        $preResponsavelId = $request->input('responsavel_id') ? (int) $request->input('responsavel_id') : null;
        $preAlunoId = $request->input('aluno_id') ? (int) $request->input('aluno_id') : null;
        $preMatriculaId = $request->input('matricula_id') ? (int) $request->input('matricula_id') : null;

        if ($preResponsavelId && !$preAlunoId) {
            $vinculo = \iEducar\Financeiro\Models\AlunoResponsavel::where('responsavel_financeiro_id', $preResponsavelId)->first();
            if ($vinculo) {
                $preAlunoId = $vinculo->aluno_id;
            }
        }

        return view('financeiro::contratos.create', compact('planos', 'responsaveis', 'anoAtual', 'preResponsavelId', 'preAlunoId', 'preMatriculaId'));
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
            'dia_vencimento' => 'nullable|integer|min:1|max:31',
            'qtd_parcelas' => 'nullable|integer|min:1|max:24',
            'valor_total_descontos' => 'nullable|numeric|min:0',
            'forma_pagamento' => 'nullable|string|in:dinheiro,cartao_presencial,cartao_credito,pix,boleto,carne',
            'aplicar_rateio' => 'nullable|boolean',
            'rateio_responsaveis' => 'nullable|array',
            'rateio_responsaveis.*.responsavel_id' => 'required_with:aplicar_rateio|integer',
            'rateio_responsaveis.*.percentual' => 'required_with:aplicar_rateio|numeric|min:0.01|max:99.99',
            'rateio_responsaveis.*.forma_pagamento' => 'required_with:aplicar_rateio|string|in:dinheiro,cartao_presencial,cartao_credito,pix,boleto,carne',
            'rateio_responsaveis.*.dia_vencimento' => 'nullable|integer|min:1|max:31',
            'cartao_bandeira' => 'nullable|string|max:50',
            'cartao_nsu' => 'nullable|string|max:100',
            'cartao_tipo' => 'nullable|string|max:20',
            'cartao_parcelas' => 'nullable|integer|min:1|max:12',
        ]);

        $aplicarRateio = $request->boolean('aplicar_rateio', false);
        $validated['aplicar_rateio'] = $aplicarRateio;
        if ($aplicarRateio && $request->filled('rateio_responsaveis')) {
            $validated['rateio_responsaveis'] = $request->input('rateio_responsaveis');
        }

        if ($request->filled('forma_pagamento')) {
            $validated['forma_pagamento'] = strtolower(trim((string) $request->input('forma_pagamento')));
        }

        $forma = $validated['forma_pagamento'] ?? '';
        if ($request->filled('qtd_parcelas')) {
            $validated['qtd_parcelas'] = (int) $request->input('qtd_parcelas');
        } else {
            $validated['qtd_parcelas'] = in_array($forma, ['pix', 'boleto', 'dinheiro']) ? 1 : 12;
        }

        if (empty($validated['dia_vencimento'])) {
            if ($forma === 'boleto') {
                $validated['dia_vencimento'] = (int) \Carbon\Carbon::today()->addDays(2)->day;
            } else {
                $validated['dia_vencimento'] = (int) \Carbon\Carbon::today()->day;
            }
        }

        try {
            $contrato = $this->contratoService->criarContratoEParcelas($validated);

            if (!empty($validated['forma_pagamento'])) {
                return redirect()->route('financeiro.contratos.checkout', $contrato->id)
                    ->with('success', 'Contrato financeiro gerado com sucesso. Confira o fechamento do pagamento abaixo.');
            }

            return redirect()->route('financeiro.contratos.show', $contrato->id)
                ->with('success', 'Contrato financeiro e parcelas gerados com sucesso.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Tela de Fechamento / Checkout Escolar do Contrato
     */
    public function checkout($id)
    {
        $contrato = Contrato::with(['responsavel', 'plano', 'titulos.cobrancaGateway'])->findOrFail($id);
        $primeiroTitulo = $contrato->titulos->first();
        $cobrancaAtiva = $primeiroTitulo?->cobrancaGateway;

        return view('financeiro::contratos.checkout', compact('contrato', 'primeiroTitulo', 'cobrancaAtiva'));
    }

    /**
     * Endpoint JSON leve para checagem de status de pagamento do contrato (Polling/Realtime).
     */
    public function statusPagamento($id)
    {
        $contrato = Contrato::with(['titulos.cobrancasGateway'])->findOrFail($id);
        $primeiroTitulo = $contrato->titulos->first();
        $todosPagos = $contrato->titulos->isNotEmpty() && $contrato->titulos->every(fn ($t) => in_array($t->status, ['pago', 'liquidado']));
        $pago = ($contrato->status === 'quitado') || $todosPagos;

        return response()->json([
            'contrato_id'     => (int) $contrato->id,
            'status'          => $contrato->status,
            'pago'            => $pago,
            'forma_pagamento' => $contrato->forma_pagamento,
            'titulo_id'       => $primeiroTitulo?->id,
            'titulo_status'   => $primeiroTitulo?->status,
            'titulo_saldo'    => (float) ($primeiroTitulo?->status === 'pago' ? $primeiroTitulo?->valor_nominal : ($primeiroTitulo?->valor_saldo ?? 0)),
            'quitado_em'      => $contrato->quitado_em?->toIso8601String(),
        ]);
    }

    public function show($id)
    {
        $contrato = Contrato::with(['responsavel', 'plano', 'titulos.cobrancaGateway'])->findOrFail($id);
        $temTitulosVencidos = $contrato->titulos->contains(fn($t) => $t->isVencido());

        return view('financeiro::contratos.show', compact('contrato', 'temTitulosVencidos'));
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

        // Se o contrato possuir modalidade diferente de 'carne', redireciona para as opções adequadas
        if (!empty($contrato->forma_pagamento) && $contrato->forma_pagamento !== 'carne') {
            return redirect()->route('financeiro.contratos.checkout', $contrato->id)
                ->with('aviso', 'Este contrato possui modalidade ' . ucfirst(str_replace('_', ' ', $contrato->forma_pagamento)) . ' e não possui emissão de Carnê.');
        }

        // 1. Verifica se já existe um carnê (installment) ativo no Asaas para este contrato
        $carne = \iEducar\Financeiro\Models\CarneGateway::where('contrato_id', $contrato->id)
            ->where('status', 'ativo')
            ->first();

        $cobrancaExistente = \iEducar\Financeiro\Models\CobrancaGateway::where('contrato_id', $contrato->id)
            ->whereNotNull('installment_id')
            ->first();

        $installmentId = $carne?->installment_id ?? $cobrancaExistente?->installment_id;

        // 2. Se ainda não há installment registrado no Asaas, gera sob demanda apenas se a modalidade for carnê
        if (empty($installmentId) && ($contrato->forma_pagamento === 'carne' || (empty($contrato->forma_pagamento) && count($titulos) >= 2))) {
            try {
                /** @var \iEducar\Financeiro\Services\CarneService $carneService */
                $carneService = app(\iEducar\Financeiro\Services\CarneService::class);
                $novoCarne = $carneService->gerar($contrato, $titulos->all(), auth()->id() ?? null);
                $installmentId = $novoCarne->installment_id;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Erro ao gerar carnê sob demanda no Asaas', [
                    'contrato_id' => $contrato->id,
                    'erro'        => $e->getMessage(),
                ]);
            }
        }

        // 3. Obtém o PDF oficial do carnê gerado pelo Asaas (todas as lâminas com QR Code Pix e Linha Digitável)
        if (!empty($installmentId)) {
            try {
                /** @var \iEducar\Financeiro\Gateways\GatewayManager $manager */
                $manager = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
                /** @var \iEducar\Financeiro\Gateways\Drivers\AsaasGateway $gateway */
                $gateway = $manager->driver('asaas');

                $pdfContent = $gateway->baixarCarnePdf($installmentId);

                if (!empty($pdfContent)) {
                    $filename = 'carne-asaas-' . $contrato->numero_contrato . '.pdf';
                    return response($pdfContent, 200, [
                        'Content-Type'        => 'application/pdf',
                        'Content-Disposition' => "inline; filename=\"{$filename}\"",
                        'Cache-Control'       => 'no-store, no-cache',
                    ]);
                }

                $carneUrl = $gateway->gerarCarneUrl($installmentId);
                if (!empty($carneUrl)) {
                    return redirect()->away($carneUrl);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Falha ao obter PDF do carnê Asaas', [
                    'contrato_id' => $contrato->id,
                    'erro'        => $e->getMessage(),
                ]);
            }
        }

        // 4. Fallback HTML apenas caso haja indisponibilidade temporária de rede no Asaas
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
            /** @var \iEducar\Financeiro\Services\CarneService $carneService */
            $carneService = app(\iEducar\Financeiro\Services\CarneService::class);
            $carne = $carneService->gerar($contrato, $titulos->all(), auth()->id());

            return redirect()->route('financeiro.contratos.imprimir-carne', $contrato->id)
                ->with('success', "Carnê oficial gerado com sucesso no Asaas com {$carne->qtd_parcelas} parcelas!");
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

    /**
     * Tela de acompanhamento de matrículas do i-Educar sem contrato financeiro gerado (BUG-014 / FEAT-09)
     */
    public function matriculasSemContrato(Request $request)
    {
        $anoLetivo = (int) $request->input('ano_letivo', date('Y'));

        $matriculasComContrato = Contrato::where('ano_letivo', $anoLetivo)
            ->whereNotIn('status', ['cancelado'])
            ->pluck('matricula_id')
            ->filter()
            ->unique()
            ->toArray();

        $pendencias = collect();

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('pmieducar.matricula')) {
                $query = \Illuminate\Support\Facades\DB::table('pmieducar.matricula')
                    ->where('matricula.ano', $anoLetivo)
                    ->where('matricula.ativo', 1);

                if (!empty($matriculasComContrato)) {
                    $query->whereNotIn('matricula.cod_matricula', $matriculasComContrato);
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('cadastro.pessoa') && \Illuminate\Support\Facades\Schema::hasTable('pmieducar.aluno')) {
                    $query->leftJoin('pmieducar.aluno', 'matricula.ref_cod_aluno', '=', 'aluno.cod_aluno')
                          ->leftJoin('cadastro.pessoa', 'aluno.ref_idpes', '=', 'pessoa.idpes')
                          ->select('matricula.cod_matricula as matricula_id', 'matricula.ref_cod_aluno as aluno_id', 'matricula.ano as ano_letivo', 'pessoa.nome as aluno_nome');
                } else {
                    $query->select('matricula.cod_matricula as matricula_id', 'matricula.ref_cod_aluno as aluno_id', 'matricula.ano as ano_letivo');
                }

                $pendencias = $query->limit(50)->get();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Erro ao consultar matrículas sem contrato: ' . $e->getMessage());
        }

        return view('financeiro::contratos.matriculas-sem-contrato', compact('pendencias', 'anoLetivo'));
    }
}
