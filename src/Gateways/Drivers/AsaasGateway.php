<?php

namespace iEducar\Financeiro\Gateways\Drivers;

use Carbon\Carbon;
use Exception;
use iEducar\Financeiro\Gateways\Contracts\GatewayInterface;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsaasGateway implements GatewayInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->apiKey = $config['api_key'] ?? '';
        $this->baseUrl = rtrim($config['base_url'] ?? 'https://sandbox.asaas.com/api/v3', '/');
    }

    public function getName(): string
    {
        return 'asaas';
    }

    /**
     * Cria requisição HTTP autenticada para o Asaas
     */
    protected function client()
    {
        return Http::withHeaders([
            'access_token' => $this->apiKey,
            'User-Agent' => 'i-Educar-Financeiro/1.0',
            'Accept' => 'application/json',
        ])->timeout(25);
    }

    /**
     * Testa a conectividade com o Asaas consultando a rota de saldo
     */
    public function testarConexao(): array
    {
        if (empty($this->apiKey)) {
            return ['sucesso' => false, 'mensagem' => 'A API Key do Asaas não foi informada nas configurações.'];
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/finance/balance");

            if ($response->successful()) {
                $dados = $response->json();
                return [
                    'sucesso' => true,
                    'mensagem' => 'Conexão com o Asaas estabelecida com sucesso!',
                    'saldo' => $dados['balance'] ?? 0.00,
                ];
            }

            return [
                'sucesso' => false,
                'mensagem' => 'Erro retornado pelo Asaas: ' . ($response->json('errors.0.description') ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Falha de comunicação de rede com o Asaas: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cria ou localiza um cliente no Asaas pelo CPF/CNPJ
     */
    public function obterOuCriarCliente(Titulo $titulo): string
    {
        $resp = $titulo->responsavel;
        if (!$resp) {
            throw new Exception('O título não possui responsável financeiro associado.');
        }

        $cpfLimpo = preg_replace('/\D/', '', $resp->cpf_cnpj);

        // Busca cliente existente pelo CPF
        $buscaResponse = $this->client()->get("{$this->baseUrl}/customers", [
            'cpfCnpj' => $cpfLimpo,
        ]);

        if ($buscaResponse->successful()) {
            $clientes = $buscaResponse->json('data');
            if (!empty($clientes[0]['id'])) {
                return $clientes[0]['id'];
            }
        }

        // Se não existir, cadastra novo cliente no Asaas
        $criaResponse = $this->client()->post("{$this->baseUrl}/customers", [
            'name' => $resp->nome,
            'cpfCnpj' => $cpfLimpo,
            'email' => $resp->email,
            'mobilePhone' => preg_replace('/\D/', '', $resp->telefone_celular),
            'address' => $resp->logradouro,
            'addressNumber' => $resp->numero,
            'complement' => $resp->complemento,
            'province' => $resp->bairro,
            'postalCode' => preg_replace('/\D/', '', $resp->cep),
            'externalReference' => (string) $resp->id,
            'notificationDisabled' => false,
        ]);

        if ($criaResponse->successful() && !empty($criaResponse->json('id'))) {
            return $criaResponse->json('id');
        }

        $erro = $criaResponse->json('errors.0.description') ?? $criaResponse->body();
        throw new Exception("Falha ao cadastrar cliente no Asaas: {$erro}");
    }

    /**
     * Emite cobrança unificada (Boleto Híbrido com Pix no topo)
     */
    public function criarCobranca(Titulo $titulo, array $opcoes = []): array
    {
        $customerId = $this->obterOuCriarCliente($titulo);

        $vencimento = Carbon::parse($titulo->data_vencimento)->format('Y-m-d');
        if (Carbon::parse($titulo->data_vencimento)->isPast()) {
            $vencimento = Carbon::today()->addDays(3)->format('Y-m-d');
        }

        $payload = [
            'customer' => $customerId,
            'billingType' => 'UNDEFINED', // Permite Boleto, Pix e Cartão
            'value' => (float) $titulo->valor_saldo,
            'dueDate' => $vencimento,
            'description' => sprintf('Mensalidade Aluno #%d - %s (%s)', $titulo->aluno_id, $titulo->observacoes ?? 'Mensalidade Escolar', $titulo->competencia),
            'externalReference' => (string) $titulo->id,
            'postalService' => false,
            'fine' => [
                'value' => (float) config('financeiro.politicas.multa_percentual', 2.00),
                'type' => 'PERCENTAGE',
            ],
            'interest' => [
                'value' => (float) config('financeiro.politicas.juros_mensal_percentual', 1.00),
            ],
        ];

        $response = $this->client()->post("{$this->baseUrl}/payments", $payload);

        if (!$response->successful()) {
            $erro = $response->json('errors.0.description') ?? $response->body();
            throw new Exception("Falha ao emitir cobrança no Asaas: {$erro}");
        }

        $dados = $response->json();
        $chargeId = $dados['id'];

        // Consulta QR Code Pix dinâmico
        $pixQrCode = null;
        $pixCopiaCola = null;
        try {
            $pixResponse = $this->client()->get("{$this->baseUrl}/payments/{$chargeId}/pixQrCode");
            if ($pixResponse->successful()) {
                $pixQrCode = $pixResponse->json('encodedImage');
                $pixCopiaCola = $pixResponse->json('payload');
            }
        } catch (\Throwable $e) {
            Log::warning("Não foi possível gerar Pix QR Code imediato para {$chargeId}: " . $e->getMessage());
        }

        // Salva ou atualiza registro de cobrança do gateway
        $cobranca = CobrancaGateway::updateOrCreate([
            'titulo_id' => $titulo->id,
        ], [
            'gateway' => 'asaas',
            'gateway_charge_id' => $chargeId,
            'status_externo' => $dados['status'] ?? 'PENDING',
            'linha_digitavel' => $dados['identificationField'] ?? null,
            'codigo_barras' => $dados['barCode'] ?? null,
            'link_boleto_pdf' => $dados['bankSlipUrl'] ?? null,
            'link_pagamento' => $dados['invoiceUrl'] ?? null,
            'pix_copia_cola' => $pixCopiaCola,
            'pix_qr_code_base64' => $pixQrCode,
            'payload_requisicao' => $payload,
            'payload_resposta' => $dados,
        ]);

        return [
            'cobranca_id' => $cobranca->id,
            'gateway_charge_id' => $chargeId,
            'link_boleto' => $dados['bankSlipUrl'] ?? null,
            'link_pagamento' => $dados['invoiceUrl'] ?? null,
            'pix_copia_cola' => $pixCopiaCola,
            'linha_digitavel' => $dados['identificationField'] ?? null,
        ];
    }

    /**
     * Consulta status atualizado no Asaas
     */
    public function consultarCobranca(string $gatewayChargeId): array
    {
        $response = $this->client()->get("{$this->baseUrl}/payments/{$gatewayChargeId}");
        if ($response->successful()) {
            return $response->json();
        }
        return [];
    }

    /**
     * Cancela cobrança no Asaas
     */
    public function cancelarCobranca(string $gatewayChargeId): bool
    {
        $response = $this->client()->delete("{$this->baseUrl}/payments/{$gatewayChargeId}");
        return $response->successful();
    }
}
