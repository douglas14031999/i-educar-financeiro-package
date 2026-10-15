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
            $diaVencimento = (int) $validated['dia_vencimento_padrao'];
            $qtd = (int) $validated['qtd_parcelas'];
            $valorMensalidades = (float) $validated['valor_total'];
            $temMatricula = $request->boolean('gerar_matricula_avulsa') && (float) $request->input('valor_matricula') > 0;
            $valorMatricula = $temMatricula ? (float) $request->input('valor_matricula') : 0.00;
            $valorTotalPlano = round($valorMensalidades + $valorMatricula, 2);

            $plano = PlanoPagamento::create([
                'escola_id' => $validated['escola_id'],
                'ano_letivo' => $validated['ano_letivo'],
                'curso_id' => $validated['curso_id'] ?? null,
                'serie_id' => $validated['serie_id'] ?? null,
                'nome' => $validated['nome'],
                'descricao' => $validated['descricao'] ?? null,
                'valor_total' => $valorTotalPlano,
                'qtd_parcelas' => $qtd,
                'ativo' => true,
            ]);

            // Se houver matrícula integrada ao plano
            if ($temMatricula) {
                PlanoItem::create([
                    'plano_id' => $plano->id,
                    'tipo_item' => 'matricula',
                    'descricao' => 'Taxa de Matrícula',
                    'numero_parcela' => 0,
                    'valor' => $valorMatricula,
                    'mes_competencia' => 1,
                    'dia_vencimento_padrao' => $diaVencimento,
                ]);
            }

            // Distribuição das mensalidades regulares
            $valorParcela = round($valorMensalidades / $qtd, 2);
            $centavosAjuste = round($valorMensalidades - ($valorParcela * $qtd), 2);

            for ($i = 1; $i <= $qtd; $i++) {
                // FIN-007: Mês de competência nunca pode ser 0. Para parcelas > 12 avança ciclicamente de 1 a 12
                $mesComp = (($i - 1) % 12) + 1;
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
        $possuiContratos = $plano->contratos()->exists();
        return view('financeiro::planos.show', compact('plano', 'possuiContratos'));
    }

    /**
     * Formulário de Edição (Apenas planos sem contratos vinculados - BUG-024)
     */
    public function edit($id)
    {
        $plano = PlanoPagamento::with('itens')->findOrFail($id);

        if ($plano->contratos()->exists()) {
            return redirect()->route('financeiro.planos.show', $plano->id)
                ->with('error', 'Não é possível editar este plano de pagamento pois já existem contratos vinculados a ele.');
        }

        try {
            $escolas = DB::table('pmieducar.escola')
                ->join('cadastro.juridica', 'cadastro.juridica.idpes', '=', 'pmieducar.escola.ref_idpes')
                ->select('pmieducar.escola.cod_escola as id', 'cadastro.juridica.fantasia as nome')
                ->orderBy('cadastro.juridica.fantasia')
                ->get();
        } catch (\Throwable $e) {
            $escolas = collect();
        }

        return view('financeiro::planos.edit', compact('plano', 'escolas'));
    }

    /**
     * Atualização do Plano e Regeneração dos Itens (BUG-024)
     */
    public function update(Request $request, $id)
    {
        $plano = PlanoPagamento::with('itens')->findOrFail($id);

        if ($plano->contratos()->exists()) {
            return redirect()->route('financeiro.planos.show', $plano->id)
                ->with('error', 'Não é possível editar este plano de pagamento pois já existem contratos vinculados a ele.');
        }

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

        return DB::transaction(function () use ($plano, $validated, $request) {
            $diaVencimento = (int) $validated['dia_vencimento_padrao'];
            $qtd = (int) $validated['qtd_parcelas'];
            $valorMensalidades = (float) $validated['valor_total'];
            $temMatricula = $request->boolean('gerar_matricula_avulsa') && (float) $request->input('valor_matricula') > 0;
            $valorMatricula = $temMatricula ? (float) $request->input('valor_matricula') : 0.00;
            $valorTotalPlano = round($valorMensalidades + $valorMatricula, 2);

            $plano->update([
                'escola_id' => $validated['escola_id'],
                'ano_letivo' => $validated['ano_letivo'],
                'curso_id' => $validated['curso_id'] ?? null,
                'serie_id' => $validated['serie_id'] ?? null,
                'nome' => $validated['nome'],
                'descricao' => $validated['descricao'] ?? null,
                'valor_total' => $valorTotalPlano,
                'qtd_parcelas' => $qtd,
            ]);

            // Remove itens antigos e recria
            $plano->itens()->delete();

            if ($temMatricula) {
                PlanoItem::create([
                    'plano_id' => $plano->id,
                    'tipo_item' => 'matricula',
                    'descricao' => 'Taxa de Matrícula',
                    'numero_parcela' => 0,
                    'valor' => $valorMatricula,
                    'mes_competencia' => 1,
                    'dia_vencimento_padrao' => $diaVencimento,
                ]);
            }

            $valorParcela = round($valorMensalidades / $qtd, 2);
            $centavosAjuste = round($valorMensalidades - ($valorParcela * $qtd), 2);

            for ($i = 1; $i <= $qtd; $i++) {
                $mesComp = (($i - 1) % 12) + 1;
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
                ->with('success', 'Plano de cobrança atualizado com sucesso.');
        });
    }

    /**
     * Copia / Duplica um Plano para novo Ano Letivo (FEAT-08)
     */
    public function copiar(Request $request, $id)
    {
        $origem = PlanoPagamento::with('itens')->findOrFail($id);

        $novoAno = (int) $request->input('novo_ano_letivo', $origem->ano_letivo + 1);
        $novoNome = $request->input('novo_nome', $origem->nome . " ({$novoAno})");

        return DB::transaction(function () use ($origem, $novoAno, $novoNome) {
            $novoPlano = PlanoPagamento::create([
                'escola_id'    => $origem->escola_id,
                'ano_letivo'   => $novoAno,
                'curso_id'     => $origem->curso_id,
                'serie_id'     => $origem->serie_id,
                'nome'         => $novoNome,
                'descricao'    => $origem->descricao ? ($origem->descricao . " [Copiado do ano {$origem->ano_letivo}]") : null,
                'valor_total'  => $origem->valor_total,
                'qtd_parcelas' => $origem->qtd_parcelas,
                'ativo'        => true,
            ]);

            foreach ($origem->itens as $item) {
                PlanoItem::create([
                    'plano_id'              => $novoPlano->id,
                    'tipo_item'             => $item->tipo_item,
                    'descricao'             => $item->descricao,
                    'numero_parcela'        => $item->numero_parcela,
                    'valor'                 => $item->valor,
                    'mes_competencia'       => $item->mes_competencia,
                    'dia_vencimento_padrao' => $item->dia_vencimento_padrao,
                ]);
            }

            return redirect()->route('financeiro.planos.show', $novoPlano->id)
                ->with('success', "Plano copiado com sucesso para o ano letivo {$novoAno}.");
        });
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
