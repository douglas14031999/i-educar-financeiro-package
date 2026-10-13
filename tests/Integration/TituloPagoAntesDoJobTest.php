<?php

namespace iEducar\Financeiro\Tests\Integration;

use Carbon\Carbon;
use iEducar\Financeiro\Jobs\EnviarNotificacaoCobrancaJob;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\ReguaEnvioLog;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/**
 * P-07 / T24 — Título Pago Antes do Job de Notificação
 *
 * Garante que quando o job de notificação de cobrança é executado,
 * se o título já estiver pago, a notificação não é enviada.
 *
 * Evita: responsável receber cobrança de um título que já quitou.
 */
class TituloPagoAntesDoJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function notificacao_nao_enviada_para_titulo_ja_pago(): void
    {
        Mail::fake();
        Queue::fake();

        // Arrange — título que já foi pago
        $titulo = Titulo::factory()->create([
            'status'      => 'pago',
            'valor_saldo' => 0.00,
            'valor_pago'  => 500.00,
            'escola_id'   => 1,
            'responsavel_financeiro_id' => 1,
        ]);

        $regra = ReguaCobrancaRegra::factory()->create([
            'canal'           => 'email',
            'dias_antes'      => 3,
            'ativo'           => true,
            'corpo_template'  => 'Olá {responsavel}, seu título vence em {vencimento}.',
            'assunto'         => 'Lembrete de pagamento',
        ]);

        // Act — executa o job diretamente (não via queue)
        $job = new EnviarNotificacaoCobrancaJob($regra, $titulo);
        $job->handle();

        // Assert — nenhum email enviado
        Mail::assertNothingSent();

        // Assert — log de envio registra que o título já estava pago
        $log = ReguaEnvioLog::where('titulo_id', $titulo->id)
            ->where('regra_id', $regra->id)
            ->latest()
            ->first();

        $this->assertNotNull($log, 'Deve haver um log mesmo quando não envia (auditoria)');
        $this->assertFalse((bool) $log->sucesso, 'Sucesso deve ser false para título já pago');
        $this->assertStringContainsStringIgnoringCase('pago', $log->erro ?? '');
    }

    /** @test */
    public function notificacao_nao_enviada_para_titulo_cancelado(): void
    {
        Mail::fake();

        $titulo = Titulo::factory()->create([
            'status'      => 'cancelado',
            'valor_saldo' => 0.00,
            'escola_id'   => 1,
            'responsavel_financeiro_id' => 1,
        ]);

        $regra = ReguaCobrancaRegra::factory()->create([
            'canal'          => 'email',
            'corpo_template' => 'Olá {responsavel}',
            'assunto'        => 'Cobrança',
        ]);

        $job = new EnviarNotificacaoCobrancaJob($regra, $titulo);
        $job->handle();

        Mail::assertNothingSent();
    }

    /** @test */
    public function notificacao_enviada_para_titulo_aberto_com_responsavel_valido(): void
    {
        Mail::fake();

        $titulo = Titulo::factory()->create([
            'status'          => 'aberto',
            'valor_saldo'     => 500.00,
            'data_vencimento' => Carbon::now()->addDays(3)->toDateString(),
            'escola_id'       => 1,
            'responsavel_financeiro_id' => 1,
        ]);

        $regra = ReguaCobrancaRegra::factory()->create([
            'canal'          => 'email',
            'corpo_template' => 'Olá {responsavel}, seu saldo é R$ {valor}.',
            'assunto'        => 'Lembrete de pagamento',
        ]);

        $job = new EnviarNotificacaoCobrancaJob($regra, $titulo);
        $job->handle();

        Mail::assertSent(\Illuminate\Mail\Mailable::class, function ($mail) use ($titulo) {
            return $mail->hasTo($titulo->responsavel->email ?? 'test@test.com');
        });
    }
}
