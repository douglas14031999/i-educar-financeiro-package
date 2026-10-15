<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Models\CentroCusto;
use iEducar\Financeiro\Models\ContaBancaria;
use iEducar\Financeiro\Models\Lancamento;
use iEducar\Financeiro\Models\PlanoConta;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ContaPagarController extends Controller
{
    /**
     * Listagem de Contas a Pagar / Despesas
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'a_pagar');
        $busca = $request->input('busca');

        $query = Lancamento::with(['planoConta', 'centroCusto', 'contaBancaria'])
            ->where('tipo', 'despesa');

        if ($status && $status !== 'todos') {
            $query->where('status', $status);
        }

        if ($busca) {
            $query->where(function ($q) use ($busca) {
                $q->where('descricao', 'ilike', "%{$busca}%")
                  ->orWhere('fornecedor_nome', 'ilike', "%{$busca}%");
            });
        }

        $despesas = $query->orderBy('data_vencimento')->paginate(20);
        $totalAberto = Lancamento::where('tipo', 'despesa')->where('status', 'a_pagar')->sum('valor');

        return view('financeiro::contas-pagar.index', compact('despesas', 'status', 'busca', 'totalAberto'));
    }

    /**
     * Formulário de nova conta a pagar
     */
    public function create()
    {
        $planosContas = PlanoConta::where('tipo', 'despesa')->orderBy('codigo')->get();
        $centrosCusto = CentroCusto::where('ativo', true)->orderBy('nome')->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();

        $escolas = $this->obterEscolasDisponiveis();

        return view('financeiro::contas-pagar.create', compact('planosContas', 'centrosCusto', 'contasBancarias', 'escolas'));
    }

    /**
     * Salva nova conta a pagar
     */
    public function store(Request $request)
    {
        $dados = $request->validate([
            'descricao' => 'required|string|max:255',
            'fornecedor_nome' => 'nullable|string|max:200',
            'valor' => 'required|numeric|min:0.01',
            'data_competencia' => 'required|date',
            'data_vencimento' => 'required|date',
            'escola_id' => 'nullable|integer',
            'plano_conta_id' => 'required|exists:plano_contas,id',
            'centro_custo_id' => 'nullable|exists:centros_custo,id',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'documento_fiscal' => 'nullable|string|max:100',
            'observacoes' => 'nullable|string',
        ]);

        $dados['tipo']     = 'despesa';
        $dados['status']   = 'a_pagar';
        $dados['escola_id'] = $request->input('escola_id') ? (int) $request->input('escola_id') : null;
        $dados['usuario_id'] = auth()->id() ?? 1;

        Lancamento::create($dados);

        return redirect()->route('financeiro.contas-pagar.index')
            ->with('success', 'Conta a pagar cadastrada com sucesso!');
    }

    /**
     * Edição de conta a pagar
     */
    public function edit($id)
    {
        $despesa = Lancamento::where('tipo', 'despesa')->findOrFail($id);
        $planosContas = PlanoConta::where('tipo', 'despesa')->orderBy('codigo')->get();
        $centrosCusto = CentroCusto::where('ativo', true)->orderBy('nome')->get();
        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();
        $escolas = $this->obterEscolasDisponiveis();

        return view('financeiro::contas-pagar.edit', compact('despesa', 'planosContas', 'centrosCusto', 'contasBancarias', 'escolas'));
    }

    /**
     * Auxiliar para consultar escolas ativas da rede
     */
    protected function obterEscolasDisponiveis()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('pmieducar.escola')) {
            return collect();
        }

        try {
            return DB::table('pmieducar.escola as e')
                ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'e.ref_idpes')
                ->where('e.ativo', 1)
                ->select('e.cod_escola as id', DB::raw("COALESCE(p.nome, 'Escola #' || e.cod_escola) as nome"))
                ->orderBy('nome')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Atualização
     */
    public function update(Request $request, $id)
    {
        $despesa = Lancamento::where('tipo', 'despesa')->findOrFail($id);

        // A24 / A25 — Despesas liquidadas são imutáveis (integridade contábil)
        if ($despesa->status === 'pago') {
            return redirect()->route('financeiro.contas-pagar.index')
                ->with('error', "A despesa '{$despesa->descricao}' já foi liquidada e não pode ser alterada.");
        }

        $dados = $request->validate([
            'descricao'        => 'required|string|max:255',
            'fornecedor_nome'  => 'nullable|string|max:200',
            'valor'            => 'required|numeric|min:0.01',
            'data_competencia' => 'required|date',
            'data_vencimento'  => 'required|date',
            'plano_conta_id'   => 'required|exists:plano_contas,id',
            'centro_custo_id'  => 'nullable|exists:centros_custo,id',
            'documento_fiscal' => 'nullable|string|max:100',
            'observacoes'      => 'nullable|string',
        ]);

        $despesa->update($dados);

        return redirect()->route('financeiro.contas-pagar.index')
            ->with('success', 'Conta a pagar atualizada com sucesso!');
    }

    /**
     * Visualização detalhada da conta a pagar (FEAT-10 / BUG-026)
     */
    public function show($id)
    {
        $despesa = Lancamento::with(['planoConta', 'centroCusto', 'contaBancaria'])
            ->where('tipo', 'despesa')
            ->findOrFail($id);

        $movimentacoes = \iEducar\Financeiro\Models\MovimentacaoConta::where(function ($q) use ($despesa) {
                $q->where('origem_tipo', 'lancamento')
                  ->orWhere('origem_tipo', 'estorno_lancamento');
            })
            ->where('origem_id', $despesa->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $contasBancarias = ContaBancaria::where('ativo', true)->orderBy('nome')->get();

        return view('financeiro::contas-pagar.show', compact('despesa', 'movimentacoes', 'contasBancarias'));
    }

    /**
     * Liquidação / Baixa da Conta a Pagar com atualização bancária (FIN-012 / FEAT-10)
     */
    public function liquidar(Request $request, $id)
    {
        $validated = $request->validate([
            'data_pagamento'    => 'required|date|before_or_equal:today',
            'conta_bancaria_id' => 'required|exists:contas_bancarias,id',
            'valor_pago'        => 'nullable|numeric|min:0.01',
            'valor_juros'       => 'nullable|numeric|min:0',
            'valor_multa'       => 'nullable|numeric|min:0',
            'valor_desconto'    => 'nullable|numeric|min:0',
            'forma_pagamento'   => 'nullable|string|in:dinheiro,pix,ted,transferencia,boleto,cartao_debito,cartao_credito,cheque',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $despesa = Lancamento::where('tipo', 'despesa')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($despesa->status === 'pago') {
                return redirect()->back()
                    ->with('error', "A despesa '{$despesa->descricao}' já foi liquidada anteriormente e não pode ser sobrescrita.");
            }

            if ($despesa->status === 'cancelado') {
                return redirect()->back()
                    ->with('error', "Não é possível liquidar uma despesa cancelada.");
            }

            $conta = ContaBancaria::lockForUpdate()->findOrFail($validated['conta_bancaria_id']);

            $valorNominal = (float) $despesa->valor;
            $juros = (float) ($validated['valor_juros'] ?? 0.00);
            $multa = (float) ($validated['valor_multa'] ?? 0.00);
            $desconto = (float) ($validated['valor_desconto'] ?? 0.00);

            $valorFinalPago = isset($validated['valor_pago']) && (float) $validated['valor_pago'] > 0
                ? (float) $validated['valor_pago']
                : round($valorNominal + $juros + $multa - $desconto, 2);

            $saldoAnterior = (float) $conta->saldo_atual;
            $saldoPosterior = round($saldoAnterior - $valorFinalPago, 2);

            // Atualiza saldo da conta bancária
            $conta->update(['saldo_atual' => $saldoPosterior]);

            // Cria movimentação de extrato bancário
            \iEducar\Financeiro\Models\MovimentacaoConta::create([
                'conta_bancaria_id' => $conta->id,
                'tipo'              => 'saida',
                'valor'             => $valorFinalPago,
                'saldo_anterior'    => $saldoAnterior,
                'saldo_posterior'   => $saldoPosterior,
                'origem_tipo'       => 'lancamento',
                'origem_id'         => $despesa->id,
                'data_movimentacao' => $validated['data_pagamento'],
                'descricao'         => "Pagamento de Despesa #{$despesa->id}: {$despesa->descricao}",
                'usuario_id'        => auth()->id() ?? null,
            ]);

            $despesa->update([
                'status'            => 'pago',
                'data_pagamento'    => $validated['data_pagamento'],
                'conta_bancaria_id' => $conta->id,
                'valor_pago'        => $valorFinalPago,
                'valor_juros'       => $juros,
                'valor_multa'       => $multa,
                'valor_desconto'    => $desconto,
                'forma_pagamento'   => $validated['forma_pagamento'] ?? 'transferencia',
                'liquidado_por'     => auth()->id() ?? null,
                'liquidado_em'      => \Carbon\Carbon::now(),
            ]);

            return redirect()->back()->with('success', "Despesa '{$despesa->descricao}' liquidada com sucesso! Saldo bancário atualizado.");
        });
    }

    /**
     * Estorno de Liquidação (FEAT-10 / BUG-026)
     */
    public function estornar(Request $request, $id)
    {
        return $this->estornarLiquidacao($request, $id);
    }

    public function estornarLiquidacao(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $despesa = Lancamento::where('tipo', 'despesa')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($despesa->status !== 'pago') {
                return redirect()->back()
                    ->with('error', "A despesa '{$despesa->descricao}' não está liquidada.");
            }

            if (!$despesa->conta_bancaria_id) {
                return redirect()->back()
                    ->with('error', "Não há conta bancária vinculada ao pagamento para efetuar o estorno.");
            }

            $conta = ContaBancaria::lockForUpdate()->findOrFail($despesa->conta_bancaria_id);
            $valorEstorno = (float) ($despesa->valor_pago ?: $despesa->valor);

            $saldoAnterior = (float) $conta->saldo_atual;
            $saldoPosterior = round($saldoAnterior + $valorEstorno, 2);

            $conta->update(['saldo_atual' => $saldoPosterior]);

            \iEducar\Financeiro\Models\MovimentacaoConta::create([
                'conta_bancaria_id' => $conta->id,
                'tipo'              => 'entrada',
                'valor'             => $valorEstorno,
                'saldo_anterior'    => $saldoAnterior,
                'saldo_posterior'   => $saldoPosterior,
                'origem_tipo'       => 'estorno_lancamento',
                'origem_id'         => $despesa->id,
                'data_movimentacao' => \Carbon\Carbon::today()->toDateString(),
                'descricao'         => "Estorno de liquidação da Despesa #{$despesa->id}: {$despesa->descricao}",
                'usuario_id'        => auth()->id() ?? null,
            ]);

            $despesa->update([
                'status'            => 'a_pagar',
                'data_pagamento'    => null,
                'valor_pago'        => null,
                'liquidado_por'     => null,
                'liquidado_em'      => null,
            ]);

            return redirect()->back()->with('success', "Liquidação estornada com sucesso e saldo bancário recomposto.");
        });
    }

    /**
     * Cancelamento de lançamento
     */
    public function destroy($id)
    {
        $despesa = Lancamento::where('tipo', 'despesa')->findOrFail($id);

        // A25 — Despesas liquidadas não podem ser excluídas (audit trail obrigatório)
        if ($despesa->status === 'pago') {
            return redirect()->route('financeiro.contas-pagar.index')
                ->with('error', "A despesa '{$despesa->descricao}' já está liquidada e não pode ser excluída. Realize o estorno da liquidação se necessário.");
        }

        $despesa->delete();

        return redirect()->route('financeiro.contas-pagar.index')
            ->with('success', 'Conta a pagar removida com sucesso!');
    }
}
