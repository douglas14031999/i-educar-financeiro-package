<?php

namespace iEducar\Financeiro\Gateways\Contracts;

use iEducar\Financeiro\Models\Contrato;
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

    /**
     * Cria um parcelamento (installment) no Asaas para um contrato inteiro.
     * Retorna o installment_id gerado pelo gateway.
     *
     * POST /v3/installments
     */
    public function criarInstallment(Contrato $contrato, array $titulos): array;

    /**
     * Obtém a URL pública do carnê PDF de um installment no Asaas.
     *
     * GET /v3/installments/{id}/paymentBook
     */
    public function gerarCarneUrl(string $installmentId): ?string;
}
