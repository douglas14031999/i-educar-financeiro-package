<?php

namespace iEducar\Financeiro\Jobs;

use Carbon\Carbon;
use iEducar\Financeiro\Models\GatewayConfiguracao;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\ReguaEnvioLog;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\ReguaCanais\AsaasNotificacaoCanal;
use iEducar\Financeiro\Services\ReguaCanais\MailCanal;
use iEducar\Financeiro\Services\ReguaCanais\WhatsAppLinkCanal;
use iEducar\Financeiro\Support\AlunoLookup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        // BUG-027.10: Respeitar horário comercial (não enviar entre 22h e 07h, exceto em testes)
        $horaAtual = (int) now()->format('H');
        if (!app()->runningUnitTests() && ($horaAtual >= 22 || $horaAtual < 7)) {
            Log::info("Notificação régua [{$this->regra->nome_regra}] adiada: fora do horário comercial permitido ({$horaAtual}h).");
            return;
        }

        $titulo = $this->titulo->fresh() ?? $this->titulo;
        $titulo->load(['responsavel', 'cobrancaGateway']);
        $responsavel = $titulo->responsavel;

        if (!$responsavel) {
            Log::warning("Notificação régua: título #{$titulo->id} sem responsável associado.");
            return;
        }

        if (in_array($titulo->status, ['pago', 'cancelado', 'renegociado']) || (float) $titulo->valor_saldo <= 0) {
            ReguaEnvioLog::create([
                'regra_id'         => $this->regra->id,
                'titulo_id'        => $titulo->id,
                'responsavel_id'   => $responsavel->id,
                'canal'            => $this->regra->canal,
                'destinatario'     => $this->regra->canal === 'email' ? ($responsavel->email ?? 'Não informado') : ($responsavel->telefone_celular ?? 'Não informado'),
                'mensagem_enviada' => 'Notificação cancelada: título já ' . $titulo->status,
                'sucesso'          => false,
                'erro'             => "Título #{$titulo->id} já está {$titulo->status} e não deve receber notificação de cobrança.",
                'enviado_em'       => now(),
            ]);
            Log::info("Notificação régua ignorada: título #{$titulo->id} já possui status '{$titulo->status}'.");
            return;
        }

        $linkPagamento = $titulo->cobrancaGateway?->link_pagamento
            ?? $titulo->cobrancaGateway?->link_boleto_pdf
            ?? url("/financeiro/titulos/{$titulo->id}");

        $linhaDigitavel      = $titulo->cobrancaGateway?->linha_digitavel ?? 'Consulte a secretaria';
        $vencimentoFormatado = Carbon::parse($titulo->data_vencimento)->format('d/m/Y');
        $valorFormatado      = number_format((float) $titulo->valor_saldo, 2, ',', '.');
        $nomeAluno           = $titulo->aluno_nome ?: ("Aluno #" . $titulo->aluno_id);

        $variaveis = [
            '{responsavel}'     => $responsavel->nome,
            '{aluno}'           => $nomeAluno,
            '{competencia}'     => $titulo->competencia,
            '{vencimento}'      => $vencimentoFormatado,
            '{valor}'           => $valorFormatado,
            '{link_pagamento}'  => $linkPagamento,
            '{linha_digitavel}' => $linhaDigitavel,
        ];

        $corpo = $this->regra->corpo_template ?? 'Prezado(a) {responsavel}, lembramos do vencimento do título de {aluno} em {vencimento} no valor de R$ {valor}.';
        $mensagem = str_replace(array_keys($variaveis), array_values($variaveis), $corpo);

        // Destinatário
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

        if (!$sucesso && $erro) {
            throw new \RuntimeException("Falha no envio da notificação régua: {$erro}");
        }
    }

    /**
     * Despacha a notificação através dos canais plugáveis
     *
     * @return array{0: bool, 1: string|null}
     */
    private function despachar(string $canal, string $destinatario, string $mensagem, string $nomeResponsavel): array
    {
        try {
            return match ($canal) {
                'email'    => (new MailCanal())->enviar($this->regra, $this->titulo, $destinatario, $mensagem),
                'whatsapp' => (new WhatsAppLinkCanal())->enviar($this->regra, $this->titulo, $destinatario, $mensagem),
                'asaas'    => (new AsaasNotificacaoCanal())->enviar($this->regra, $this->titulo, $destinatario, $mensagem),
                'sms'      => [true, null], // Fila simulada ou fallback
                default    => throw new \InvalidArgumentException("Canal '{$canal}' não suportado."),
            };
        } catch (\Throwable $e) {
            Log::error("Falha ao despachar notificação [{$this->regra->nome_regra}] via {$canal}: " . $e->getMessage());
            return [false, $e->getMessage()];
        }
    }
}
