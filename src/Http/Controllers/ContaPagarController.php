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

        return view('financeiro::contas-pagar.create', compact('planosContas', 'centrosCusto', 'contasBancarias'));
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
            'plano_conta_id' => 'required|exists:plano_contas,id',
            'centro_custo_id' => 'nullable|exists:centros_custo,id',
            'conta_bancaria_id' => 'nullable|exists:contas_bancarias,id',
            'documento_fiscal' => 'nullable|string|max:100',
            'observacoes' => 'nullable|string',
        ]);

        $dados['tipo']     = 'despesa';
        $dados['status']   = 'a_pagar';
        // A28 — escola_id deve vir do formulário; sem fallback silencioso para ID 1
        $dados['escola_id'] = $request->input('escola_id') ? (int) $request->input('escola_id') : null;
        // A28 — usuário autenticado obrigatório
        if (!auth()->check()) {
            return redirect()->back()->with('error', 'Sessão expirada. Faça login novamente.');
        }
        $dados['usuario_id'] = auth()->id();

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

        return view('financeiro::contas-pagar.edit', compact('despesa', 'planosContas', 'centrosCusto', 'contasBancarias'));
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
     * Liquidação / Baixa da Conta a Pagar (FIN-012)
     */
    public function liquidar(Request $request, $id)
    {
        $request->validate([
            'data_pagamento' => 'required|date',
            'conta_bancaria_id' => 'required|exists:contas_bancarias,id',
        ]);

        return DB::transaction(function () use ($request, $id) {
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

            $despesa->update([
                'status' => 'pago',
                'data_pagamento' => $request->input('data_pagamento'),
                'conta_bancaria_id' => $request->input('conta_bancaria_id'),
            ]);

            return redirect()->back()->with('success', "Despesa '{$despesa->descricao}' liquidada com sucesso!");
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
                ->with('error', "A despesa '{$despesa->descricao}' já está liquidada e não pode ser excluída. Crie um estorno contabil se necessário.");
        }

        $despesa->delete();

        return redirect()->route('financeiro.contas-pagar.index')
            ->with('success', 'Conta a pagar removida com sucesso!');
    }
}
