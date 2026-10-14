<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de API e Webhooks do Módulo Financeiro
|--------------------------------------------------------------------------
| Rotas públicas destinadas ao recebimento de callbacks automatizados de
| gateways de pagamento. Não exigem sessão web ou token CSRF.
*/

$apiMiddleware = config('financeiro.api_middleware', ['api']);

// Suporta tanto /financeiro/webhooks/asaas (padrão) quanto /financeiro/webhook/asaas (legado/Asaas dashboard)
foreach (['financeiro/webhooks', 'financeiro/webhook'] as $index => $prefix) {
    $as = ($index === 0) ? 'financeiro.webhooks.' : 'financeiro.webhook.';

    Route::group(['prefix' => $prefix, 'as' => $as, 'middleware' => $apiMiddleware, 'namespace' => 'iEducar\Financeiro\Http\Webhooks'], function () {

        // Webhook Asaas (Notificações de Boleto, Pix e Cartão)
        Route::post('asaas', 'AsaasWebhookController@handle')->name('asaas');

        // Healthcheck / Monitoramento do Webhook Asaas (GET/HEAD para testes)
        Route::match(['get', 'head'], 'asaas', function () {
            return response()->json([
                'status'    => 'active',
                'gateway'   => 'asaas',
                'message'   => 'Endpoint de webhook Asaas operacional.',
                'timestamp' => now()->toIso8601String(),
            ]);
        })->name('asaas.status');
    });
}

