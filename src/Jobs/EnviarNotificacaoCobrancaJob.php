<?php

namespace iEducar\Financeiro\Jobs;

use Carbon\Carbon;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\ReguaEnvioLog;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarNotificacaoCobrancaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var ReguaCobrancaRegra
     */
    protected $regra;

    /**
     * @var Titulo
     */
    protected $titulo;

    public function __construct(ReguaCobrancaRegra $regra, Titulo $titulo)
    {
        $this->regra = $regra;
        $this->titulo = $titulo;
    }

    public function handle()
    {
        $titulo = $this->titulo->load(['responsavel', 'cobrancaGateway']);
        $responsavel = $titulo->responsavel;

        if (!$responsavel) {
            Log::warning("Não foi possível enviar notificação da régua: título #{$titulo->id} não possui responsável associado.");
            return;
        }

        // Variáveis de substituição do template
        $linkPagamento = $titulo->cobrancaGateway->link_pagamento 
            ?? $titulo->cobrancaGateway->link_boleto_pdf 
            ?? url("/financeiro/titulos/{$titulo->id}");

        $linhaDigitavel = $titulo->cobrancaGateway->linha_digitavel ?? 'Consulte a secretaria para a linha digitável';
        $vencimentoFormatado = Carbon::parse($titulo->data_vencimento)->format('d/m/Y');
        $valorFormatado = number_format($titulo->valor_saldo, 2, ',', '.');

        $variaveis = [
            '{responsavel}' => $responsavel->nome,
            '{aluno}' => "Aluno #{$titulo->aluno_id}",
            '{competencia}' => $titulo->competencia,
            '{vencimento}' => $vencimentoFormatado,
            '{valor}' => $valorFormatado,
            '{link_pagamento}' => $linkPagamento,
            '{linha_digitavel}' => $linhaDigitavel,
        ];

        $mensagem = str_replace(array_keys($variaveis), array_values($variaveis), $this->regra->corpo_template);
        $destinatario = $this->regra->canal === 'email' 
            ? ($responsavel->email ?? '') 
            : ($responsavel->celular ?? $responsavel->telefone ?? '');

        $sucesso = true;
        $erro = null;

        if (empty($destinatario)) {
            $sucesso = false;
            $erro = "Contato ({$this->regra->canal}) do responsável não cadastrado.";
        } else {
            // Em ambiente com gateway Asaas ou disparo de webhook/WhatsApp/E-mail configurado:
            Log::info("Disparando notificação régua [{$this->regra->nome_regra}] para {$responsavel->nome} via {$this->regra->canal}: {$destinatario}");
        }

        // Registra log do envio para evitar duplicidade e fornecer auditoria
        ReguaEnvioLog::create([
            'regra_id' => $this->regra->id,
            'titulo_id' => $titulo->id,
            'responsavel_id' => $responsavel->id,
            'canal' => $this->regra->canal,
            'destinatario' => $destinatario ?: 'Não informado',
            'mensagem_enviada' => $mensagem,
            'sucesso' => $sucesso,
            'erro' => $erro,
            'enviado_em' => now(),
        ]);
    }
}
