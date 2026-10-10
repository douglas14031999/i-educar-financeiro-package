<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Models\PlanoItem;
use iEducar\Financeiro\Models\PlanoPagamento;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class PlanoPagamentoController extends Controller
{
    /**
     * Listagem dos Planos de Cobrança
     */
    public function index(Request $request)
    {
        $anoLetivo = $request->input('ano_letivo', date('Y'));

        $query = PlanoPagamento::with('itens');

        if ($anoLetivo) {
            $query->where('ano_letivo', $anoLetivo);
        }

        $planos = $query->orderBy('ano_letivo', 'desc')->orderBy('nome')->paginate(15);

        return view('financeiro::planos.index', compact('planos', 'anoLetivo'));
    }

    /**
     * Formulário de Criação
     */
    public function create()
    {
        $plano = new PlanoPagamento([
            'ano_letivo' => date('Y'),
            'qtd_parcelas' => 12,
            'valor_total' => 0.00,
            'escola_id' => 1,
        ]);

        return view('financeiro::planos.create', compact('plano'));
    }

    /**
     * Gravação do Plano e Geração Automática das Parcelas
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'escola_id' => 'required|integer',
            'ano_letivo' => 'required|integer|min:2020|max:2040',
            'curso_id' => 'nullable|integer',
            'serie_id' => 'nullable|integer',
            'nome' => 'required|string|max:150',
            'descricao' => 'nullable|string',
            'valor_total' => 'required|numeric|min:1',
            'qtd_parcelas' => 'required|integer|min:1|max:24',
            'dia_vencimento_padrao' => 'required|integer|min:1|max:31',
            'gerar_matricula_avulsa' => 'nullable|boolean',
            'valor_matricula' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $plano = PlanoPagamento::create([
                'escola_id' => $validated['escola_id'],
                'ano_letivo' => $validated['ano_letivo'],
                'curso_id' => $validated['curso_id'] ?? null,
                'serie_id' => $validated['serie_id'] ?? null,
                'nome' => $validated['nome'],
                'descricao' => $validated['descricao'] ?? null,
                'valor_total' => $validated['valor_total'],
                'qtd_parcelas' => $validated['qtd_parcelas'],
                'ativo' => true,
            ]);

            $diaVencimento = (int) $validated['dia_vencimento_padrao'];
            $qtd = (int) $validated['qtd_parcelas'];
            $valorTotal = (float) $validated['valor_total'];

            // Se houver matrícula avulsa à parte
            if ($request->boolean('gerar_matricula_avulsa') && (float) $request->input('valor_matricula') > 0) {
                PlanoItem::create([
                    'plano_id' => $plano->id,
                    'tipo_item' => 'matricula',
                    'descricao' => 'Taxa de Matrícula',
                    'numero_parcela' => 0,
                    'valor' => (float) $request->input('valor_matricula'),
                    'mes_competencia' => 1,
                    'dia_vencimento_padrao' => $diaVencimento,
                ]);
            }

            // Distribuição das mensalidades regulares
            $valorParcela = round($valorTotal / $qtd, 2);
            $centavosAjuste = round($valorTotal - ($valorParcela * $qtd), 2);

            for ($i = 1; $i <= $qtd; $i++) {
                $mesComp = $i <= 12 ? $i : ($i % 12);
                $valorFinalItem = ($i === 1) ? ($valorParcela + $centavosAjuste) : $valorParcela;

                PlanoItem::create([
                    'plano_id' => $plano->id,
                    'tipo_item' => 'mensalidade',
                    'descricao' => sprintf('Mensalidade %02d/%02d', $i, $qtd),
                    'numero_parcela' => $i,
                    'valor' => $valorFinalItem,
                    'mes_competencia' => $mesComp,
                    'dia_vencimento_padrao' => $diaVencimento,
                ]);
            }

            return redirect()->route('financeiro.planos.show', $plano->id)
                ->with('success', 'Plano de cobrança e parcelas gerados com sucesso.');
        });
    }

    /**
     * Visualização do Plano e Detalhamento dos Itens
     */
    public function show($id)
    {
        $plano = PlanoPagamento::with('itens')->findOrFail($id);
        return view('financeiro::planos.show', compact('plano'));
    }

    /**
     * Exclusão
     */
    public function destroy($id)
    {
        $plano = PlanoPagamento::findOrFail($id);

        if ($plano->contratos()->exists()) {
            return back()->with('error', 'Não é possível excluir um plano com contratos já vinculados.');
        }

        $plano->itens()->delete();
        $plano->delete();

        return redirect()->route('financeiro.planos.index')
            ->with('success', 'Plano de pagamento removido com sucesso.');
    }
}
