<?php

namespace iEducar\Financeiro\Jobs;

use Carbon\Carbon;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\WebhookEvent;
use iEducar\Financeiro\Services\BaixaTituloService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    protected $webhookEventId;

    public function __construct(int $webhookEventId)
    {
        $this->webhookEventId = $webhookEventId;
    }

    /**
     * Processa a liquidação e reconciliação financeira do webhook
     *
     * @param BaixaTituloService $baixaService
     * @return void
     */
    public function handle(BaixaTituloService $baixaService)
    {
        $evento = WebhookEvent::find($this->webhookEventId);
        if (!$evento || $evento->processado) {
            return;
        }

        try {
            $payload     = $evento->payload;
            $eventType   = $evento->event_type;
            $paymentData = $payload['payment'] ?? [];
            $chargeId    = $paymentData['id'] ?? null;

            if (!$chargeId) {
                $evento->update(['erro' => 'Identificador da cobrança ausente no payload']);
                return;
            }

            $cobranca = CobrancaGateway::where('gateway_charge_id', $chargeId)->first();
            if (!$cobranca || !$cobranca->titulo) {
                $evento->update(['erro' => "Cobrança {$chargeId} não localizada no sistema local"]);
                return;
            }

            $titulo = $cobranca->titulo;

            if (in_array($eventType, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'])) {
                // A09 (complementar) — Idempotência financeira:
                // Se o título já foi liquidado por outro evento, sai sem erro.
                if ($titulo->isLiquidado()) {
                    Log::info("Webhook {$this->webhookEventId}: título #{$titulo->id} já liquidado. Ignorando.");
                    $evento->update(['processado' => true, 'processado_em' => Carbon::now()]);
                    return;
                }

                $valorPago     = (float) ($paymentData['value'] ?? $titulo->valor_saldo);
                $dataPagamento = Carbon::parse($paymentData['paymentDate'] ?? Carbon::today());
                $formaPagamento = strtolower($paymentData['billingType'] ?? 'pix');
                if ($formaPagamento === 'undefined') {
                    $formaPagamento = 'pix';
                }

                $baixaService->realizarBaixa($titulo, [
                    'valor_pago'              => $valorPago,
                    'forma_pagamento'         => $formaPagamento,
                    'data_pagamento'          => $dataPagamento,
                    'origem_baixa'            => 'webhook_asaas',
                    'valor_desconto_concedido' => (float) ($paymentData['discountValue'] ?? 0.00),
                    'valor_multa'             => (float) ($paymentData['fineValue'] ?? 0.00),
                    'valor_juros'             => (float) ($paymentData['interestValue'] ?? 0.00),
                ]);

                $cobranca->update(['status_externo' => 'RECEIVED']);

            } elseif ($eventType === 'PAYMENT_OVERDUE') {
                if ($titulo->status === 'aberto') {
                    $titulo->update(['status' => 'vencido']);
                }
                $cobranca->update(['status_externo' => 'OVERDUE']);
            } else {
                // A10 — Eventos desconhecidos ficam registrados sem processamento (RN59)
                Log::info("Webhook {$this->webhookEventId}: evento '{$eventType}' ignorado — sem handler definido.");
            }

            $evento->update([
                'processado'   => true,
                'processado_em' => Carbon::now(),
            ]);

        } catch (\Throwable $e) {
            // A10 — Log + relance: a fila de retentativas (backoff, max_attempts) funciona corretamente.
            // Não silenciar a exceção aqui era o bug original.
            Log::error("Erro ao processar webhook {$this->webhookEventId}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            $evento->update(['erro' => $e->getMessage()]);

            throw $e; // Permite que a fila tente novamente (backoff progressivo)
        }
    }
}
