<?php

namespace iEducar\Financeiro\Http\Webhooks;

use iEducar\Financeiro\Jobs\ProcessarWebhookJob;
use iEducar\Financeiro\Models\GatewayConfiguracao;
use iEducar\Financeiro\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    /**
     * Recebe notificações HTTP de eventos disparadas pelo Asaas
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // A16 — Fonte de verdade em runtime: tabela financeiro.gateway_configuracoes
        $dbConfig = GatewayConfiguracao::paraGateway('asaas');
        $expectedToken = $dbConfig?->webhook_token ?: config('financeiro.gateways.asaas.webhook_token');
        $receivedToken = $request->header('asaas-access-token');

        if (empty($expectedToken)) {
            Log::error('Webhook Asaas rejeitado: nenhum webhook_token configurado no sistema.', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Webhook authentication not configured'], 401);
        }

        if (empty($receivedToken) || !hash_equals((string) $expectedToken, (string) $receivedToken)) {
            Log::warning('Webhook Asaas rejeitado: token ausente ou inválido.', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload      = $request->all();
        $eventType    = $payload['event'] ?? null;
        $asaasEventId = $payload['id'] ?? null;
        $paymentData  = $payload['payment'] ?? [];
        $paymentId    = $paymentData['id'] ?? ($payload['payment_id'] ?? ($payload['id'] ?? null));

        if (empty($eventType)) {
            return response()->json(['error' => 'Tipo de evento não informado'], 400);
        }

        if (!$paymentId) {
            return response()->json(['error' => 'Identificador do pagamento não informado'], 400);
        }

        // Chave de idempotência: ID do evento externo é a fonte de verdade
        // Fallback para tipo+pagamento apenas quando o Asaas não envia 'id' do evento
        $eventKey = $asaasEventId ?: sprintf('asaas_%s_%s', $eventType, $paymentId);

        // Checagem de idempotência pelo ID do evento
        $eventoExistente = WebhookEvent::where('event_id', $eventKey)->first();
        if ($eventoExistente) {
            return response()->json([
                'status'     => 'already_processed',
                'duplicated' => true,
                'message'    => 'Evento já recebido e registrado anteriormente.',
            ], 200);
        }

        // Persistência na tabela de auditoria antes de responder sucesso (RN53)
        $webhookEvent = WebhookEvent::create([
            'gateway'    => 'asaas',
            'event_id'   => $eventKey,
            'event_type' => $eventType,
            'payload'    => $payload,
            'processado' => false,
        ]);

        // Despacha o Job — falha no dispatch não deve impedir o HTTP 200
        // O evento já foi persistido; o Job pode ser reenfileirado manualmente se necessário
        try {
            ProcessarWebhookJob::dispatch($webhookEvent->id);
        } catch (\Throwable $e) {
            Log::error('Erro ao enfileirar ProcessarWebhookJob: ' . $e->getMessage(), [
                'webhook_event_id' => $webhookEvent->id,
            ]);
        }

        return response()->json([
            'status'   => 'received',
            'event_id' => $eventKey,
        ], 200);
    }
}
