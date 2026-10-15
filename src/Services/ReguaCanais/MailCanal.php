<?php

namespace iEducar\Financeiro\Services\ReguaCanais;

use iEducar\Financeiro\Contracts\CanalNotificacaoInterface;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailCanal implements CanalNotificacaoInterface
{
    public function enviar(ReguaCobrancaRegra $regra, Titulo $titulo, string $destinatario, string $mensagem): array
    {
        try {
            $assunto = $regra->titulo_template 
                ?? $regra->assunto 
                ?? config('financeiro.regua_cobranca.assunto_padrao', 'Aviso Financeiro Escolar');

            Mail::raw($mensagem, function ($mail) use ($destinatario, $assunto) {
                $mail->to($destinatario)->subject($assunto);
            });

            Log::info("MailCanal: Notificação enviada com sucesso para {$destinatario} - Assunto: {$assunto}");
            return [true, null];
        } catch (\Throwable $e) {
            Log::error("MailCanal: Erro ao enviar e-mail para {$destinatario}: " . $e->getMessage());
            return [false, $e->getMessage()];
        }
    }
}
