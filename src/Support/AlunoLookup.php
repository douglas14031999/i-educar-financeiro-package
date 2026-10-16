<?php

namespace iEducar\Financeiro\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlunoLookup
{
    /**
     * Cache estático em memória para CPFs de alunos no ciclo da requisição
     * Formato: [aluno_id => 'CPF Formatado ou Vazio']
     */
    protected static array $cacheCpfs = [];

    /**
     * Cache estático em memória para nomes de alunos no ciclo da requisição
     * Formato: [aluno_id => 'Nome Completo']
     */
    protected static array $cacheNomes = [];

    /**
     * Cache estático em memória para turmas no ciclo da requisição
     * Formato: ['aluno_id_ano' => 'Nome da Turma']
     */
    protected static array $cacheTurmas = [];

    /**
     * Limpa o cache estático (ideal para testes automatizados)
     */
    public static function limparCache(): void
    {
        static::$cacheNomes = [];
        static::$cacheCpfs = [];
        static::$cacheTurmas = [];
    }

    /**
     * Define manualmente um nome no cache (útil para testes unitários/mock)
     */
    public static function definirNome(int $alunoId, string $nome, ?string $cpf = null): void
    {
        static::$cacheNomes[$alunoId] = $nome;
        if ($cpf !== null) {
            static::$cacheCpfs[$alunoId] = static::formatarCpf($cpf);
        }
    }

    /**
     * Formata uma string de CPF para o padrão 000.000.000-00
     */
    public static function formatarCpf(?string $cpf): string
    {
        if (empty($cpf)) {
            return '';
        }
        $digits = preg_replace('/\D/', '', (string) $cpf);
        if (strlen($digits) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);
        }
        return $cpf;
    }

    /**
     * Define manualmente uma turma no cache (útil para testes unitários/mock)
     */
    public static function definirTurma(int $alunoId, string $turma, ?int $ano = null): void
    {
        $sufixo = $ano ? (string) $ano : 'any';
        static::$cacheTurmas[$alunoId . '_' . $sufixo] = $turma;
    }

    /**
     * Resolve nomes e CPFs de alunos em lote com uma única consulta SQL
     *
     * @param array $alunoIds
     * @return array<int, string>
     */
    public static function nomes(array $alunoIds): array
    {
        $ids = array_values(array_filter(array_unique(array_map('intval', $alunoIds)), fn($id) => $id > 0));
        if (empty($ids)) {
            return [];
        }

        $faltantes = array_diff($ids, array_keys(static::$cacheNomes));

        if (!empty($faltantes)) {
            $encontrados = [];
            $cpfs = [];
            try {
                if (Schema::hasTable('pmieducar.aluno') && Schema::hasTable('cadastro.pessoa')) {
                    $query = DB::table('pmieducar.aluno')
                        ->join('cadastro.pessoa', 'cadastro.pessoa.idpes', '=', 'pmieducar.aluno.ref_idpes');

                    if (Schema::hasTable('cadastro.fisica')) {
                        $query->leftJoin('cadastro.fisica', 'cadastro.fisica.idpes', '=', 'cadastro.pessoa.idpes')
                              ->select('pmieducar.aluno.cod_aluno as id', 'cadastro.pessoa.nome', 'cadastro.fisica.cpf');
                    } else {
                        $query->select('pmieducar.aluno.cod_aluno as id', 'cadastro.pessoa.nome', DB::raw("NULL as cpf"));
                    }

                    $rows = $query->whereIn('pmieducar.aluno.cod_aluno', $faltantes)->get();

                    foreach ($rows as $row) {
                        $encontrados[(int) $row->id] = (string) $row->nome;
                        $cpfs[(int) $row->id] = static::formatarCpf($row->cpf ?? null);
                    }
                }
            } catch (\Throwable $e) {
                // Degradação elegante caso o schema não esteja presente
            }

            foreach ($faltantes as $id) {
                static::$cacheNomes[$id] = $encontrados[$id] ?? ('Aluno #' . $id);
                static::$cacheCpfs[$id] = $cpfs[$id] ?? '';
            }
        }

        $resultado = [];
        foreach ($ids as $id) {
            $resultado[$id] = static::$cacheNomes[$id] ?? ('Aluno #' . $id);
        }

        return $resultado;
    }

    /**
     * Obtém o nome de um aluno individualmente do cache ou busca
     */
    public static function nome(?int $alunoId): string
    {
        if (empty($alunoId)) {
            return '-';
        }

        if (isset(static::$cacheNomes[$alunoId])) {
            return static::$cacheNomes[$alunoId];
        }

        $res = static::nomes([$alunoId]);
        return $res[$alunoId] ?? ('Aluno #' . $alunoId);
    }

    /**
     * Obtém o CPF formatado de um aluno
     */
    public static function cpf(?int $alunoId): string
    {
        if (empty($alunoId)) {
            return '';
        }

        if (!isset(static::$cacheCpfs[$alunoId])) {
            static::nomes([$alunoId]);
        }

        return static::$cacheCpfs[$alunoId] ?? '';
    }

    /**
     * Retorna a exibição padronizada do aluno: "Nome do aluno(CPF)" ou apenas o nome / fallback
     */
    public static function nomeComCpf(?int $alunoId): string
    {
        if (empty($alunoId)) {
            return '-';
        }

        $nome = static::nome($alunoId);
        $cpf = static::cpf($alunoId);

        if (!empty($cpf)) {
            return "{$nome}({$cpf})";
        }

        return $nome;
    }

    /**
     * Resolve nomes de turmas em lote com uma única consulta SQL
     *
     * @param array $alunoIds
     * @param int|null $ano
     * @return array<int, string>
     */
    public static function turmas(array $alunoIds, ?int $ano = null): array
    {
        $ids = array_values(array_filter(array_unique(array_map('intval', $alunoIds)), fn($id) => $id > 0));
        if (empty($ids)) {
            return [];
        }

        $sufixo = $ano ? (string) $ano : 'any';
        $faltantes = [];
        foreach ($ids as $id) {
            $key = $id . '_' . $sufixo;
            if (!isset(static::$cacheTurmas[$key])) {
                $faltantes[] = $id;
            }
        }

        if (!empty($faltantes)) {
            $encontrados = [];
            try {
                if (Schema::hasTable('pmieducar.matricula') && Schema::hasTable('pmieducar.matricula_turma') && Schema::hasTable('pmieducar.turma')) {
                    $query = DB::table('pmieducar.matricula')
                        ->join('pmieducar.matricula_turma', 'pmieducar.matricula_turma.ref_cod_matricula', '=', 'pmieducar.matricula.cod_matricula')
                        ->join('pmieducar.turma', 'pmieducar.turma.cod_turma', '=', 'pmieducar.matricula_turma.ref_cod_turma')
                        ->whereIn('pmieducar.matricula.ref_cod_aluno', $faltantes)
                        ->where('pmieducar.matricula.ativo', 1);

                    if ($ano) {
                        $query->where('pmieducar.matricula.ano', $ano);
                    }

                    $rows = $query->select('pmieducar.matricula.ref_cod_aluno as aluno_id', 'pmieducar.turma.nm_turma as turma_nome')->get();
                    foreach ($rows as $row) {
                        $encontrados[(int) $row->aluno_id] = (string) $row->turma_nome;
                    }
                }
            } catch (\Throwable $e) {
                // Degradação elegante caso o schema não esteja presente
            }

            foreach ($faltantes as $id) {
                $key = $id . '_' . $sufixo;
                static::$cacheTurmas[$key] = $encontrados[$id] ?? '-';
            }
        }

        $resultado = [];
        foreach ($ids as $id) {
            $key = $id . '_' . $sufixo;
            $resultado[$id] = static::$cacheTurmas[$key] ?? '-';
        }

        return $resultado;
    }

    /**
     * Obtém o nome da turma de um aluno individualmente do cache ou busca
     */
    public static function turma(?int $alunoId, ?int $ano = null): string
    {
        if (empty($alunoId)) {
            return '-';
        }

        $sufixo = $ano ? (string) $ano : 'any';
        $key = $alunoId . '_' . $sufixo;

        if (isset(static::$cacheTurmas[$key])) {
            return static::$cacheTurmas[$key];
        }

        $res = static::turmas([$alunoId], $ano);
        return $res[$alunoId] ?? '-';
    }

    /**
     * Hidrata o cache de nomes e turmas para uma coleção ou array de títulos
     */
    public static function carregarParaTitulos(iterable $titulos, ?int $ano = null): void
    {
        $alunoIds = [];
        foreach ($titulos as $titulo) {
            if (!empty($titulo->aluno_id)) {
                $alunoIds[] = (int) $titulo->aluno_id;
            }
        }

        if (!empty($alunoIds)) {
            static::nomes($alunoIds);
            static::turmas($alunoIds, $ano);
        }
    }

    /**
     * Resolve todos os dados acadêmicos do aluno (Nome, CPF, Escola, Turma e Turno)
     *
     * @param int|null $alunoId
     * @param int|null $ano
     * @return array{nome: string, cpf: string, escola: string, turma: string, turno: string}
     */
    public static function dadosAcademicos(?int $alunoId, ?int $ano = null): array
    {
        if (empty($alunoId)) {
            return [
                'nome'   => '-',
                'cpf'    => '',
                'escola' => '-',
                'turma'  => '-',
                'turno'  => '-',
            ];
        }

        $nome = static::nome($alunoId);
        $cpf  = static::cpf($alunoId);
        $escola = '-';
        $turma  = '-';
        $turno  = '-';

        try {
            if (Schema::hasTable('pmieducar.matricula')) {
                $query = DB::table('pmieducar.matricula')
                    ->where('pmieducar.matricula.ref_cod_aluno', $alunoId)
                    ->where('pmieducar.matricula.ativo', 1);

                if (Schema::hasTable('pmieducar.matricula_turma') && Schema::hasTable('pmieducar.turma')) {
                    $query->leftJoin('pmieducar.matricula_turma', 'pmieducar.matricula_turma.ref_cod_matricula', '=', 'pmieducar.matricula.cod_matricula')
                          ->leftJoin('pmieducar.turma', 'pmieducar.turma.cod_turma', '=', 'pmieducar.matricula_turma.ref_cod_turma');

                    if (Schema::hasTable('pmieducar.turma_turno')) {
                        $query->leftJoin('pmieducar.turma_turno', 'pmieducar.turma_turno.id', '=', 'pmieducar.turma.turma_turno_id');
                    }
                }

                if (Schema::hasTable('pmieducar.escola') && Schema::hasTable('cadastro.pessoa')) {
                    $query->leftJoin('pmieducar.escola', 'pmieducar.escola.cod_escola', '=', 'pmieducar.matricula.ref_ref_cod_escola')
                          ->leftJoin('cadastro.pessoa as escola_pessoa', 'escola_pessoa.idpes', '=', 'pmieducar.escola.ref_idpes');
                }

                if ($ano) {
                    $query->where('pmieducar.matricula.ano', $ano);
                }

                $row = $query->orderByDesc('pmieducar.matricula.ano')
                    ->select([
                        Schema::hasTable('cadastro.pessoa') ? 'escola_pessoa.nome as escola_nome' : DB::raw("NULL as escola_nome"),
                        Schema::hasTable('pmieducar.turma') ? 'pmieducar.turma.nm_turma as turma_nome' : DB::raw("NULL as turma_nome"),
                        Schema::hasTable('pmieducar.turma_turno') ? 'pmieducar.turma_turno.nome as turno_nome' : DB::raw("NULL as turno_nome"),
                    ])->first();

                if ($row) {
                    $escola = !empty($row->escola_nome) ? (string) $row->escola_nome : $escola;
                    $turma  = !empty($row->turma_nome) ? (string) $row->turma_nome : $turma;
                    $turno  = !empty($row->turno_nome) ? (string) $row->turno_nome : $turno;
                }
            }
        } catch (\Throwable $e) {
            // Degradação elegante caso tabelas ou colunas não estejam presentes
        }

        return [
            'nome'   => $nome,
            'cpf'    => $cpf,
            'escola' => $escola,
            'turma'  => $turma,
            'turno'  => $turno,
        ];
    }

    /**
     * Monta texto rico e padronizado com Aluno, Escola, Turma e Turno para envio ao gateway Asaas
     * Formato respeita o limite de 500 caracteres da API do Asaas.
     */
    public static function textoDescricaoAsaas(?int $alunoId, ?int $ano = null, ?string $prefixo = null): string
    {
        $dados = static::dadosAcademicos($alunoId, $ano);

        $partes = [];
        if (!empty($prefixo)) {
            $partes[] = $prefixo;
        }

        $partes[] = "Aluno: {$dados['nome']}";
        if (!empty($dados['escola']) && $dados['escola'] !== '-') {
            $partes[] = "Escola: {$dados['escola']}";
        }
        if (!empty($dados['turma']) && $dados['turma'] !== '-') {
            $partes[] = "Turma: {$dados['turma']}";
        }
        if (!empty($dados['turno']) && $dados['turno'] !== '-') {
            $partes[] = "Turno: {$dados['turno']}";
        }

        $texto = implode(' | ', $partes);

        if (mb_strlen($texto) > 490) {
            $texto = mb_substr($texto, 0, 487) . '...';
        }

        return $texto;
    }
}
