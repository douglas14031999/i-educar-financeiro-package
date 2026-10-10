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

Route::group(['prefix' => 'financeiro/webhooks', 'as' => 'financeiro.webhooks.', 'middleware' => $apiMiddleware, 'namespace' => 'iEducar\Financeiro\Http\Webhooks'], function () {

    // Webhook Asaas (Notificações de Boleto, Pix e Cartão)
    Route::post('asaas', 'AsaasWebhookController@handle')->name('asaas');
});
