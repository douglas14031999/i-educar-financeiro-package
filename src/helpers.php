<?php

use iEducar\Financeiro\Http\Middleware\AutorizarProcessoFinanceiro;

if (!function_exists('financeiro_can')) {
    /**
     * Verifica se o usuário autenticado possui permissão para um processo financeiro
     *
     * @param  string|int  $processo
     * @param  string      $nivel (visualiza|cadastra|exclui)
     * @return bool
     */
    function financeiro_can($processo, string $nivel = 'visualiza'): bool
    {
        return AutorizarProcessoFinanceiro::verificarPermissao(
            auth()->user(),
            $processo,
            $nivel
        );
    }
}
