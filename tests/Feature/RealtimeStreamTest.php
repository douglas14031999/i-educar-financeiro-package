<?php

namespace iEducar\Financeiro\Tests\Feature;

use iEducar\Financeiro\Http\Middleware\AutorizarProcessoFinanceiro;
use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\PlanoPagamento;
use iEducar\Financeiro\Models\ResponsavelFinanceiro;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\BaixaTituloService;
use iEducar\Financeiro\Services\RealtimeEventService;
use iEducar\Financeiro\Tests\TestCase;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RealtimeStreamTest extends TestCase
{
    use DatabaseTransactions;

    private function criarEstruturaBase(): array
    {
        $randomCpf = str_pad((string) mt_rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);

        $resp = ResponsavelFinanceiro::create([
            'nome' => 'Maria Silva Realtime',
            'cpf_cnpj' => $randomCpf,
            'email' => 'maria.' . uniqid() . '@email.com',
            'telefone_celular' => '11999998888',
            'ativo' => true,
        ]);

        $plano = PlanoPagamento::create([
            'escola_id' => 1,
            'nome' => 'Plano Anual 2026 Realtime ' . uniqid(),
            'ano_letivo' => 2026,
            'valor_total' => 1200.00,
            'qtd_parcelas' => 12,
            'ativo' => true,
        ]);

        $contrato = Contrato::create([
            'escola_id' => 1,
            'matricula_id' => 90101,
            'numero_contrato' => 'CTR-TEST-' . uniqid(),
            'ano_letivo' => 2026,
            'aluno_id' => 901,
            'plano_id' => $plano->id,
            'responsavel_financeiro_id' => $resp->id,
            'valor_total_bruto' => 1200.00,
            'valor_total_liquido' => 1200.00,
            'status' => 'ativo',
            'data_inicio' => now()->startOfYear(),
            'data_termino' => now()->endOfYear(),
        ]);

        $titulo = Titulo::create([
            'contrato_id' => $contrato->id,
            'responsavel_financeiro_id' => $resp->id,
            'aluno_id' => 901,
            'escola_id' => 1,
            'numero_parcela' => 1,
            'total_parcelas' => 12,
            'competencia' => '01/2026',
            'data_vencimento' => now()->addDays(5)->format('Y-m-d'),
            'valor_nominal' => 100.00,
            'valor_saldo' => 100.00,
            'valor_pago' => 0.00,
            'status' => 'aberto',
            'observacoes' => 'Parcela 1/12 Teste Realtime',
        ]);

        return [$resp, $contrato, $titulo];
    }

    public function test_dispara_e_obtem_evento_de_pagamento_em_tempo_real()
    {
        Cache::flush();
        [$resp, $contrato, $titulo] = $this->criarEstruturaBase();

        $beforeMs = (int) (microtime(true) * 1000) - 100;

        // Dispara evento
        $payload = RealtimeEventService::dispararPagamentoConfirmado($titulo, null, 'webhook_asaas');

        $this->assertNotEmpty($payload['id']);
        $this->assertEquals('PAGAMENTO_CONFIRMADO', $payload['tipo']);
        $this->assertEquals('webhook_asaas', $payload['origem']);
        $this->assertEquals($titulo->id, $payload['titulo']['id']);
        $this->assertEquals('Maria Silva Realtime', $payload['titulo']['responsavel_nome']);

        // Consulta filtrando por título
        $novosTitulo = RealtimeEventService::obterNovosEventos($beforeMs, $titulo->id);
        $this->assertCount(1, $novosTitulo);
        $this->assertEquals($payload['id'], $novosTitulo[0]['id']);

        // Consulta global (sem filtro de título)
        $novosGlobal = RealtimeEventService::obterNovosEventos($beforeMs);
        $this->assertCount(1, $novosGlobal);
        $this->assertEquals($payload['id'], $novosGlobal[0]['id']);

        // Consulta com timestamp futuro não deve retornar nada
        $futuroMs = (int) (microtime(true) * 1000) + 10000;
        $vazio = RealtimeEventService::obterNovosEventos($futuroMs, $titulo->id);
        $this->assertEmpty($vazio);
    }

    public function test_baixa_titulo_service_dispara_evento_sse_automaticamente()
    {
        Cache::flush();
        [$resp, $contrato, $titulo] = $this->criarEstruturaBase();

        $beforeMs = (int) (microtime(true) * 1000) - 100;

        $baixaService = app(BaixaTituloService::class);
        $pagamento = $baixaService->realizarBaixa($titulo, [
            'valor_pago' => 100.00,
            'forma_pagamento' => 'pix',
            'origem_baixa' => 'webhook_asaas',
            'data_pagamento' => now(),
        ]);

        $this->assertNotNull($pagamento);
        $this->assertEquals('pago', $titulo->fresh()->status);

        // Deve existir evento registrado no cache do RealtimeEventService
        $eventos = RealtimeEventService::obterNovosEventos($beforeMs, $titulo->id);
        $this->assertCount(1, $eventos);
        $this->assertEquals('PAGAMENTO_CONFIRMADO', $eventos[0]['tipo']);
        $this->assertEquals($pagamento->id, $eventos[0]['pagamento']['id']);
        $this->assertEquals(100.00, $eventos[0]['pagamento']['valor_pago']);
    }

    public function test_endpoint_stream_sse_valida_autorizacao_e_retorna_headers_corretos()
    {
        AutorizarProcessoFinanceiro::limparCache();

        $usuarioComum = new AuthenticatableUser();
        $usuarioComum->id = 99;
        $usuarioComum->tipo_usuario_id = 99;

        // 1. Simular usuário sem permissão no banco
        DB::table('menu_tipo_usuario')->where('tipo_usuario_id', 99)->delete();

        $response403 = $this->actingAs($usuarioComum)->get(route('financeiro.api.stream'));
        $this->assertEquals(403, $response403->getStatusCode());

        // 2. Conceder permissão administrativa
        AutorizarProcessoFinanceiro::limparCache();
        $admin = new AuthenticatableUser();
        $admin->id = 1;
        $admin->tipo_usuario_id = 1;
        $admin->is_admin = true;

        DB::table('menu_tipo_usuario')->insertOrIgnore([
            'menu_id' => 649, // MENU_DASHBOARD
            'tipo_usuario_id' => 1,
            'visualiza' => 1,
            'cadastra' => 1,
            'exclui' => 1,
        ]);

        // Realizar request autenticado e com permissão
        $response = $this->actingAs($admin)->get(route('financeiro.api.stream', ['titulo_id' => 9999]));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertEquals('no', $response->headers->get('X-Accel-Buffering'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }
}
