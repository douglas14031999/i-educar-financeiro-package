<?php

namespace iEducar\Financeiro\Jobs;

use iEducar\Financeiro\Gateways\Drivers\AsaasGateway;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\GatewayConfiguracao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * P-03 / A24 — Cancela uma cobrança ativa no Asaas após cancelamento local do contrato.
 *
 * Feito via Job (não síncrono) para:
 *  - Não bloquear a request do usuário em caso de timeout da API externa.
 *  - Permitir retentativas automáticas em caso de falha.
 *  - Manter o cancelamento local mesmo se o gateway estiver temporariamente indisponível.
 */
class CancelarCobrancaGatewayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $backoff = 120; // 2 minutos entre retentativas

    public function __construct(
        private readonly int    $cobrancaGatewayId,
        private readonly string $motivo = 'Cancelamento de contrato'
    ) {}

    public function handle(): void
    {
        $cobranca = CobrancaGateway::find($this->cobrancaGatewayId);

        if (!$cobranca) {
            Log::warning("CancelarCobrancaGatewayJob: CobrancaGateway #{$this->cobrancaGatewayId} não encontrada.");
            return;
        }

        // Já cancelada externamente — idempotente
        if (in_array($cobranca->status_externo, ['CANCELLED', 'DELETED', 'REFUNDED'])) {
            Log::info("CancelarCobrancaGatewayJob: cobrança {$cobranca->gateway_charge_id} já cancelada externamente.");
            return;
        }

        $config = GatewayConfiguracao::paraGateway('asaas');

        if (!$config?->api_key) {
            Log::error("CancelarCobrancaGatewayJob: Asaas não configurado. Cobrança {$cobranca->gateway_charge_id} não cancelada.");
            // Não relança — sem configuração a retentativa nunca vai funcionar
            return;
        }

        $baseUrl = ($config->environment === 'production')
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';

        $gateway = new AsaasGateway([
            'api_key'  => $config->api_key,
            'base_url' => $baseUrl,
        ]);

        $sucesso = $gateway->cancelarCobranca($cobranca->gateway_charge_id);

        if ($sucesso) {
            $cobranca->update(['status_externo' => 'CANCELLED']);
            Log::info("CancelarCobrancaGatewayJob: cobrança {$cobranca->gateway_charge_id} cancelada no Asaas. Motivo: {$this->motivo}");
        } else {
            throw new \RuntimeException(
                "Falha ao cancelar cobrança {$cobranca->gateway_charge_id} no Asaas. Tentativa {$this->attempts()}/{$this->tries}."
            );
        }
    }

    /**
     * Se esgotar todas as tentativas, registra log crítico mas não quebra o sistema.
     * O cancelamento local já foi feito — a pendência fica visível no status_externo.
     */
    public function failed(\Throwable $e): void
    {
        Log::critical("CancelarCobrancaGatewayJob: falha definitiva ao cancelar cobrança #{$this->cobrancaGatewayId} no Asaas.", [
            'exception' => $e->getMessage(),
            'motivo'    => $this->motivo,
        ]);
    }
}
