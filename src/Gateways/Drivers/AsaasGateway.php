<?php

namespace iEducar\Financeiro\Gateways\Drivers;

use Carbon\Carbon;
use Exception;
use iEducar\Financeiro\Gateways\Contracts\GatewayInterface;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Support\AlunoLookup;
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

        $cpfLimpo = preg_replace('/\D/', '', (string) $resp->cpf_cnpj);

        if (empty($cpfLimpo)) {
            throw new Exception('O responsável financeiro não possui CPF/CNPJ cadastrado.');
        }

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

        // Monta payload — só inclui campos opcionais quando não-vazios
        $phone      = preg_replace('/\D/', '', (string) ($resp->telefone_celular ?? $resp->telefone ?? ''));
        $postalCode = preg_replace('/\D/', '', (string) ($resp->cep ?? ''));

        $payload = array_filter([
            'name'                 => $resp->nome,
            'cpfCnpj'              => $cpfLimpo,
            'email'                => $resp->email ?: null,
            'mobilePhone'          => $phone ?: null,
            'address'              => $resp->logradouro ?: null,
            'addressNumber'        => $resp->numero ?: null,
            'complement'           => $resp->complemento ?: null,
            'province'             => $resp->bairro ?: null,
            'postalCode'           => $postalCode ?: null,
            'externalReference'    => (string) $resp->id,
            'notificationDisabled' => false,
        ], fn($v) => $v !== null && $v !== '');

        $criaResponse = $this->client()->post("{$this->baseUrl}/customers", $payload);

        if ($criaResponse->successful() && !empty($criaResponse->json('id'))) {
            return $criaResponse->json('id');
        }

        $erroDesc = $criaResponse->json('errors.0.description')
            ?? $criaResponse->json('errors.0.code')
            ?? $criaResponse->body();

        Log::error('AsaasGateway::obterOuCriarCliente falhou', [
            'status'    => $criaResponse->status(),
            'response'  => $criaResponse->body(),
            'payload'   => $payload,
            'responsavel_id' => $resp->id,
        ]);

        throw new Exception("Falha ao cadastrar cliente no Asaas (HTTP {$criaResponse->status()}): {$erroDesc}");
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

        $anoLetivo = $titulo->contrato?->ano_letivo ?? (int) date('Y');
        $prefixoDesc = $titulo->contrato?->numero_contrato
            ? "Contrato {$titulo->contrato->numero_contrato}"
            : ($titulo->observacoes ?? 'Mensalidade Escolar');
        $descricaoCompleta = AlunoLookup::textoDescricaoAsaas($titulo->aluno_id, $anoLetivo, $prefixoDesc);

        $payload = [
            'customer' => $customerId,
            'billingType' => 'UNDEFINED', // Permite Boleto, Pix e Cartão
            'value' => (float) $titulo->valor_saldo,
            'dueDate' => $vencimento,
            'description' => $descricaoCompleta,
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

        // Consulta Linha Digitável e Código de Barras do Boleto
        $linhaDigitavel = $dados['identificationField'] ?? null;
        $codigoBarras   = $dados['barCode'] ?? null;
        try {
            $identResponse = $this->client()->get("{$this->baseUrl}/payments/{$chargeId}/identificationField");
            if ($identResponse->successful()) {
                $linhaDigitavel = $identResponse->json('identificationField') ?? $linhaDigitavel;
                $codigoBarras   = $identResponse->json('barCode') ?? $codigoBarras;
            }
        } catch (\Throwable $e) {
            Log::warning("Não foi possível consultar linha digitável para {$chargeId}: " . $e->getMessage());
        }

        // Consulta QR Code Pix dinâmico (com retry leve caso o Asaas ainda esteja gerando)
        $pixQrCode = null;
        $pixCopiaCola = null;
        for ($tentativa = 1; $tentativa <= 3; $tentativa++) {
            try {
                $pixResponse = $this->client()->get("{$this->baseUrl}/payments/{$chargeId}/pixQrCode");
                if ($pixResponse->successful() && !empty($pixResponse->json('payload'))) {
                    $pixQrCode = $pixResponse->json('encodedImage');
                    $pixCopiaCola = $pixResponse->json('payload');
                    break;
                }
            } catch (\Throwable $e) {
                Log::warning("Tentativa {$tentativa} Pix QR Code para {$chargeId}: " . $e->getMessage());
            }
            if ($tentativa < 3) {
                usleep(300000); // 300ms
            }
        }

        // Salva ou atualiza registro de cobrança do gateway
        $cobranca = CobrancaGateway::updateOrCreate([
            'titulo_id' => $titulo->id,
        ], [
            'gateway' => 'asaas',
            'contrato_id' => $titulo->contrato_id,
            'gateway_charge_id' => $chargeId,
            'status_externo' => $dados['status'] ?? 'PENDING',
            'linha_digitavel' => $linhaDigitavel,
            'codigo_barras' => $codigoBarras,
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
            'pix_qr_code_base64' => $pixQrCode,
            'linha_digitavel' => $linhaDigitavel,
            'codigo_barras' => $codigoBarras,
        ];
    }

    /**
     * Consulta status atualizado no Asaas
     */
    public function consultarCobranca(string $gatewayChargeId): array
    {
        $response = $this->client()->get("{$this->baseUrl}/payments/{$gatewayChargeId}");
        if ($response->successful()) {
            $dados = $response->json();

            // Busca linha digitável e código de barras se não presentes
            try {
                $ident = $this->client()->get("{$this->baseUrl}/payments/{$gatewayChargeId}/identificationField");
                if ($ident->successful()) {
                    $dados['identificationField'] = $ident->json('identificationField');
                    $dados['barCode'] = $ident->json('barCode');
                }
            } catch (\Throwable $e) {
                // Silencia
            }

            // Busca Pix QR Code se não presente
            try {
                $pix = $this->client()->get("{$this->baseUrl}/payments/{$gatewayChargeId}/pixQrCode");
                if ($pix->successful() && !empty($pix->json('payload'))) {
                    $dados['pixQrCode'] = $pix->json('encodedImage');
                    $dados['pixCopiaCola'] = $pix->json('payload');
                }
            } catch (\Throwable $e) {
                // Silencia
            }

            return $dados;
        }
        return [];
    }

    /**
     * Cancela cobrança no Asaas de forma idempotente (404 é considerado sucesso)
     */
    public function cancelarCobranca(string $gatewayChargeId): bool
    {
        try {
            $response = $this->client()->delete("{$this->baseUrl}/payments/{$gatewayChargeId}");
            return $response->successful() || $response->status() === 404;
        } catch (\Throwable $e) {
            Log::error("Erro ao cancelar cobrança Asaas {$gatewayChargeId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancela um parcelamento completo (carnê) no Asaas
     */
    public function cancelarParcelamento(string $installmentId): bool
    {
        try {
            $response = $this->client()->delete("{$this->baseUrl}/installments/{$installmentId}");
            return $response->successful() || $response->status() === 404;
        } catch (\Throwable $e) {
            Log::error("Erro ao cancelar parcelamento Asaas {$installmentId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista todas as cobranças individuais geradas por um parcelamento no Asaas
     */
    public function listarPagamentosDoParcelamento(string $installmentId): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/installments/{$installmentId}/payments?limit=100");
            if ($response->successful()) {
                $dados = $response->json('data') ?? [];
                if (!empty($dados)) {
                    return $dados;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("AsaasGateway: tentativa de listagem via /installments/{$installmentId}/payments falhou: " . $e->getMessage());
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/payments?installment={$installmentId}&limit=100");
            if ($response->successful()) {
                return $response->json('data') ?? [];
            }
        } catch (\Throwable $e) {
            Log::error("AsaasGateway: erro ao listar pagamentos do parcelamento {$installmentId}: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Baixa o conteúdo binário do PDF do carnê oficial gerado pelo Asaas
     */
    public function baixarCarnePdf(string $installmentId): ?string
    {
        try {
            $url = "{$this->baseUrl}/installments/{$installmentId}/paymentBook";
            $response = $this->client()->timeout(30)->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable $e) {
            Log::error("Erro ao baixar PDF do carnê {$installmentId}: " . $e->getMessage());
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Carnê / Parcelamento via Asaas Installments
    // -------------------------------------------------------------------------

    /**
     * Cria um parcelamento (installment) no Asaas a partir de todos os títulos
     * de um contrato. Usa POST /v3/installments.
     *
     * O Asaas cria N cobranças internamente (uma por parcela) e associa a um
     * installment_id que permite acessar o carnê PDF via /paymentBook.
     *
     * @param  Contrato  $contrato  O contrato financeiro com os dados do aluno
     * @param  array     $titulos   Coleção/array de \iEducar\Financeiro\Models\Titulo
     * @return array     ['installment_id', 'carne_url', 'total_parcelas']
     * @throws Exception
     */
    public function criarInstallment(Contrato $contrato, array $titulos): array
    {
        if (empty($titulos)) {
            throw new Exception('Nenhum título disponível para gerar o carnê no Asaas.');
        }

        // Obtém ou cria o cliente Asaas a partir do primeiro título
        $primeiroTitulo = reset($titulos);
        if (!($primeiroTitulo instanceof Titulo)) {
            throw new Exception('Os títulos fornecidos devem ser instâncias de Titulo.');
        }

        $customerId = $this->obterOuCriarCliente($primeiroTitulo);

        // Ordena os títulos por data de vencimento
        usort($titulos, fn($a, $b) => strcmp((string) $a->data_vencimento, (string) $b->data_vencimento));

        // Calcula valor total e numero de parcelas
        $totalParcelas = count($titulos);
        $valorTotal    = array_sum(array_map(fn($t) => (float) $t->valor_saldo, $titulos));

        // Usa o vencimento do primeiro título como data de referência
        $primeiroVencimento = Carbon::parse($primeiroTitulo->data_vencimento);
        if ($primeiroVencimento->isPast()) {
            $primeiroVencimento = Carbon::today()->addDays(3);
        }

        $payload = [
            'customer'              => $customerId,
            'billingType'           => 'BOLETO',
            'value'                 => round($valorTotal / $totalParcelas, 2), // valor por parcela
            'totalValue'            => round($valorTotal, 2),
            'installmentCount'      => $totalParcelas,
            'dueDate'               => $primeiroVencimento->format('Y-m-d'),
            'description'           => AlunoLookup::textoDescricaoAsaas(
                $contrato->aluno_id,
                $contrato->ano_letivo,
                "Carnê Mensalidades - {$contrato->numero_contrato}"
            ),
            'paymentExternalReference' => 'CTR-' . $contrato->id,
            'postalService'         => false,
            'fine'                  => [
                'value' => (float) config('financeiro.politicas.multa_percentual', 2.00),
                'type'  => 'PERCENTAGE',
            ],
            'interest'              => [
                'value' => (float) config('financeiro.politicas.juros_mensal_percentual', 1.00),
            ],
        ];

        $response = $this->client()->post("{$this->baseUrl}/installments", $payload);

        if (!$response->successful()) {
            $erro = $response->json('errors.0.description') ?? $response->body();
            Log::error('AsaasGateway::criarInstallment falhou', [
                'status'      => $response->status(),
                'response'    => $response->body(),
                'contrato_id' => $contrato->id,
            ]);
            throw new Exception("Falha ao criar parcelamento no Asaas (HTTP {$response->status()}): {$erro}");
        }

        $dados         = $response->json();
        $installmentId = $dados['id'] ?? null;

        if (empty($installmentId)) {
            throw new Exception('Asaas não retornou o ID do parcelamento criado.');
        }

        // Obtém a URL do carnê PDF
        $carneUrl = $this->gerarCarneUrl($installmentId);

        // Lista as cobranças individuais geradas para o parcelamento
        $payments = $this->listarPagamentosDoParcelamento($installmentId);

        Log::info("AsaasGateway::criarInstallment — installment {$installmentId} criado para contrato {$contrato->numero_contrato}", [
            'carne_url'      => $carneUrl,
            'total_parcelas' => $totalParcelas,
            'total_payments' => count($payments),
        ]);

        return [
            'installment_id'   => $installmentId,
            'carne_url'        => $carneUrl,
            'total_parcelas'   => $totalParcelas,
            'valor_total'      => $valorTotal,
            'payments'         => $payments,
            'payload_resposta' => $dados,
        ];
    }

    /**
     * Retorna a URL pública do carnê PDF de um installment no Asaas.
     *
     * GET /v3/installments/{id}/paymentBook
     *
     * O Asaas retorna um redirect (302) ou binário PDF. Capturamos o header
     * Location ou retornamos a URL direta da API.
     *
     * @param  string  $installmentId
     * @return string|null  URL do PDF do carnê, ou null se indisponível
     */
    public function gerarCarneUrl(string $installmentId): ?string
    {
        try {
            // Tenta obter a URL sem seguir redirect para capturar o header Location
            $url = "{$this->baseUrl}/installments/{$installmentId}/paymentBook";

            $response = Http::withHeaders([
                'access_token' => $this->apiKey,
                'User-Agent'   => 'i-Educar-Financeiro/1.0',
            ])->timeout(20)->withoutRedirecting()->get($url);

            // Se retornou redirect (302/301), o carnê está na URL de destino
            if (in_array($response->status(), [301, 302, 303, 307, 308])) {
                $location = $response->header('Location');
                if (!empty($location)) {
                    return $location;
                }
            }

            // Se retornou 200 com conteúdo PDF, retorna a URL da API diretamente
            // (o controller fará o streaming ou redirect)
            if ($response->successful()) {
                return $url;
            }

            Log::warning("AsaasGateway::gerarCarneUrl — status inesperado para installment {$installmentId}", [
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error("AsaasGateway::gerarCarneUrl — erro para installment {$installmentId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Registra ou atualiza as configurações de Webhook no Asaas (POST /webhook)
     */
    public function registrarWebhook(string $url, string $token, array $events = []): array
    {
        if (empty($this->apiKey)) {
            return ['sucesso' => false, 'mensagem' => 'A API Key do Asaas não foi informada.'];
        }

        try {
            $payload = [
                'name'        => 'i-Educar Webhook',
                'url'         => $url,
                'email'       => config('mail.from.address', 'financeiro@escola.local'),
                'enabled'     => true,
                'interrupted' => false,
                'apiVersion'  => 3,
                'authToken'   => $token,
                'sendType'    => 'SEQUENTIALLY',
                'events'      => !empty($events) ? $events : [
                    'PAYMENT_RECEIVED',
                    'PAYMENT_CONFIRMED',
                    'PAYMENT_OVERDUE',
                    'PAYMENT_DELETED',
                    'PAYMENT_RESTORED',
                    'PAYMENT_REFUNDED',
                    'PAYMENT_CHARGEBACK_REQUESTED',
                ],
            ];

            $response = $this->client()->post("{$this->baseUrl}/webhook", $payload);

            if ($response->successful()) {
                return [
                    'sucesso'  => true,
                    'mensagem' => 'Webhook registrado com sucesso no Asaas!',
                    'dados'    => $response->json(),
                ];
            }

            return [
                'sucesso'  => false,
                'mensagem' => 'Erro retornado pelo Asaas ao registrar webhook: ' . ($response->json('errors.0.description') ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            return [
                'sucesso'  => false,
                'mensagem' => 'Falha de comunicação ao registrar webhook no Asaas: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cria um Link de Pagamento no Asaas para o contrato (Cartão de crédito até 12x com repasse de juros)
     *
     * POST /v3/paymentLinks
     *
     * @param  Contrato $contrato
     * @param  float    $valor
     * @param  int      $maxParcelas (default: 12)
     * @param  int      $validadeDiasUteis (default: 5)
     * @return array    ['payment_link_id', 'payment_link_url', 'value', 'max_installments']
     * @throws Exception
     */
    public function criarPaymentLink(Contrato $contrato, float $valor, int $maxParcelas = 12, int $validadeDiasUteis = 5): array
    {
        $diasCorridos = max(3, (int) ceil($validadeDiasUteis * 1.4)); // 5 dias úteis ~ 7 dias corridos

        $payload = [
            'name'                 => sprintf('Anuidade Escolar - %s', $contrato->numero_contrato),
            'description'          => AlunoLookup::textoDescricaoAsaas(
                $contrato->aluno_id,
                $contrato->ano_letivo,
                "Contrato {$contrato->numero_contrato}"
            ),
            'billingType'          => 'CREDIT_CARD',
            'chargeType'           => 'INSTALLMENT',
            'value'                => round($valor, 2),
            'maxInstallmentCount'  => min(12, max(1, $maxParcelas)),
            'dueDateLimitDays'     => $diasCorridos,
            'notificationEnabled'  => true,
            'externalReference'    => 'CTR-' . $contrato->id,
        ];

        $response = $this->client()->post("{$this->baseUrl}/paymentLinks", $payload);

        if (!$response->successful()) {
            $erro = $response->json('errors.0.description') ?? $response->body();
            Log::error('AsaasGateway::criarPaymentLink falhou', [
                'status'      => $response->status(),
                'response'    => $response->body(),
                'contrato_id' => $contrato->id,
                'payload'     => $payload,
            ]);
            throw new Exception("Falha ao gerar link de pagamento no Asaas (HTTP {$response->status()}): {$erro}");
        }

        $dados = $response->json();
        $linkId = $dados['id'] ?? null;
        $url    = $dados['url'] ?? null;

        if (empty($linkId) || empty($url)) {
            throw new Exception('O Asaas não retornou os identificadores do Link de Pagamento.');
        }

        Log::info("AsaasGateway::criarPaymentLink gerado com sucesso: {$linkId} ({$url})");

        return [
            'payment_link_id'  => $linkId,
            'payment_link_url' => $url,
            'value'            => (float) ($dados['value'] ?? $valor),
            'max_installments' => (int) ($dados['maxInstallmentCount'] ?? $maxParcelas),
            'expira_em'        => Carbon::today()->addDays($diasCorridos)->endOfDay(),
        ];
    }

    /**
     * Consulta o QR Code dinâmico e o código copia-e-cola de uma cobrança Pix no Asaas
     *
     * GET /v3/payments/{id}/pixQrCode
     *
     * @param  string $gatewayChargeId
     * @return array  ['encodedImage', 'payload', 'expirationDate']
     */
    public function obterPixQrCode(string $gatewayChargeId): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/payments/{$gatewayChargeId}/pixQrCode");

            if ($response->successful()) {
                $dados = $response->json();
                return [
                    'encodedImage'   => $dados['encodedImage'] ?? null,
                    'payload'        => $dados['payload'] ?? null,
                    'expirationDate' => $dados['expirationDate'] ?? Carbon::now()->addHours(24)->toIso8601String(),
                ];
            }

            Log::warning("AsaasGateway::obterPixQrCode falhou para {$gatewayChargeId}: " . $response->body());
            return [
                'encodedImage'   => null,
                'payload'        => null,
                'expirationDate' => null,
            ];
        } catch (\Throwable $e) {
            Log::error("AsaasGateway::obterPixQrCode exceção para {$gatewayChargeId}: " . $e->getMessage());
            return [
                'encodedImage'   => null,
                'payload'        => null,
                'expirationDate' => null,
            ];
        }
    }
}

