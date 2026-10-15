<?php

namespace iEducar\Financeiro\Services\ReguaCanais;

use iEducar\Financeiro\Contracts\CanalNotificacaoInterface;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\Log;

class AsaasNotificacaoCanal implements CanalNotificacaoInterface
{
    public function enviar(ReguaCobrancaRegra $regra, Titulo $titulo, string $destinatario, string $mensagem): array
    {
        $cobranca = $titulo->cobrancaGateway;

        if (!$cobranca || empty($cobranca->gateway_charge_id)) {
            Log::info("AsaasNotificacaoCanal: Título #{$titulo->id} não possui cobrança ativa no Asaas para notificação nativa.");
            return [false, "Título não possui cobrança ativa no gateway Asaas."];
        }

        Log::info("AsaasNotificacaoCanal: Cobrança #{$cobranca->gateway_charge_id} já possui régua de notificação nativa configurada no Asaas.");
        return [true, null];
    }
}
