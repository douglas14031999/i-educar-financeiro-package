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
     */
    public function index()
    {
        // A16 — Carrega configurações do banco (tabela financeiro.gateway_configuracoes)
        // Não depende exclusivamente do arquivo config/financeiro.php, que é imutável em runtime
        $dbConfig = GatewayConfiguracao::where('gateway', 'asaas')->first();

        $gatewayConfig = [
            'api_key'       => $dbConfig?->api_key
                ?? config('financeiro.gateways.asaas.api_key', ''),
            'environment'   => $dbConfig?->environment
                ?? config('financeiro.gateways.asaas.environment', 'sandbox'),
            'webhook_token' => $dbConfig?->webhook_token
                ?? config('financeiro.gateways.asaas.webhook_token', ''),
        ];

        $webhookUrl = url('/financeiro/webhooks/asaas');

        return view('financeiro::configuracoes.gateway', compact('gatewayConfig', 'webhookUrl'));
    }

    /**
     * Salva as configurações no banco de dados (A16 — persistência real)
     */
    public function salvar(Request $request)
    {
        $request->validate([
            'api_key'       => 'required|string|min:10',
            'environment'   => 'required|in:sandbox,production',
            'webhook_token' => 'nullable|string|min:16',
        ]);

        // A16 — Persiste no banco de dados usando upsert por gateway
        // O config() em runtime NÃO é gravável; a fonte de verdade é a tabela gateway_configuracoes
        GatewayConfiguracao::updateOrCreate(
            ['gateway' => 'asaas'],
            [
                'api_key'       => trim($request->input('api_key')),
                'environment'   => $request->input('environment'),
                'webhook_token' => $request->input('webhook_token') ? trim($request->input('webhook_token')) : null,
                'ativo'         => true,
            ]
        );

        Log::info('Configurações do gateway Asaas atualizadas.', [
            'environment' => $request->input('environment'),
            'usuario_id'  => auth()->id(),
        ]);

        return back()->with('success', 'Configurações do Asaas salvas com sucesso no banco de dados.');
    }

    /**
     * Testa conectividade com a API do Asaas usando as credenciais informadas ou salvas
     */
    public function testarConexao(Request $request)
    {
        // Prioridade: campo do formulário > banco de dados > config file
        $dbConfig = GatewayConfiguracao::where('gateway', 'asaas')->first();

        $apiKey = $request->input('api_key')
            ?: ($dbConfig?->api_key ?: config('financeiro.gateways.asaas.api_key'));
        $env = $request->input('environment')
            ?: ($dbConfig?->environment ?: config('financeiro.gateways.asaas.environment', 'sandbox'));

        $baseUrl = ($env === 'production')
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';

        $gateway   = new AsaasGateway(['api_key' => $apiKey, 'base_url' => $baseUrl]);
        $resultado = $gateway->testarConexao();

        if ($request->ajax()) {
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
}
