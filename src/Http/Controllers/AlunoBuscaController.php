<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Models\AlunoResponsavel;
use iEducar\Financeiro\Models\ResponsavelFinanceiro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AlunoBuscaController extends Controller
{
    /**
     * Busca rápida de alunos por Nome ou CPF (retorna JSON para autocomplete)
     */
    public function buscar(Request $request): JsonResponse
    {
        $termo = trim((string) $request->input('q', ''));
        if (mb_strlen($termo) < 2) {
            return response()->json([]);
        }

        $termoDigitos = preg_replace('/\D/', '', $termo);

        $query = DB::table('pmieducar.aluno as a')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->where('a.ativo', 1)
            ->select([
                'a.cod_aluno as id',
                'p.nome',
                'f.cpf',
                'f.data_nasc',
            ]);

        if (!empty($termoDigitos) && strlen($termoDigitos) >= 3) {
            $query->where(function ($q) use ($termo, $termoDigitos) {
                $q->where('a.cod_aluno', (int) $termoDigitos)
                  ->orWhere('f.cpf', 'like', "%{$termoDigitos}%");
            });
        } else {
            $query->whereRaw('LOWER(p.nome) LIKE ?', ['%' . mb_strtolower($termo, 'UTF-8') . '%']);
        }

        $alunos = $query->orderBy('p.nome')->limit(20)->get();

        $resultado = $alunos->map(function ($aluno) {
            $cpfFormatado = '';
            if (!empty($aluno->cpf)) {
                $cpfLimpo = str_pad(preg_replace('/\D/', '', (string) $aluno->cpf), 11, '0', STR_PAD_LEFT);
                $cpfFormatado = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfLimpo);
            }

            $dataNascimento = $aluno->data_nasc ? date('d/m/Y', strtotime($aluno->data_nasc)) : null;

            return [
                'id' => (int) $aluno->id,
                'nome' => $aluno->nome,
                'cpf' => $cpfFormatado,
                'data_nascimento' => $dataNascimento,
            ];
        });

        return response()->json($resultado);
    }

    /**
     * Retorna os detalhes completos do aluno, suas matrículas e os responsáveis identificados no i-Educar
     */
    public function detalhes($id): JsonResponse
    {
        $alunoId = (int) $id;

        // 1. Dados básicos do aluno
        $aluno = DB::table('pmieducar.aluno as a')
            ->join('cadastro.pessoa as p', 'p.idpes', '=', 'a.ref_idpes')
            ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'a.ref_idpes')
            ->where('a.cod_aluno', $alunoId)
            ->where('a.ativo', 1)
            ->select([
                'a.cod_aluno as id',
                'p.nome',
                'f.cpf',
                'f.data_nasc',
                'f.idpes_mae',
                'f.nome_mae',
                'f.idpes_pai',
                'f.nome_pai',
                'f.idpes_responsavel',
                'f.nome_responsavel',
            ])
            ->first();

        if (!$aluno) {
            return response()->json(['error' => 'Aluno não encontrado no i-Educar.'], 404);
        }

        $cpfFormatado = '';
        if (!empty($aluno->cpf)) {
            $cpfLimpo = str_pad(preg_replace('/\D/', '', (string) $aluno->cpf), 11, '0', STR_PAD_LEFT);
            $cpfFormatado = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfLimpo);
        }

        // 2. Matrículas ativas do aluno
        $matriculas = DB::table('pmieducar.matricula as m')
            ->leftJoin('pmieducar.escola as e', 'e.cod_escola', '=', 'm.ref_ref_cod_escola')
            ->leftJoin('cadastro.pessoa as pe', 'pe.idpes', '=', 'e.ref_idpes')
            ->leftJoin('pmieducar.curso as c', 'c.cod_curso', '=', 'm.ref_cod_curso')
            ->leftJoin('pmieducar.serie as s', 's.cod_serie', '=', 'm.ref_ref_cod_serie')
            ->where('m.ref_cod_aluno', $alunoId)
            ->where('m.ativo', 1)
            ->orderBy('m.ano', 'desc')
            ->orderBy('m.cod_matricula', 'desc')
            ->select([
                'm.cod_matricula',
                'm.ano',
                'm.ref_ref_cod_escola as escola_id',
                'pe.nome as escola_nome',
                'c.nm_curso as curso_nome',
                's.nm_serie as serie_nome',
                'm.modalidade_ensino',
            ])
            ->get()
            ->map(function ($m) {
                return [
                    'cod_matricula' => (int) $m->cod_matricula,
                    'ano' => (int) $m->ano,
                    'escola_id' => (int) $m->escola_id,
                    'escola_nome' => $m->escola_nome ?: 'Escola não identificada',
                    'curso_nome' => $m->curso_nome ?: 'Ensino Regular',
                    'serie_nome' => $m->serie_nome ?: 'Série regular',
                    'descricao_completa' => sprintf(
                        'Matrícula #%d (%d) - %s - %s (%s)',
                        $m->cod_matricula,
                        $m->ano,
                        $m->serie_nome ?: 'Série',
                        $m->curso_nome ?: 'Curso',
                        $m->escola_nome ?: 'Escola'
                    ),
                ];
            });

        // 3. Mapear responsáveis candidatos do i-Educar
        $candidatos = [];

        if (!empty($aluno->idpes_mae) || !empty($aluno->nome_mae)) {
            $candidatos[] = [
                'tipo' => 'Mãe',
                'idpes' => $aluno->idpes_mae ? (int) $aluno->idpes_mae : null,
                'nome_fallback' => $aluno->nome_mae,
            ];
        }

        if (!empty($aluno->idpes_pai) || !empty($aluno->nome_pai)) {
            $candidatos[] = [
                'tipo' => 'Pai',
                'idpes' => $aluno->idpes_pai ? (int) $aluno->idpes_pai : null,
                'nome_fallback' => $aluno->nome_pai,
            ];
        }

        if (!empty($aluno->idpes_responsavel) || !empty($aluno->nome_responsavel)) {
            $candidatos[] = [
                'tipo' => 'Responsável Legal',
                'idpes' => $aluno->idpes_responsavel ? (int) $aluno->idpes_responsavel : null,
                'nome_fallback' => $aluno->nome_responsavel,
            ];
        }

        // Também verificar se o aluno já tem responsáveis vinculados no Financeiro
        $responsaveisJaVinculados = AlunoResponsavel::with('responsavel')
            ->where('aluno_id', $alunoId)
            ->get();

        $responsaveisProcessados = [];
        $idsPessoasJaIncluidas = [];

        // Adiciona primeiro os que já têm vínculo financeiro
        foreach ($responsaveisJaVinculados as $vinculo) {
            $resp = $vinculo->responsavel;
            if (!$resp) continue;

            if ($resp->pessoa_id) {
                $idsPessoasJaIncluidas[] = $resp->pessoa_id;
            }

            $responsaveisProcessados[] = [
                'responsavel_financeiro_id' => $resp->id,
                'pessoa_id' => $resp->pessoa_id,
                'parentesco' => $vinculo->parentesco ?: 'Responsável Financeiro',
                'nome' => $resp->nome,
                'cpf' => $resp->cpf_cnpj_formatado,
                'cpf_puro' => $resp->cpf_cnpj,
                'email' => $resp->email,
                'telefone_celular' => $resp->telefone_celular,
                'cep' => $resp->cep,
                'logradouro' => $resp->logradouro,
                'numero' => $resp->numero,
                'complemento' => $resp->complemento,
                'bairro' => $resp->bairro,
                'cidade' => $resp->cidade,
                'estado' => $resp->estado,
                'ja_cadastrado_financeiro' => true,
                'ja_vinculado_aluno' => true,
                'responsavel_principal' => (bool) $vinculo->responsavel_principal,
                'cadastro_completo' => !empty($resp->cpf_cnpj) && !empty($resp->cep) && !empty($resp->logradouro),
            ];
        }

        // Processa candidatos do i-Educar que ainda não estão no array
        foreach ($candidatos as $cand) {
            $idpes = $cand['idpes'];
            $tipo = $cand['tipo'];

            if ($idpes && in_array($idpes, $idsPessoasJaIncluidas)) {
                continue;
            }

            $nome = $cand['nome_fallback'];
            $cpf = null;
            $email = null;
            $telefone = null;
            $endereco = null;

            if ($idpes) {
                $dadosPessoa = DB::table('cadastro.pessoa as p')
                    ->leftJoin('cadastro.fisica as f', 'f.idpes', '=', 'p.idpes')
                    ->leftJoin('cadastro.v_endereco as e', 'e.idpes', '=', 'p.idpes')
                    ->leftJoin('cadastro.v_fone_pessoa as fp', 'fp.idpes', '=', 'p.idpes')
                    ->where('p.idpes', $idpes)
                    ->select([
                        'p.idpes',
                        'p.nome',
                        'p.email',
                        'f.cpf',
                        'e.cep',
                        'e.logradouro',
                        'e.numero',
                        'e.complemento',
                        'e.bairro',
                        'e.cidade',
                        'e.sigla_uf',
                        'fp.ddd_mov',
                        'fp.fone_mov',
                        'fp.ddd_1',
                        'fp.fone_1',
                    ])
                    ->first();

                if ($dadosPessoa) {
                    $nome = $dadosPessoa->nome ?: $nome;
                    $cpf = $dadosPessoa->cpf;
                    $email = $dadosPessoa->email;
                    if (!empty($dadosPessoa->fone_mov)) {
                        $ddd  = trim((string) $dadosPessoa->ddd_mov);
                        $fone = preg_replace('/\D/', '', (string) $dadosPessoa->fone_mov);
                        $telefone = $ddd ? "({$ddd}) {$fone}" : $fone;
                    } elseif (!empty($dadosPessoa->fone_1)) {
                        $ddd  = trim((string) $dadosPessoa->ddd_1);
                        $fone = preg_replace('/\D/', '', (string) $dadosPessoa->fone_1);
                        $telefone = $ddd ? "({$ddd}) {$fone}" : $fone;
                    }
                    $endereco = [
                        'cep' => $dadosPessoa->cep,
                        'logradouro' => $dadosPessoa->logradouro,
                        'numero' => $dadosPessoa->numero,
                        'complemento' => $dadosPessoa->complemento,
                        'bairro' => $dadosPessoa->bairro,
                        'cidade' => $dadosPessoa->cidade,
                        'estado' => $dadosPessoa->sigla_uf,
                    ];
                }
            }

            if (empty($nome)) {
                continue;
            }

            $cpfLimpo = $cpf ? preg_replace('/\D/', '', (string) $cpf) : '';
            if (strlen($cpfLimpo) > 0) {
                $cpfLimpo = str_pad($cpfLimpo, 11, '0', STR_PAD_LEFT);
            }

            // Checar se já existe no financeiro por CPF ou pessoa_id
            $queryFinanceiro = ResponsavelFinanceiro::query();
            if ($idpes) {
                $queryFinanceiro->where('pessoa_id', $idpes);
            }
            if (!empty($cpfLimpo)) {
                $queryFinanceiro->orWhere('cpf_cnpj', $cpfLimpo);
            }
            $respFinanceiro = $queryFinanceiro->first();

            $cpfFormatadoResp = '';
            if (!empty($cpfLimpo)) {
                $cpfFormatadoResp = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfLimpo);
            }

            $jaCadastrado = (bool) $respFinanceiro;
            $cadastroCompleto = !empty($cpfLimpo) && !empty($endereco['cep']) && !empty($endereco['logradouro']);

            if ($idpes) {
                $idsPessoasJaIncluidas[] = $idpes;
            }

            $responsaveisProcessados[] = [
                'responsavel_financeiro_id' => $respFinanceiro ? $respFinanceiro->id : null,
                'pessoa_id' => $idpes,
                'parentesco' => $tipo,
                'nome' => $respFinanceiro ? $respFinanceiro->nome : $nome,
                'cpf' => $respFinanceiro ? $respFinanceiro->cpf_cnpj_formatado : $cpfFormatadoResp,
                'cpf_puro' => $respFinanceiro ? $respFinanceiro->cpf_cnpj : $cpfLimpo,
                'email' => $respFinanceiro ? $respFinanceiro->email : ($email ?: ''),
                'telefone_celular' => $respFinanceiro ? $respFinanceiro->telefone_celular : ($telefone ?: ''),
                'cep' => $respFinanceiro ? $respFinanceiro->cep : ($endereco['cep'] ?? ''),
                'logradouro' => $respFinanceiro ? $respFinanceiro->logradouro : ($endereco['logradouro'] ?? ''),
                'numero' => $respFinanceiro ? $respFinanceiro->numero : ($endereco['numero'] ?? ''),
                'complemento' => $respFinanceiro ? $respFinanceiro->complemento : ($endereco['complemento'] ?? ''),
                'bairro' => $respFinanceiro ? $respFinanceiro->bairro : ($endereco['bairro'] ?? ''),
                'cidade' => $respFinanceiro ? $respFinanceiro->cidade : ($endereco['cidade'] ?? ''),
                'estado' => $respFinanceiro ? $respFinanceiro->estado : ($endereco['estado'] ?? ''),
                'ja_cadastrado_financeiro' => $jaCadastrado,
                'ja_vinculado_aluno' => false,
                'responsavel_principal' => false,
                'cadastro_completo' => $jaCadastrado ? true : $cadastroCompleto,
            ];
        }

        return response()->json([
            'aluno' => [
                'id' => (int) $aluno->id,
                'nome' => $aluno->nome,
                'cpf' => $cpfFormatado,
                'data_nascimento' => $aluno->data_nasc ? date('d/m/Y', strtotime($aluno->data_nasc)) : null,
            ],
            'matriculas' => $matriculas,
            'responsaveis' => $responsaveisProcessados,
        ]);
    }

    /**
     * Vincula ou cria instantaneamente o responsável financeiro e associa ao aluno
     */
    public function vincularOuCriarResponsavel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'aluno_id' => 'required|integer',
            'responsavel_financeiro_id' => 'nullable|integer',
            'pessoa_id' => 'nullable|integer',
            'parentesco' => 'nullable|string|max:50',
            'nome' => 'required|string|max:255',
            'cpf_cnpj' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefone_celular' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
        ]);

        $cpfLimpo = preg_replace('/\D/', '', $validated['cpf_cnpj']);
        if (!ResponsavelFinanceiro::validarCpfCnpj($cpfLimpo)) {
            return response()->json(['error' => 'O CPF ou CNPJ informado é inválido.'], 422);
        }

        $alunoId = (int) $validated['aluno_id'];
        $responsavelId = !empty($validated['responsavel_financeiro_id']) ? (int) $validated['responsavel_financeiro_id'] : null;

        // Se já tem ID de responsável financeiro, busca e atualiza dados complementares se fornecidos
        $responsavel = null;
        if ($responsavelId) {
            $responsavel = ResponsavelFinanceiro::find($responsavelId);
        }

        // Se não achou por ID, busca por CPF
        if (!$responsavel) {
            $responsavel = ResponsavelFinanceiro::where('cpf_cnpj', $cpfLimpo)->first();
        }

        // Se ainda não achou e tem pessoa_id, busca por pessoa_id
        if (!$responsavel && !empty($validated['pessoa_id'])) {
            $responsavel = ResponsavelFinanceiro::where('pessoa_id', (int) $validated['pessoa_id'])->first();
        }

        if ($responsavel) {
            // Atualiza campos que estavam vazios
            $updates = [];
            if (empty($responsavel->cep) && !empty($validated['cep'])) $updates['cep'] = $validated['cep'];
            if (empty($responsavel->logradouro) && !empty($validated['logradouro'])) $updates['logradouro'] = $validated['logradouro'];
            if (empty($responsavel->numero) && !empty($validated['numero'])) $updates['numero'] = $validated['numero'];
            if (empty($responsavel->bairro) && !empty($validated['bairro'])) $updates['bairro'] = $validated['bairro'];
            if (empty($responsavel->cidade) && !empty($validated['cidade'])) $updates['cidade'] = $validated['cidade'];
            if (empty($responsavel->estado) && !empty($validated['estado'])) $updates['estado'] = $validated['estado'];
            if (empty($responsavel->telefone_celular) && !empty($validated['telefone_celular'])) $updates['telefone_celular'] = $validated['telefone_celular'];
            if (empty($responsavel->email) && !empty($validated['email'])) $updates['email'] = $validated['email'];

            if (!empty($updates)) {
                $responsavel->update($updates);
            }
        } else {
            // Cria o registro no módulo financeiro
            // IMPORTANTE: campos opcionais vão como null, nunca com placeholders falsos
            $responsavel = ResponsavelFinanceiro::create([
                'pessoa_id'       => !empty($validated['pessoa_id']) ? (int) $validated['pessoa_id'] : null,
                'nome'            => trim($validated['nome']),
                'cpf_cnpj'        => $cpfLimpo,
                'email'           => !empty($validated['email']) ? trim($validated['email']) : null,
                'telefone_celular' => !empty($validated['telefone_celular']) ? trim($validated['telefone_celular']) : null,
                'cep'             => !empty($validated['cep'])       ? trim($validated['cep'])       : null,
                'logradouro'      => !empty($validated['logradouro']) ? trim($validated['logradouro']) : null,
                'numero'          => !empty($validated['numero'])    ? trim($validated['numero'])    : null,
                'complemento'     => $validated['complemento'] ?? null,
                'bairro'          => !empty($validated['bairro'])    ? trim($validated['bairro'])    : null,
                'cidade'          => !empty($validated['cidade'])    ? trim($validated['cidade'])    : null,
                'estado'          => !empty($validated['estado'])    ? strtoupper(trim($validated['estado'])) : null,
                'ativo'           => true,
            ]);
        }

        // BUG-009: Vincula o responsável ajustando percentuais para garantir soma estrita de 100,00%
        /** @var \iEducar\Financeiro\Services\RateioResponsavelService $rateioService */
        $rateioService = app(\iEducar\Financeiro\Services\RateioResponsavelService::class);
        $vinculo = $rateioService->vincularComAjusteRateio(
            $alunoId,
            $responsavel->id,
            $validated['parentesco'] ?: 'Responsável'
        );

        return response()->json([
            'success' => true,
            'responsavel' => [
                'id' => $responsavel->id,
                'nome' => $responsavel->nome,
                'cpf' => $responsavel->cpf_cnpj_formatado,
                'parentesco' => $vinculo->parentesco,
                'telefone' => $responsavel->telefone_celular,
                'email' => $responsavel->email,
            ],
            'message' => "Responsável {$responsavel->nome} vinculado com sucesso!",
        ]);
    }
}
