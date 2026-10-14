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

        if (!empty($expectedToken)) {
            if ($receivedToken !== $expectedToken) {
                Log::warning('Webhook Asaas rejeitado: token inválido.', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } else {
            // Se o token ainda não estiver configurado no banco mas veio na requisição,
            // sincroniza com o banco para garantir a autenticação dos próximos callbacks
            if (!empty($receivedToken) && $dbConfig) {
                $dbConfig->update(['webhook_token' => $receivedToken]);
                Log::info('Webhook Asaas: token recebido sincronizado com a configuração do gateway.');
            } else {
                Log::notice('Webhook Asaas recebido sem autenticação de token configurada.');
            }
        }

        $payload     = $request->all();
        $eventType   = $payload['event'] ?? 'UNKNOWN';
        $paymentData = $payload['payment'] ?? [];
        $paymentId   = $paymentData['id'] ?? ($payload['id'] ?? null);

        // A09 — Chave de idempotência usa o ID externo do evento Asaas.
        // O ID do evento garante unicidade por evento individual.
        // Não usamos tipo+pagamento porque PAYMENT_CONFIRMED e PAYMENT_RECEIVED
        // são eventos distintos para o mesmo pagamento e devem ser tratados uma vez cada.
        $asaasEventId = $payload['id'] ?? null; // ID único do evento no Asaas

        if (!$paymentId) {
            return response()->json(['error' => 'Identificador do pagamento n\u00e3o informado'], 400);
        }

        // Chave de idempotência: ID do evento externo é a fonte de verdade
        // Fallback para tipo+pagamento apenas quando o Asaas não envia 'id' do evento
        $eventKey = $asaasEventId
            ? 'asaas_evt_' . $asaasEventId
            : sprintf('asaas_%s_%s', $eventType, $paymentId);

        // Checagem de idempotência pelo ID do evento
        $eventoExistente = WebhookEvent::where('event_id', $eventKey)->first();
        if ($eventoExistente) {
            return response()->json([
                'status'  => 'already_processed',
                'message' => 'Evento j\u00e1 recebido e registrado anteriormente.',
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
