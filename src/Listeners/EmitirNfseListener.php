<?php

namespace iEducar\Financeiro\Listeners;

use iEducar\Financeiro\Events\TituloPago;
use Illuminate\Support\Facades\Log;

class EmitirNfseListener
{
    /**
     * Trata o evento de recebimento de pagamento para emissão fiscal
     */
    public function handle(TituloPago $event): void
    {
        $habilitado = (bool) config('financeiro.fiscal.nfse_habilitada', false);

        if (!$habilitado) {
            // Emissão fiscal desabilitada por padrão até configuração de provedor de NFS-e
            return;
        }

        Log::info('EmitirNfseListener: Processando emissão de NFS-e para título #' . $event->titulo->id, [
            'referencia' => $event->payloadFiscal['referencia_externa'] ?? null,
            'tomador' => $event->payloadFiscal['tomador']['razao_social'] ?? null,
            'valor' => $event->payloadFiscal['valor_servicos'] ?? null,
        ]);
    }
}
