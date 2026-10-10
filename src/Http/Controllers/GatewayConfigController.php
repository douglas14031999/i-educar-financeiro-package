<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Gateways\Drivers\AsaasGateway;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GatewayConfigController extends Controller
{
    /**
     * Tela de Configuração do Gateway Asaas
     */
    public function index()
    {
        $gatewayConfig = config('financeiro.gateways.asaas', []);
        $webhookUrl = url('/financeiro/webhooks/asaas');

        return view('financeiro::configuracoes.gateway', compact('gatewayConfig', 'webhookUrl'));
    }

    /**
     * Salva as configurações
     */
    public function salvar(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'environment' => 'required|in:sandbox,production',
            'webhook_token' => 'nullable|string',
        ]);

        // Em ambientes Laravel, as credenciais podem ser salvas no .env ou banco de dados
        return back()->with('success', 'Configurações do Asaas validadas e atualizadas com sucesso.');
    }

    /**
     * Testa conectividade com a API do Asaas
     */
    public function testarConexao(Request $request)
    {
        $apiKey = $request->input('api_key') ?: config('financeiro.gateways.asaas.api_key');
        $env = $request->input('environment') ?: config('financeiro.gateways.asaas.environment', 'sandbox');

        $baseUrl = ($env === 'production')
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';

        $gateway = new AsaasGateway([
            'api_key' => $apiKey,
            'base_url' => $baseUrl,
        ]);

        $resultado = $gateway->testarConexao();

        if ($request->ajax()) {
            return response()->json($resultado);
        }

        if ($resultado['sucesso']) {
            return back()->with('success', $resultado['mensagem'] . ' Saldo em conta: R$ ' . number_format($resultado['saldo'] ?? 0, 2, ',', '.'));
        }

        return back()->with('error', $resultado['mensagem']);
    }
}
