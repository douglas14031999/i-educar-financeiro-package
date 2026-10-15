<?php

namespace iEducar\Financeiro\Services\ReguaCanais;

use iEducar\Financeiro\Contracts\CanalNotificacaoInterface;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\Log;

class WhatsAppLinkCanal implements CanalNotificacaoInterface
{
    public function enviar(ReguaCobrancaRegra $regra, Titulo $titulo, string $destinatario, string $mensagem): array
    {
        $digitos = preg_replace('/\D/', '', $destinatario);

        if (strlen($digitos) < 10) {
            return [false, "Telefone inválido para WhatsApp: {$destinatario}"];
        }

        if (!str_starts_with($digitos, '55')) {
            $digitos = '55' . $digitos;
        }

        $link = 'https://api.whatsapp.com/send?phone=' . $digitos . '&text=' . urlencode($mensagem);

        Log::info("WhatsAppLinkCanal: Link gerado para {$digitos} (Título #{$titulo->id}): {$link}");

        return [true, null];
    }
}
