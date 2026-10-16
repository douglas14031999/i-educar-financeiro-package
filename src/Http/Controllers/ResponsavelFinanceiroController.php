<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Models\AlunoResponsavel;
use iEducar\Financeiro\Models\ResponsavelFinanceiro;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ResponsavelFinanceiroController extends Controller
{
    /**
     * Listagem de Responsáveis Financeiros
     */
    public function index(Request $request)
    {
        $busca = $request->input('busca');

        $query = ResponsavelFinanceiro::withCount('alunoVinculos');

        if ($busca) {
            $limpo = preg_replace('/\D/', '', $busca);
            $query->where(function ($q) use ($busca, $limpo) {
                $q->where('nome', 'ilike', "%{$busca}%")
                  ->orWhere('email', 'ilike', "%{$busca}%");
                if ($limpo) {
                    $q->orWhere('cpf_cnpj', 'like', "%{$limpo}%");
                }
            });
        }

        $responsaveis = $query->orderBy('nome')->paginate(20);

        return view('financeiro::responsaveis.index', compact('responsaveis', 'busca'));
    }

    /**
     * Formulário de Criação
     */
    public function create()
    {
        $responsavel = new ResponsavelFinanceiro();
        return view('financeiro::responsaveis.create', compact('responsavel'));
    }

    /**
     * Gravação de Novo Responsável
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf_cnpj' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'telefone_celular' => 'required|string|max:20',
            'telefone_fixo' => 'nullable|string|max:20',
            'cep' => 'required|string|max:10',
            'logradouro' => 'required|string|max:255',
            'numero' => 'required|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|size:2',
            'observacoes' => 'nullable|string',
        ]);

        $cpfLimpo = preg_replace('/\D/', '', $validated['cpf_cnpj']);
        if (!ResponsavelFinanceiro::validarCpfCnpj($cpfLimpo)) {
            return back()->withInput()->withErrors(['cpf_cnpj' => 'O CPF ou CNPJ informado não é válido.']);
        }

        // BUG-025: Suporte à restauração de cadastro soft-deleted com o mesmo CPF
        $softDeleted = ResponsavelFinanceiro::onlyTrashed()->where('cpf_cnpj', $cpfLimpo)->first();
        if ($softDeleted) {
            if ($request->boolean('restaurar')) {
                $softDeleted->restore();
                $softDeleted->update($validated);
                return redirect()->route('financeiro.responsaveis.show', $softDeleted->id)
                    ->with('success', 'Cadastro anterior deste responsável foi restaurado e atualizado com sucesso.');
            }
            return back()->withInput()->withErrors([
                'cpf_cnpj' => 'Existe um cadastro anterior inativo/excluído com este CPF/CNPJ. Marque a opção de restauração se desejar reativá-lo.',
            ])->with('pode_restaurar', $softDeleted->id);
        }

        $jaExiste = ResponsavelFinanceiro::where('cpf_cnpj', $cpfLimpo)->exists();
        if ($jaExiste) {
            return back()->withInput()->withErrors(['cpf_cnpj' => 'Já existe um responsável cadastrado com este CPF/CNPJ.']);
        }

        $responsavel = ResponsavelFinanceiro::create($validated);

        return redirect()->route('financeiro.responsaveis.show', $responsavel->id)
            ->with('success', 'Responsável financeiro cadastrado com sucesso.');
    }

    /**
     * Exibição e Gerenciamento de Alunos Vinculados
     */
    public function show($id)
    {
        $responsavel = ResponsavelFinanceiro::with(['alunoVinculos', 'contratos'])->findOrFail($id);

        // Busca alunos no schema do i-Educar se a tabela existir
        $alunosDisponiveis = [];
        if (DB::getSchemaBuilder()->hasTable('pmieducar.aluno')) {
            $alunosDisponiveis = DB::table('pmieducar.aluno as a')
                ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
                ->leftJoin('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
                ->select('a.cod_aluno as id', DB::raw("COALESCE(p.nome, 'Aluno ' || a.cod_aluno) as nome"))
                ->limit(50)
                ->get();
        }

        $saldoVencido = \iEducar\Financeiro\Models\Titulo::where('responsavel_financeiro_id', $responsavel->id)->vencidos()->sum('valor_saldo');
        $saldoAVencer = \iEducar\Financeiro\Models\Titulo::where('responsavel_financeiro_id', $responsavel->id)->abertos()->where('data_vencimento', '>=', \Carbon\Carbon::today())->sum('valor_saldo');
        $totalCreditos = $responsavel->creditosDisponiveis()->sum('valor');
        $totalContratosAtivos = $responsavel->contratos()->where('status', 'ativo')->count();
        $titulosVencidosCount = \iEducar\Financeiro\Models\Titulo::where('responsavel_financeiro_id', $responsavel->id)->vencidos()->count();

        return view('financeiro::responsaveis.show', compact(
            'responsavel',
            'alunosDisponiveis',
            'saldoVencido',
            'saldoAVencer',
            'totalCreditos',
            'totalContratosAtivos',
            'titulosVencidosCount'
        ));
    }

    /**
     * Formulário de Edição
     */
    public function edit($id)
    {
        $responsavel = ResponsavelFinanceiro::findOrFail($id);
        return view('financeiro::responsaveis.edit', compact('responsavel'));
    }

    /**
     * Atualização
     */
    public function update(Request $request, $id)
    {
        $responsavel = ResponsavelFinanceiro::findOrFail($id);

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf_cnpj' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'telefone_celular' => 'required|string|max:20',
            'telefone_fixo' => 'nullable|string|max:20',
            'cep' => 'required|string|max:10',
            'logradouro' => 'required|string|max:255',
            'numero' => 'required|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|size:2',
            'observacoes' => 'nullable|string',
            'ativo' => 'nullable|boolean',
        ]);

        $validated['ativo'] = $request->boolean('ativo', true);

        $cpfLimpo = preg_replace('/\D/', '', $validated['cpf_cnpj']);
        if (!ResponsavelFinanceiro::validarCpfCnpj($cpfLimpo)) {
            return back()->withInput()->withErrors(['cpf_cnpj' => 'O CPF ou CNPJ informado não é válido.']);
        }

        $jaExisteOutro = ResponsavelFinanceiro::where('cpf_cnpj', $cpfLimpo)
            ->where('id', '!=', $id)
            ->exists();
        if ($jaExisteOutro) {
            return back()->withInput()->withErrors(['cpf_cnpj' => 'Outro responsável já possui este documento cadastrado.']);
        }

        $responsavel->update($validated);

        return redirect()->route('financeiro.responsaveis.show', $responsavel->id)
            ->with('success', 'Dados do responsável atualizados com sucesso.');
    }

    /**
     * Exclusão (BUG-025)
     */
    public function destroy($id)
    {
        $responsavel = ResponsavelFinanceiro::findOrFail($id);

        // Bloqueia se houver títulos pendentes
        if ($responsavel->titulos()->whereIn('status', ['aberto', 'parcial', 'vencido'])->exists()) {
            return back()->with('error', 'Não é possível excluir este responsável pois existem títulos financeiros pendentes ou em aberto.');
        }

        // Bloqueia se houver contratos ativos
        if (\iEducar\Financeiro\Models\Contrato::where('responsavel_financeiro_id', $id)->where('status', '!=', 'cancelado')->exists()) {
            return back()->with('error', 'Não é possível excluir este responsável pois existem contratos ativos associados.');
        }

        // Bloqueia se possuir saldo de créditos financeiros
        if ($responsavel->creditosDisponiveis()->sum('valor') > 0) {
            return back()->with('error', 'Não é possível excluir este responsável pois ele possui créditos financeiros disponíveis.');
        }

        if ($responsavel->alunos()->exists()) {
            return back()->with('error', 'Não é possível excluir este responsável pois ainda possui vínculos com alunos.');
        }

        $responsavel->delete();

        return redirect()->route('financeiro.responsaveis.index')
            ->with('success', 'Responsável financeiro removido com sucesso.');
    }

    /**
     * Vincula Aluno com Percentual de Rateio
     */
    public function vincularAluno(Request $request, $id)
    {
        $request->validate([
            'aluno_id' => 'required|integer',
            'parentesco' => 'nullable|string|max:50',
            'percentual_rateio' => 'nullable|numeric|min:1|max:100',
        ]);

        $responsavel = ResponsavelFinanceiro::findOrFail($id);
        $alunoId = (int) $request->input('aluno_id');
        $percentual = $request->filled('percentual_rateio') ? (float) $request->input('percentual_rateio') : 100.00;
        $vinculoExistente = AlunoResponsavel::where('aluno_id', $alunoId)
            ->where('responsavel_id', $responsavel->id)
            ->first();

        try {
            AlunoResponsavel::validarSomaRateio($alunoId, $percentual, $vinculoExistente?->id);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        AlunoResponsavel::updateOrCreate([
            'aluno_id' => $alunoId,
            'responsavel_id' => $responsavel->id,
        ], [
            'parentesco' => $request->input('parentesco') ?: 'Responsável',
            'percentual_rateio' => $percentual,
            'responsavel_principal' => $request->boolean('responsavel_principal', true),
        ]);

        return back()->with('success', 'Aluno vinculado ao responsável financeiro com sucesso.');
    }

    /**
     * Desvincula Aluno
     */
    public function desvincularAluno($id, $alunoId)
    {
        AlunoResponsavel::where('responsavel_id', $id)
            ->where('aluno_id', $alunoId)
            ->delete();

        return back()->with('success', 'Vínculo do aluno removido com sucesso.');
    }
}
