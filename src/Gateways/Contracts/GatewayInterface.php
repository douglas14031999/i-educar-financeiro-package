<?php

namespace iEducar\Financeiro\Gateways\Contracts;

use iEducar\Financeiro\Models\Titulo;

interface GatewayInterface
{
    /**
     * Identificador do gateway
     */
    public function getName(): string;

    /**
     * Cria ou emite cobrança para um título (Boleto/Pix)
     */
    public function criarCobranca(Titulo $titulo, array $opcoes = []): array;

    /**
     * Cancela uma cobrança no gateway
     */
    public function cancelarCobranca(string $gatewayChargeId): bool;

    /**
     * Consulta os dados e status da cobrança no gateway
     */
    public function consultarCobranca(string $gatewayChargeId): array;

    /**
     * Testa a conectividade com o gateway
     */
    public function testarConexao(): array;
}
