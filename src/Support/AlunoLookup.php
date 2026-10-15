<?php

namespace iEducar\Financeiro\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlunoLookup
{
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
        static::$cacheTurmas = [];
    }

    /**
     * Define manualmente um nome no cache (útil para testes unitários/mock)
     */
    public static function definirNome(int $alunoId, string $nome): void
    {
        static::$cacheNomes[$alunoId] = $nome;
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
     * Resolve nomes de alunos em lote com uma única consulta SQL
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
            try {
                if (Schema::hasTable('pmieducar.aluno') && Schema::hasTable('cadastro.pessoa')) {
                    $rows = DB::table('pmieducar.aluno')
                        ->join('cadastro.pessoa', 'cadastro.pessoa.idpes', '=', 'pmieducar.aluno.ref_idpes')
                        ->whereIn('pmieducar.aluno.cod_aluno', $faltantes)
                        ->select('pmieducar.aluno.cod_aluno as id', 'cadastro.pessoa.nome')
                        ->get();

                    foreach ($rows as $row) {
                        $encontrados[(int) $row->id] = (string) $row->nome;
                    }
                }
            } catch (\Throwable $e) {
                // Degradação elegante caso o schema não esteja presente
            }

            foreach ($faltantes as $id) {
                static::$cacheNomes[$id] = $encontrados[$id] ?? ('Aluno #' . $id);
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
}
