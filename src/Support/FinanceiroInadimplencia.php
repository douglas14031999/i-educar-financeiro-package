<?php

namespace iEducar\Financeiro\Support;

use iEducar\Financeiro\Services\InadimplenciaCheckService;

class FinanceiroInadimplencia
{
    /**
     * Ponto de consulta consultiva sobre inadimplência de aluno (Lei 9.870/99)
     * Usado para travar ou alertar a rematrícula no i-Educar.
     *
     * @param int $alunoId
     * @param int|null $escolaId
     * @param int|null $diasTolerancia
     * @return array{
     *     aluno_id: int,
     *     aluno_nome: string,
     *     inadimplente: bool,
     *     pode_rematricular: bool,
     *     total_debito: float,
     *     qtd_titulos_vencidos: int,
     *     maior_atraso_dias: int,
     *     fundamentacao_legal: string,
     *     titulos: \Illuminate\Support\Collection
     * }
     */
    public static function alunoInadimplente(int $alunoId, ?int $escolaId = null, ?int $diasTolerancia = null): array
    {
        $tolerancia = $diasTolerancia ?? (int) config('financeiro.politicas.dias_tolerancia', 0);
        $service = app(InadimplenciaCheckService::class);
        return $service->verificarAluno($alunoId, $escolaId, $tolerancia);
    }

    /**
     * Verifica débitos acumulados por responsável financeiro
     */
    public static function responsavelInadimplente(int $responsavelId): array
    {
        $service = app(InadimplenciaCheckService::class);
        return $service->verificarResponsavel($responsavelId);
    }
}
