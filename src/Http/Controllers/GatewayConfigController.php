<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Gateways\Drivers\AsaasGateway;
use iEducar\Financeiro\Models\GatewayConfiguracao;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class GatewayConfigController extends Controller
{
    /**
     * Tela de Configuração do Gateway Asaas
     *
     * BUG-012: A chave bruta nunca é enviada ao navegador; apenas a máscara.
     */
    public function index()
    {
        $dbConfig = GatewayConfiguracao::where('gateway', 'asaas')->first();

        $gatewayConfig = [
            'api_key_mascarada' => $dbConfig?->api_key_mascarada ?? '',
            'has_api_key'       => !empty($dbConfig?->api_key),
            'environment'       => $dbConfig?->environment
                ?? config('financeiro.gateways.asaas.environment', 'sandbox'),
            'webhook_token'     => $dbConfig?->webhook_token
                ?? config('financeiro.gateways.asaas.webhook_token', ''),
        ];

        $webhookUrl = url('/financeiro/webhooks/asaas');

        return view('financeiro::configuracoes.gateway', compact('gatewayConfig', 'webhookUrl'));
    }

    /**
     * Salva as configurações no banco de dados (BUG-012: Criptografado com APP_KEY)
     */
    public function salvar(Request $request)
    {
        $dbConfig = GatewayConfiguracao::where('gateway', 'asaas')->first();

        $rules = [
            'environment'   => 'required|in:sandbox,production',
            'webhook_token' => 'nullable|string|min:16',
        ];

        // Se já existe chave cadastrada, o campo api_key é opcional
        if (!$dbConfig || empty($dbConfig->api_key)) {
            $rules['api_key'] = 'required|string|min:10';
        } else {
            $rules['api_key'] = 'nullable|string';
        }

        $request->validate($rules);

        $apiKeyInput = trim((string) $request->input('api_key', ''));

        // Se o usuário digitou uma nova chave válida (não máscara e não vazia)
        if (!empty($apiKeyInput) && !str_starts_with($apiKeyInput, '•••')) {
            $novaApiKey = $apiKeyInput;
        } else {
            $novaApiKey = $dbConfig?->api_key;
        }

        GatewayConfiguracao::updateOrCreate(
            ['gateway' => 'asaas'],
            [
                'api_key'       => $novaApiKey,
                'environment'   => $request->input('environment'),
                'webhook_token' => $request->input('webhook_token') ? trim($request->input('webhook_token')) : null,
                'ativo'         => true,
            ]
        );

        Log::info('Configurações do gateway Asaas atualizadas.', [
            'environment' => $request->input('environment'),
            'usuario_id'  => auth()->id(),
        ]);

        return back()->with('success', 'Configurações do Asaas salvas com sucesso e chave criptografada com segurança.');
    }

    /**
     * Testa conectividade com a API do Asaas usando as credenciais informadas ou salvas
     */
    public function testarConexao(Request $request)
    {
        $dbConfig = GatewayConfiguracao::where('gateway', 'asaas')->first();

        $apiKeyInput = trim((string) $request->input('api_key', ''));
        if (!empty($apiKeyInput) && !str_starts_with($apiKeyInput, '•••')) {
            $apiKey = $apiKeyInput;
        } else {
            $apiKey = $dbConfig?->api_key ?: config('financeiro.gateways.asaas.api_key');
        }

        $env = $request->input('environment')
            ?: ($dbConfig?->environment ?: config('financeiro.gateways.asaas.environment', 'sandbox'));

        $baseUrl = ($env === 'production')
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';

        $gateway   = new AsaasGateway(['api_key' => $apiKey, 'base_url' => $baseUrl]);
        $resultado = $gateway->testarConexao();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($resultado);
        }

        if ($resultado['sucesso']) {
            return back()->with(
                'success',
                $resultado['mensagem'] . ' Saldo em conta: R$ ' . number_format($resultado['saldo'] ?? 0, 2, ',', '.')
            );
        }

        return back()->with('error', $resultado['mensagem']);
    }

    /**
     * Registra ou atualiza automaticamente o Webhook no painel do Asaas
     */
    public function registrarWebhook(Request $request)
    {
        $dbConfig = GatewayConfiguracao::where('gateway', 'asaas')->first();

        $apiKeyInput = trim((string) $request->input('api_key', ''));
        if (!empty($apiKeyInput) && !str_starts_with($apiKeyInput, '•••')) {
            $apiKey = $apiKeyInput;
        } else {
            $apiKey = $dbConfig?->api_key ?: config('financeiro.gateways.asaas.api_key');
        }

        $token = $request->input('webhook_token')
            ?: ($dbConfig?->webhook_token ?: config('financeiro.gateways.asaas.webhook_token'));
        $env = $request->input('environment')
            ?: ($dbConfig?->environment ?: config('financeiro.gateways.asaas.environment', 'sandbox'));

        $baseUrl = ($env === 'production')
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';

        $gateway = new AsaasGateway(['api_key' => $apiKey, 'base_url' => $baseUrl]);
        $webhookUrl = url('/financeiro/webhooks/asaas');

        $resultado = $gateway->registrarWebhook($webhookUrl, (string) $token);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($resultado);
        }

        if ($resultado['sucesso']) {
            return back()->with('success', $resultado['mensagem']);
        }

        return back()->with('error', $resultado['mensagem']);
    }
}
