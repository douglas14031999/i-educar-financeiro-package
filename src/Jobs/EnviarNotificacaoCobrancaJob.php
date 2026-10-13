<?php

namespace iEducar\Financeiro\Jobs;

use Carbon\Carbon;
use iEducar\Financeiro\Models\GatewayConfiguracao;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\ReguaEnvioLog;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarNotificacaoCobrancaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** A10-pattern: fila reprocessa em caso de falha */
    public int $tries   = 3;
    public int $backoff = 60;

    protected ReguaCobrancaRegra $regra;
    protected Titulo $titulo;

    public function __construct(ReguaCobrancaRegra $regra, Titulo $titulo)
    {
        $this->regra  = $regra;
        $this->titulo = $titulo;
    }

    public function handle(): void
    {
        $titulo      = $this->titulo->load(['responsavel', 'cobrancaGateway']);
        $responsavel = $titulo->responsavel;

        if (!$responsavel) {
            Log::warning("Notificação régua: título #{$titulo->id} sem responsável associado.");
            return;
        }

        $linkPagamento = $titulo->cobrancaGateway?->link_pagamento
            ?? $titulo->cobrancaGateway?->link_boleto_pdf
            ?? url("/financeiro/titulos/{$titulo->id}");

        $linhaDigitavel      = $titulo->cobrancaGateway?->linha_digitavel ?? 'Consulte a secretaria';
        $vencimentoFormatado = Carbon::parse($titulo->data_vencimento)->format('d/m/Y');
        $valorFormatado      = number_format((float) $titulo->valor_saldo, 2, ',', '.');

        $variaveis = [
            '{responsavel}'     => $responsavel->nome,
            '{aluno}'           => "Aluno #{$titulo->aluno_id}",
            '{competencia}'     => $titulo->competencia,
            '{vencimento}'      => $vencimentoFormatado,
            '{valor}'           => $valorFormatado,
            '{link_pagamento}'  => $linkPagamento,
            '{linha_digitavel}' => $linhaDigitavel,
        ];

        $mensagem = str_replace(array_keys($variaveis), array_values($variaveis), $this->regra->corpo_template);

        // A18 — campo correto do model ResponsavelFinanceiro
        $destinatario = $this->regra->canal === 'email'
            ? ($responsavel->email ?? '')
            : ($responsavel->telefone_celular ?? '');

        $sucesso = false;
        $erro    = null;

        if (empty($destinatario)) {
            $erro = "Contato ({$this->regra->canal}) do responsável não cadastrado.";
            Log::warning("Notificação régua [{$this->regra->nome_regra}]: {$erro}", [
                'titulo_id'      => $titulo->id,
                'responsavel_id' => $responsavel->id,
            ]);
        } else {
            // A17 — Disparo real; não apenas logar como se tivesse enviado
            [$sucesso, $erro] = $this->despachar($this->regra->canal, $destinatario, $mensagem, $responsavel->nome);
        }

        ReguaEnvioLog::create([
            'regra_id'         => $this->regra->id,
            'titulo_id'        => $titulo->id,
            'responsavel_id'   => $responsavel->id,
            'canal'            => $this->regra->canal,
            'destinatario'     => $destinatario ?: 'Não informado',
            'mensagem_enviada' => $mensagem,
            'sucesso'          => $sucesso,
            'erro'             => $erro,
            'enviado_em'       => now(),
        ]);

        // A10-pattern: relança para que a fila reprocesse em caso de falha de envio
        if (!$sucesso && $erro) {
            throw new \RuntimeException("Falha no envio da notificação régua: {$erro}");
        }
    }

    /**
     * A17 — Despacha a notificação pelo provedor correto.
     * Extensível: adicione cases aqui sem alterar a lógica do Job.
     *
     * @return array{0: bool, 1: string|null}
     */
    private function despachar(string $canal, string $destinatario, string $mensagem, string $nomeResponsavel): array
    {
        try {
            match ($canal) {
                'email'    => $this->enviarEmail($destinatario, $mensagem),
                'whatsapp' => $this->enviarWhatsApp($destinatario, $mensagem),
                'sms'      => $this->enviarSms($destinatario, $mensagem),
                default    => throw new \InvalidArgumentException("Canal '{$canal}' não suportado."),
            };

            Log::info("Notificação régua [{$this->regra->nome_regra}] enviada para {$nomeResponsavel} via {$canal}.");
            return [true, null];
        } catch (\Throwable $e) {
            Log::error("Falha ao enviar notificação régua [{$this->regra->nome_regra}] via {$canal}: " . $e->getMessage());
            return [false, $e->getMessage()];
        }
    }

    /** Envia e-mail usando o SMTP configurado no host via Laravel Mail */
    private function enviarEmail(string $email, string $mensagem): void
    {
        Mail::html($mensagem, function ($mail) use ($email) {
            $mail->to($email)->subject($this->regra->assunto ?? 'Notificação Financeira');
        });
    }

    /**
     * Envia WhatsApp via Asaas Messaging API.
     * Para outros provedores, substitua apenas este método.
     */
    private function enviarWhatsApp(string $telefone, string $mensagem): void
    {
        $config = GatewayConfiguracao::paraGateway('asaas');

        if (!$config?->api_key) {
            throw new \RuntimeException('Asaas não configurado. Salve as credenciais em Configurações → Gateway.');
        }

        $baseUrl = ($config->environment === 'production')
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';

        $response = Http::withHeaders([
            'access_token' => $config->api_key,
        ])->post("{$baseUrl}/messages/whatsapp", [
            'phoneNumber' => preg_replace('/\D/', '', $telefone),
            'message'     => $mensagem,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Asaas WhatsApp: ' . $response->body());
        }
    }

    /**
     * Placeholder para SMS — implemente com Twilio, Zenvia ou outro provedor.
     * Este método lança exceção intencionalmente até que um provedor seja configurado.
     */
    private function enviarSms(string $telefone, string $mensagem): void
    {
        throw new \RuntimeException(
            'Provedor de SMS não configurado. ' .
            'Implemente EnviarNotificacaoCobrancaJob::enviarSms() com Twilio, Zenvia ou equivalente.'
        );
    }
}
