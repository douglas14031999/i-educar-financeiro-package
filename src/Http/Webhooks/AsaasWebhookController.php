<?php

namespace iEducar\Financeiro\Http\Webhooks;

use iEducar\Financeiro\Jobs\ProcessarWebhookJob;
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
        // 1. Validação do Token de Webhook se configurado
        $expectedToken = config('financeiro.gateways.asaas.webhook_token');
        if (!empty($expectedToken)) {
            $receivedToken = $request->header('asaas-access-token');
            if ($receivedToken !== $expectedToken) {
                Log::warning('Webhook Asaas rejeitado: token de segurança inválido.');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $payload = $request->all();
        $eventType = $payload['event'] ?? 'UNKNOWN';
        $paymentData = $payload['payment'] ?? [];
        $paymentId = $paymentData['id'] ?? ($payload['id'] ?? null);

        if (!$paymentId) {
            return response()->json(['error' => 'Identificador do pagamento não informado'], 400);
        }

        // Chave única de idempotência do evento
        $eventId = sprintf('asaas_%s_%s', $eventType, $paymentId);

        // 2. Checagem de Idempotência: impede qualquer processamento duplicado
        $eventoExistente = WebhookEvent::where('event_id', $eventId)->first();
        if ($eventoExistente) {
            return response()->json([
                'status' => 'already_processed',
                'message' => 'Evento já recebido e registrado anteriormente.',
            ], 200);
        }

        // 3. Persistência na tabela de auditoria
        $webhookEvent = WebhookEvent::create([
            'gateway' => 'asaas',
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payload' => $payload,
            'processado' => false,
        ]);

        // 4. Despacha Job Assíncrono de Processamento
        try {
            if (class_exists(ProcessarWebhookJob::class)) {
                ProcessarWebhookJob::dispatch($webhookEvent->id);
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao enfileirar job do webhook Asaas: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'received',
            'event_id' => $eventId,
        ], 200);
    }
}
