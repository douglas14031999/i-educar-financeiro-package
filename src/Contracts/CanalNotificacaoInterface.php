<?php

namespace iEducar\Financeiro\Contracts;

use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\Titulo;

interface CanalNotificacaoInterface
{
    /**
     * Envia notificação da régua de cobrança
     *
     * @param ReguaCobrancaRegra $regra
     * @param Titulo $titulo
     * @param string $destinatario
     * @param string $mensagem
     * @return array{0: bool, 1: string|null} [sucesso, erro]
     */
    public function enviar(ReguaCobrancaRegra $regra, Titulo $titulo, string $destinatario, string $mensagem): array;
}
