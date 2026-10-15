<?php

namespace iEducar\Financeiro\Http\Controllers;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\BaixaTituloService;
use iEducar\Financeiro\Services\DescontoService;
use iEducar\Financeiro\Support\CalculoEncargos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $calculo = null;
        $descontoPontualidade = 0.00;
        $totalAReceber = 0.00;
        $creditoDisponivel = 0.00;

        if ($tituloId) {
            $titulo = Titulo::with(['contrato', 'responsavel'])->findOrFail($tituloId);
            $calculo = CalculoEncargos::calcularTotais($titulo);
            $descontoPontualidade = $this->descontoService->calcularDescontoPontualidade($titulo, Carbon::today());
            $totalAReceber = round(max(0, $calculo['total_devido'] - $descontoPontualidade), 2);

            if ($titulo->responsavel_financeiro_id) {
                $creditoDisponivel = (float) \iEducar\Financeiro\Models\CreditoResponsavel::where('responsavel_financeiro_id', $titulo->responsavel_financeiro_id)
                    ->where('utilizado', false)
                    ->where('cancelado', false)
                    ->sum('valor');
            }
        }

        // Títulos recentes para seleção inicial
        $titulosAbertos = Titulo::with('responsavel')
            ->whereIn('status', ['aberto', 'parcial', 'vencido'])
            ->where('valor_saldo', '>=', 0.005)
            ->orderBy('data_vencimento', 'asc')
            ->limit(50)
            ->get();

        return view('financeiro::baixas.create', compact(
            'titulo',
            'titulosAbertos',
            'calculo',
            'descontoPontualidade',
            'totalAReceber',
            'creditoDisponivel'
        ));
    }

    /**
     * Efetiva a baixa manual no balcão
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo_id'                => 'required|integer',
            'valor_pago'               => 'required|numeric|min:0.01',
            'forma_pagamento'          => ['required', 'string', \Illuminate\Validation\Rule::in(['dinheiro', 'pix', 'cartao_debito', 'cartao_credito', 'transferencia', 'cheque'])],
            'data_pagamento'           => 'required|date|before_or_equal:today',
            'valor_desconto_concedido' => 'nullable|numeric|min:0',
            'valor_juros'              => 'nullable|numeric|min:0',
            'valor_multa'              => 'nullable|numeric|min:0',
            'devolver_troco'           => 'nullable|boolean',
            'idempotency_key'          => 'nullable|string|max:64',
            'observacoes'              => 'nullable|string|max:500',
        ]);

        $titulo = Titulo::findOrFail($validated['titulo_id']);

        $usuarioId = Auth::id() ?? (auth()->id() ?? 1);
        $validated['usuario_id'] = $usuarioId;
        $validated['origem_baixa'] = 'manual';

        if (empty($validated['idempotency_key'])) {
            $validated['idempotency_key'] = (string) Str::uuid();
        }

        try {
            $pagamento = $this->baixaService->realizarBaixa($titulo, $validated);

            return redirect()->route('financeiro.titulos.recibo-pdf', [
                'id'           => $titulo->id,
                'pagamento_id' => $pagamento->id,
            ])->with('success', 'Baixa registrada com sucesso! Recibo de pagamento emitido.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Estorna um pagamento
     */
    public function estornar(Request $request, $pagamento_id)
    {
        $motivo = $request->input('motivo_estorno') ?? $request->input('motivo');
        if (empty($motivo)) {
            $request->validate([
                'motivo_estorno' => 'required|string|max:500',
            ]);
        }

        $pagamento = Pagamento::with('titulo')->findOrFail($pagamento_id);
        $usuarioId = Auth::id() ?? (auth()->id() ?? 1);

        try {
            $this->baixaService->estornarBaixa($pagamento, (string) $motivo, $usuarioId);

            return back()->with('success', 'Pagamento estornado com sucesso! Saldo do título restaurado.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Endpoint API para busca incremental de títulos para o Caixa (FEAT-02)
     */
    public function apiBuscarTitulos(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (strlen($q) < 2) {
            $titulos = Titulo::with('responsavel')
                ->whereIn('status', ['aberto', 'parcial', 'vencido'])
                ->where('valor_saldo', '>=', 0.005)
                ->orderBy('data_vencimento', 'asc')
                ->limit(20)
                ->get();
        } else {
            $query = Titulo::with('responsavel')
                ->whereIn('status', ['aberto', 'parcial', 'vencido'])
                ->where('valor_saldo', '>=', 0.005);

            if (is_numeric($q)) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('id', (int) $q)
                        ->orWhere('contrato_id', (int) $q)
                        ->orWhere('aluno_id', (int) $q);
                });
            } else {
                $query->whereHas('responsavel', function ($sub) use ($q) {
                    $sub->where('nome', 'ilike', "%{$q}%")
                        ->orWhere('cpf_cnpj', 'like', "%{$q}%");
                });
            }

            $titulos = $query->orderBy('data_vencimento', 'asc')->limit(30)->get();
        }

        $dados = $titulos->map(function ($t) {
            $calc = CalculoEncargos::calcularTotais($t);
            $desconto = $this->descontoService->calcularDescontoPontualidade($t, Carbon::today());
            $totalAReceber = round(max(0, $calc['total_devido'] - $desconto), 2);

            return [
                'id'              => $t->id,
                'competencia'     => $t->competencia,
                'numero_parcela'  => $t->numero_parcela == 0 ? 'Matr.' : sprintf('%02d/%02d', $t->numero_parcela, $t->total_parcelas),
                'data_vencimento' => $t->data_vencimento ? Carbon::parse($t->data_vencimento)->format('d/m/Y') : '',
                'is_vencido'      => $t->isVencido(),
                'valor_nominal'   => (float) $t->valor_nominal,
                'valor_saldo'     => (float) $t->valor_saldo,
                'dias_atraso'     => $calc['dias_atraso'],
                'multa'           => $calc['multa'],
                'juros'           => $calc['juros'],
                'desconto'        => $desconto,
                'total_receber'   => $totalAReceber,
                'responsavel'     => $t->responsavel?->nome ?? 'Não informado',
                'cpf'             => $t->responsavel?->cpf_cnpj_formatado ?? '',
                'aluno_id'        => $t->aluno_id,
                'observacoes'     => $t->observacoes ?? '',
                'url_selecionar'  => route('financeiro.baixas.create', ['titulo_id' => $t->id]),
            ];
        });

        return response()->json(['data' => $dados]);
    }

    /**
     * Endpoint API para recálculo dinâmico em tempo real de encargos (FEAT-02)
     */
    public function apiCalcular(Request $request, $id): JsonResponse
    {
        $titulo = Titulo::with('responsavel')->findOrFail($id);
        $dataPagamento = $request->input('data', Carbon::today()->toDateString());

        $calc = CalculoEncargos::calcularTotais($titulo, $dataPagamento);
        $descontoPontualidade = $this->descontoService->calcularDescontoPontualidade($titulo, Carbon::parse($dataPagamento));

        $totalReceber = round(max(0, $calc['total_devido'] - $descontoPontualidade), 2);

        return response()->json([
            'titulo_id'             => $titulo->id,
            'saldo'                 => $calc['saldo'],
            'dias_atraso'           => $calc['dias_atraso'],
            'multa'                 => $calc['multa'],
            'juros'                 => $calc['juros'],
            'desconto_pontualidade' => $descontoPontualidade,
            'total_receber'         => $totalReceber,
        ]);
    }
}
