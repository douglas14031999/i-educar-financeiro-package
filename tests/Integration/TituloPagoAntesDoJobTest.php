<?php

namespace iEducar\Financeiro\Tests\Integration;

use Carbon\Carbon;
use iEducar\Financeiro\Jobs\EnviarNotificacaoCobrancaJob;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\ReguaEnvioLog;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Database\Eloquent\Model::unguard();

        \iEducar\Financeiro\Models\ResponsavelFinanceiro::firstOrCreate(['id' => 1], [
            'nome' => 'Responsavel Teste 1',
            'cpf_cnpj' => '12345678901',
            'email' => 'teste1@teste.com',
            'telefone_celular' => '11999999999',
            'cep' => '01001000',
            'logradouro' => 'Rua Teste',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'ativo' => true,
        ]);
    }

    /** @test */
    public function notificacao_nao_enviada_para_titulo_ja_pago(): void
    {
        Mail::fake();
        Queue::fake();

        // Arrange — título que já foi pago
        $titulo = Titulo::create([
            'aluno_id'                  => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id'                 => 1,
            'tipo'                      => 'mensalidade',
            'numero_parcela'            => 1,
            'total_parcelas'            => 1,
            'competencia'               => '2026-01',
            'data_vencimento'           => '2026-01-10',
            'valor_nominal'             => 500.00,
            'valor_saldo'               => 0.00,
            'valor_pago'                => 500.00,
            'status'                    => 'pago',
        ]);

        $regra = ReguaCobrancaRegra::create([
            'nome_regra'      => 'Aviso 3 dias antes',
            'tipo_gatilho'    => 'antes_vencimento',
            'dias_gatilho'    => 3,
            'canal'           => 'email',
            'ativo'           => true,
            'corpo_template'  => 'Olá {responsavel}, seu título vence em {vencimento}.',
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

        $titulo = Titulo::create([
            'aluno_id'                  => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id'                 => 1,
            'tipo'                      => 'mensalidade',
            'numero_parcela'            => 1,
            'total_parcelas'            => 1,
            'competencia'               => '2026-01',
            'data_vencimento'           => '2026-01-10',
            'valor_nominal'             => 500.00,
            'valor_saldo'               => 0.00,
            'status'                    => 'cancelado',
        ]);

        $regra = ReguaCobrancaRegra::create([
            'nome_regra'      => 'Aviso Cobrança',
            'tipo_gatilho'    => 'apos_vencimento',
            'dias_gatilho'    => 1,
            'canal'           => 'email',
            'corpo_template'  => 'Olá {responsavel}',
            'ativo'           => true,
        ]);

        $job = new EnviarNotificacaoCobrancaJob($regra, $titulo);
        $job->handle();

        Mail::assertNothingSent();
    }

    /** @test */
    public function notificacao_enviada_para_titulo_aberto_com_responsavel_valido(): void
    {
        Mail::fake();

        $titulo = Titulo::create([
            'aluno_id'                  => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id'                 => 1,
            'tipo'                      => 'mensalidade',
            'numero_parcela'            => 1,
            'total_parcelas'            => 1,
            'competencia'               => '2026-01',
            'valor_nominal'             => 500.00,
            'valor_saldo'               => 500.00,
            'data_vencimento'           => Carbon::now()->addDays(3)->toDateString(),
            'status'                    => 'aberto',
        ]);

        $regra = ReguaCobrancaRegra::create([
            'nome_regra'      => 'Aviso Antes',
            'tipo_gatilho'    => 'antes_vencimento',
            'dias_gatilho'    => 3,
            'canal'           => 'email',
            'corpo_template'  => 'Olá {responsavel}, seu saldo é R$ {valor}.',
            'ativo'           => true,
        ]);

        $job = new EnviarNotificacaoCobrancaJob($regra, $titulo);
        $job->handle();

        $log = ReguaEnvioLog::where('titulo_id', $titulo->id)->where('regra_id', $regra->id)->first();
        $this->assertNotNull($log);
        $this->assertTrue((bool) $log->sucesso);
    }
}
