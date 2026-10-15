<?php

namespace iEducar\Financeiro\Tests\Feature;

use Carbon\Carbon;
use iEducar\Financeiro\Models\AlunoResponsavel;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\ContaBancaria;
use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\CreditoResponsavel;
use iEducar\Financeiro\Models\Lancamento;
use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\PlanoConta;
use iEducar\Financeiro\Models\PlanoItem;
use iEducar\Financeiro\Models\PlanoPagamento;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\Renegociacao;
use iEducar\Financeiro\Models\ResponsavelFinanceiro;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Models\WebhookEvent;
use iEducar\Financeiro\Services\BaixaTituloService;
use iEducar\Financeiro\Services\ContratoService;
use iEducar\Financeiro\Services\RenegociacaoService;
use iEducar\Financeiro\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use RuntimeException;

class PrdComplianceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->criarFixturesBasicas();
    }

    private function criarFixturesBasicas(): void
    {
        \Illuminate\Database\Eloquent\Model::unguard();

        // Cria Responsável Financeiro básico
        $resp1 = ResponsavelFinanceiro::firstOrCreate(['id' => 1], [
            'nome' => 'Responsavel Teste 1',
            'cpf_cnpj' => '12345678901',
            'email' => 'resp1@teste.com',
            'telefone_celular' => '11999999999',
            'cep' => '01001000',
            'logradouro' => 'Rua Teste',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'ativo' => true,
        ]);

        $resp2 = ResponsavelFinanceiro::firstOrCreate(['id' => 2], [
            'nome' => 'Responsavel Teste 2',
            'cpf_cnpj' => '98765432100',
            'email' => 'resp2@teste.com',
            'telefone_celular' => '11988888888',
            'cep' => '01001000',
            'logradouro' => 'Rua Teste 2',
            'numero' => '200',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'ativo' => true,
        ]);

        // Vincula Aluno 1 ao Responsável 1
        AlunoResponsavel::firstOrCreate([
            'aluno_id' => 1,
            'responsavel_id' => $resp1->id,
        ], [
            'parentesco' => 'Pai',
            'percentual_rateio' => 100.00,
            'responsavel_principal' => true,
        ]);

        // Plano de Contas
        PlanoConta::firstOrCreate(['id' => 1], [
            'codigo' => '3.1.01',
            'nome' => 'Material Didático',
            'tipo' => 'despesa',
            'aceita_lancamento' => true,
            'ativo' => true,
        ]);

        // Conta Bancária
        ContaBancaria::firstOrCreate(['id' => 1], [
            'escola_id' => 1,
            'banco_codigo' => '001',
            'nome' => 'Banco do Brasil - Principal',
            'agencia' => '1234',
            'conta' => '56789',
            'saldo_inicial' => 10000.00,
            'saldo_atual' => 10000.00,
            'ativo' => true,
        ]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval('financeiro.contas_bancarias_id_seq', (SELECT COALESCE(MAX(id), 1) + 1 FROM financeiro.contas_bancarias));");
            DB::statement("SELECT setval('financeiro.responsaveis_financeiros_id_seq', (SELECT COALESCE(MAX(id), 1) + 1 FROM financeiro.responsaveis_financeiros));");
            DB::statement("SELECT setval('financeiro.plano_contas_id_seq', (SELECT COALESCE(MAX(id), 1) + 1 FROM financeiro.plano_contas));");
        }
    }

    /** T01 — Webhook sem token configurado */
    public function test_t01_webhook_sem_token_rejeitado_sem_mutacao(): void
    {
        config(['financeiro.gateways.asaas.webhook_token' => null]);

        $response = $this->postJson('/financeiro/webhook/asaas', [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => ['id' => 'pay_test_123', 'value' => 100.00]
        ], ['asaas-access-token' => 'token_tentado']);

        $response->assertStatus(401);
        $this->assertEquals(0, WebhookEvent::count());
        $this->assertEquals(0, Pagamento::count());
    }

    /** T02 — Token incorreto */
    public function test_t02_webhook_token_incorreto_rejeitado(): void
    {
        config(['financeiro.gateways.asaas.webhook_token' => 'segredo_valido_999']);

        $response = $this->postJson('/financeiro/webhook/asaas', [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => ['id' => 'pay_test_123', 'value' => 100.00]
        ], ['asaas-access-token' => 'segredo_errado']);

        $response->assertStatus(401);
        $this->assertEquals(0, WebhookEvent::count());
    }

    /** T03 — Mesmo evento repetido */
    public function test_t03_mesmo_evento_repetido_uma_aplicacao(): void
    {
        config(['financeiro.gateways.asaas.webhook_token' => 'segredo_valido']);

        $payload = [
            'id' => 'evt_123456',
            'event' => 'PAYMENT_RECEIVED',
            'payment' => ['id' => 'pay_999', 'value' => 150.00]
        ];

        $headers = ['asaas-access-token' => 'segredo_valido'];

        $res1 = $this->postJson('/financeiro/webhook/asaas', $payload, $headers);
        $res1->assertStatus(200);

        $res2 = $this->postJson('/financeiro/webhook/asaas', $payload, $headers);
        $res2->assertStatus(200);
        $res2->assertJson(['duplicated' => true]);

        $this->assertEquals(1, WebhookEvent::where('event_id', 'evt_123456')->count());
    }

    /** T04 — Webhook e sincronização simultâneos: um único recebimento */
    public function test_t04_webhook_e_sincronizacao_simultaneos_um_recebimento(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => '2026-01-10',
            'valor_nominal' => 200.00,
            'valor_saldo' => 200.00,
            'status' => 'aberto',
        ]);

        $baixaService = app(BaixaTituloService::class);

        // Primeira baixa (simulando webhook)
        $pag1 = $baixaService->realizarBaixa($titulo, [
            'valor_pago' => 200.00,
            'forma_pagamento' => 'boleto',
            'origem_baixa' => 'webhook_asaas',
            'data_pagamento' => '2026-01-10',
        ]);

        // Segunda baixa repetida (simulando sincronização manual que roda ao mesmo tempo)
        $this->expectException(InvalidArgumentException::class);
        $baixaService->realizarBaixa($titulo->fresh(), [
            'valor_pago' => 200.00,
            'forma_pagamento' => 'boleto',
            'origem_baixa' => 'sincronizacao_gateway',
            'data_pagamento' => '2026-01-10',
        ]);

        $this->assertEquals(1, $titulo->pagamentos()->count());
    }

    /** T05 — Pagamento excedente e estorno */
    public function test_t05_pagamento_excedente_e_estorno_restaura_saldo_original(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => '2026-01-10',
            'valor_nominal' => 100.00,
            'valor_saldo' => 100.00,
            'status' => 'aberto',
        ]);

        $baixaService = app(BaixaTituloService::class);

        // Paga R$ 120 numa dívida de R$ 100 -> Amortiza 100, gera crédito de 20
        $pagamento = $baixaService->realizarBaixa($titulo, [
            'valor_pago' => 120.00,
            'forma_pagamento' => 'dinheiro',
            'origem_baixa' => 'manual',
            'data_pagamento' => '2026-01-10',
        ]);

        $this->assertEquals(100.00, (float) $pagamento->valor_amortizado);
        $this->assertEquals(0.00, (float) $titulo->fresh()->valor_saldo);

        $credito = CreditoResponsavel::where('pagamento_id', $pagamento->id)->first();
        $this->assertNotNull($credito);
        $this->assertEquals(20.00, (float) $credito->valor);
        $this->assertFalse((bool) $credito->utilizado);
        $this->assertFalse((bool) $credito->cancelado);

        // Estorno
        $baixaService->estornarBaixa($pagamento, 'Estorno de teste', 1);

        $this->assertEquals(100.00, (float) $titulo->fresh()->valor_saldo);
        $this->assertTrue((bool) $credito->fresh()->cancelado);
    }

    /** T06 — Dois estornos simultâneos */
    public function test_t06_dois_estornos_simultaneos_uma_reversao(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => '2026-01-10',
            'valor_nominal' => 80.00,
            'valor_saldo' => 80.00,
            'status' => 'aberto',
        ]);

        $baixaService = app(BaixaTituloService::class);
        $pagamento = $baixaService->realizarBaixa($titulo, [
            'valor_pago' => 80.00,
            'forma_pagamento' => 'dinheiro',
            'data_pagamento' => '2026-01-10',
        ]);

        // Primeiro estorno
        $baixaService->estornarBaixa($pagamento, 'Estorno 1', 1);

        // Segundo estorno concorrente deve falhar
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Este pagamento já foi estornado');
        $baixaService->estornarBaixa($pagamento, 'Estorno 2', 1);
    }

    /** T07 — Crédito excedente já utilizado bloqueia estorno */
    public function test_t07_credito_excedente_ja_utilizado_bloqueia_estorno(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => '2026-01-10',
            'valor_nominal' => 100.00,
            'valor_saldo' => 100.00,
            'status' => 'aberto',
        ]);

        $baixaService = app(BaixaTituloService::class);
        $pagamento = $baixaService->realizarBaixa($titulo, [
            'valor_pago' => 150.00,
            'forma_pagamento' => 'dinheiro',
            'data_pagamento' => '2026-01-10',
        ]);

        // Marca crédito como utilizado por outra operação
        $credito = CreditoResponsavel::where('pagamento_id', $pagamento->id)->first();
        $credito->update(['utilizado' => true]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/já foi utilizado/i');

        $baixaService->estornarBaixa($pagamento, 'Tentativa de estorno', 1);
    }

    /** T08 — Baixa retroativa e identificação precisa do recibo */
    public function test_t08_baixa_retroativa_recibo_correto(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => '2026-01-10',
            'valor_nominal' => 300.00,
            'valor_saldo' => 300.00,
            'status' => 'aberto',
        ]);

        $baixaService = app(BaixaTituloService::class);

        // Pagamento 1: R$ 100 pago hoje
        $pag1 = $baixaService->realizarBaixa($titulo, [
            'valor_pago' => 100.00,
            'forma_pagamento' => 'dinheiro',
            'data_pagamento' => '2026-01-15',
        ]);

        // Pagamento 2: R$ 100 com data retroativa
        $pag2 = $baixaService->realizarBaixa($titulo->fresh(), [
            'valor_pago' => 100.00,
            'forma_pagamento' => 'pix',
            'data_pagamento' => '2026-01-05',
        ]);

        // Rota de recibo direcionada pelo ID do pagamento
        $response = $this->get(route('financeiro.pagamentos.recibo', $pag2->id));
        $response->assertStatus(200);
        $response->assertSee('pix');
        $response->assertSee('05/01/2026');
    }

    /** T09 — Impressão de carnê não cria cobranças externas */
    public function test_t09_impressao_carne_nao_cria_cobrancas(): void
    {
        $contrato = Contrato::create([
            'numero_contrato' => 'CTR-2026-9999',
            'aluno_id' => 1,
            'matricula_id' => 1,
            'responsavel_financeiro_id' => 1,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 1200.00,
            'valor_total_descontos' => 0.00,
            'valor_total_liquido' => 1200.00,
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);

        $response = $this->get(route('financeiro.contratos.imprimir-carne', $contrato->id));
        $response->assertStatus(200);

        // Verifica que nenhuma cobrança no gateway foi criada
        $this->assertEquals(0, CobrancaGateway::count());
    }

    /** T10 — Seleção de duas parcelas imprime apenas duas */
    public function test_t10_selecao_duas_parcelas_apenas_duas_impressas(): void
    {
        $titulos = [];
        for ($i = 1; $i <= 5; $i++) {
            $titulos[] = Titulo::create([
                'aluno_id' => 1,
                'responsavel_financeiro_id' => 1,
                'escola_id' => 1,
                'tipo' => 'mensalidade',
                'numero_parcela' => $i,
                'total_parcelas' => 5,
                'competencia' => sprintf('2026-%02d', $i),
                'data_vencimento' => sprintf('2026-%02d-10', $i),
                'valor_nominal' => 200.00,
                'valor_saldo' => 200.00,
                'status' => 'aberto',
            ]);
        }

        // Seleciona parcelas 2 e 3
        $selecionados = [$titulos[1]->id, $titulos[2]->id];

        $response = $this->get(route('financeiro.titulos.imprimir-carne', [
            'titulo_ids' => implode(',', $selecionados)
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('titulos', function ($t) use ($selecionados) {
            return $t->count() === 2 && $t->pluck('id')->sort()->values()->all() === collect($selecionados)->sort()->values()->all();
        });
    }

    /** T11 — Geração repetida de títulos: nenhuma duplicidade */
    public function test_t11_geracao_repetida_nenhuma_duplicidade(): void
    {
        $plano = PlanoPagamento::create([
            'escola_id' => 1,
            'ano_letivo' => 2026,
            'nome' => 'Plano Anual Teste',
            'valor_total' => 1200.00,
            'qtd_parcelas' => 12,
            'ativo' => true,
        ]);

        for ($i = 1; $i <= 12; $i++) {
            PlanoItem::create([
                'plano_id' => $plano->id,
                'tipo_item' => 'mensalidade',
                'descricao' => "Parcela {$i}",
                'numero_parcela' => $i,
                'valor' => 100.00,
                'mes_competencia' => $i,
                'dia_vencimento_padrao' => 10,
            ]);
        }

        $contratoService = app(ContratoService::class);
        $contrato = $contratoService->criarContratoEParcelas([
            'aluno_id' => 1,
            'matricula_id' => 1,
            'plano_id' => $plano->id,
            'ano_letivo' => 2026,
            'escola_id' => 1,
            'dia_vencimento' => 10,
            'valor_total_descontos' => 0.00,
        ]);

        $this->assertEquals(12, Titulo::where('contrato_id', $contrato->id)->count());

        // Chama regeneração de faltantes: todos já existem
        $novos = $contratoService->regenerarTitulosFaltantes($contrato);
        $this->assertEquals(0, $novos);
        $this->assertEquals(12, Titulo::where('contrato_id', $contrato->id)->count());
    }

    /** T12 — Parcela ausente com rateio: recomposição correta */
    public function test_t12_parcela_ausente_com_rateio_recomposicao_correta(): void
    {
        // Define rateio: Resp 1 (60%), Resp 2 (40%)
        AlunoResponsavel::where('aluno_id', 1)->delete();
        AlunoResponsavel::create([
            'aluno_id' => 1,
            'responsavel_id' => 1,
            'parentesco' => 'Pai',
            'percentual_rateio' => 60.00,
            'responsavel_principal' => true,
        ]);
        AlunoResponsavel::create([
            'aluno_id' => 1,
            'responsavel_id' => 2,
            'parentesco' => 'Mãe',
            'percentual_rateio' => 40.00,
            'responsavel_principal' => false,
        ]);

        $plano = PlanoPagamento::create([
            'escola_id' => 1,
            'ano_letivo' => 2026,
            'nome' => 'Plano Rateio',
            'valor_total' => 200.00,
            'qtd_parcelas' => 2,
            'ativo' => true,
        ]);

        PlanoItem::create(['plano_id' => $plano->id, 'tipo_item' => 'mensalidade', 'descricao' => 'P1', 'numero_parcela' => 1, 'valor' => 100.00, 'mes_competencia' => 1, 'dia_vencimento_padrao' => 10]);
        PlanoItem::create(['plano_id' => $plano->id, 'tipo_item' => 'mensalidade', 'descricao' => 'P2', 'numero_parcela' => 2, 'valor' => 100.00, 'mes_competencia' => 2, 'dia_vencimento_padrao' => 10]);

        $contratoService = app(ContratoService::class);
        $contrato = $contratoService->criarContratoEParcelas([
            'aluno_id' => 1,
            'matricula_id' => 1,
            'plano_id' => $plano->id,
            'ano_letivo' => 2026,
            'escola_id' => 1,
            'dia_vencimento' => 10,
            'valor_total_descontos' => 0.00,
        ]);

        $this->assertEquals(4, Titulo::where('contrato_id', $contrato->id)->count());

        // Deleta os títulos da parcela 2
        Titulo::where('contrato_id', $contrato->id)->where('numero_parcela', 2)->delete();
        $this->assertEquals(2, Titulo::where('contrato_id', $contrato->id)->count());

        // Regenera
        $novos = $contratoService->regenerarTitulosFaltantes($contrato);
        $this->assertEquals(2, $novos);

        $p2Titulos = Titulo::where('contrato_id', $contrato->id)->where('numero_parcela', 2)->get();
        $this->assertEquals(60.00, (float) $p2Titulos->firstWhere('responsavel_financeiro_id', 1)->valor_nominal);
        $this->assertEquals(40.00, (float) $p2Titulos->firstWhere('responsavel_financeiro_id', 2)->valor_nominal);
    }

    /** T13 — Matrícula de outro aluno rejeitada mesmo com escola preenchida */
    public function test_t13_matricula_de_outro_aluno_rejeitada(): void
    {
        // Matrícula 1 pertence ao aluno 1. Vamos tentar vincular ao aluno 99.
        $contratoService = app(ContratoService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/não pertence ao aluno/i');

        $contratoService->criarContratoEParcelas([
            'aluno_id' => 99,
            'matricula_id' => 1,
            'plano_id' => 1,
            'ano_letivo' => 2026,
            'escola_id' => 1,
        ]);
    }

    /** T14 — Plano de 24 parcelas: calendário e competência válidos sem mês zero */
    public function test_t14_plano_24_parcelas_calendario_valido(): void
    {
        $response = $this->post(route('financeiro.planos.store'), [
            'escola_id' => 1,
            'ano_letivo' => 2026,
            'nome' => 'Plano Bienal 24x',
            'valor_total' => 2400.00,
            'qtd_parcelas' => 24,
            'dia_vencimento_padrao' => 15,
            'gerar_matricula_avulsa' => 1,
            'valor_matricula' => 200.00,
        ]);

        $plano = PlanoPagamento::with('itens')->where('nome', 'Plano Bienal 24x')->first();
        $this->assertNotNull($plano);
        $this->assertEquals(2600.00, (float) $plano->valor_total); // 2400 + 200 integrada

        // Nenhum item com mês 0
        $mesesZero = $plano->itens->filter(fn($item) => $item->mes_competencia === 0);
        $this->assertCount(0, $mesesZero);

        // Parcela 24 tem mês 12
        $item24 = $plano->itens->firstWhere('numero_parcela', 24);
        $this->assertEquals(12, $item24->mes_competencia);

        // Ao gerar títulos, parcelas 13 a 24 avançam para 2027
        $contratoService = app(ContratoService::class);
        $contrato = $contratoService->criarContratoEParcelas([
            'aluno_id' => 1,
            'matricula_id' => 1,
            'plano_id' => $plano->id,
            'ano_letivo' => 2026,
            'escola_id' => 1,
            'dia_vencimento' => 15,
        ]);

        $titulo13 = Titulo::where('contrato_id', $contrato->id)->where('numero_parcela', 13)->first();
        $this->assertEquals('2027-01', $titulo13->competencia);
        $this->assertEquals('2027-01-15', Carbon::parse($titulo13->data_vencimento)->format('Y-m-d'));

        $titulo24 = Titulo::where('contrato_id', $contrato->id)->where('numero_parcela', 24)->first();
        $this->assertEquals('2027-12', $titulo24->competencia);
        $this->assertEquals('2027-12-15', Carbon::parse($titulo24->data_vencimento)->format('Y-m-d'));
    }

    /** T15 — Pagamento após simulação de renegociação invalida confirmação */
    public function test_t15_pagamento_apos_simulacao_renegociacao_exige_revalidacao(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => '2026-01-10',
            'valor_nominal' => 500.00,
            'valor_saldo' => 500.00,
            'status' => 'aberto',
        ]);

        $service = app(RenegociacaoService::class);
        $simulacao = $service->simular([$titulo->id], 0.0, 2, '2026-02-10');

        // Pagamento intermediário
        app(BaixaTituloService::class)->realizarBaixa($titulo, [
            'valor_pago' => 100.00,
            'forma_pagamento' => 'dinheiro',
            'data_pagamento' => '2026-01-10',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/saldo devedor dos títulos foi alterado/i');

        $service->confirmar([$titulo->id], 0.0, 2, '2026-02-10', 1, (float) $simulacao['total_original']);
    }

    /** T16 — Renegociações concorrentes e isolamento */
    public function test_t16_renegociacoes_responsaveis_diferentes_bloqueadas(): void
    {
        $t1 = Titulo::create(['aluno_id' => 1, 'responsavel_financeiro_id' => 1, 'escola_id' => 1, 'tipo' => 'mensalidade', 'numero_parcela' => 1, 'total_parcelas' => 1, 'competencia' => '2026-01', 'data_vencimento' => '2026-01-10', 'valor_nominal' => 100, 'valor_saldo' => 100, 'status' => 'aberto']);
        $t2 = Titulo::create(['aluno_id' => 1, 'responsavel_financeiro_id' => 2, 'escola_id' => 1, 'tipo' => 'mensalidade', 'numero_parcela' => 2, 'total_parcelas' => 1, 'competencia' => '2026-02', 'data_vencimento' => '2026-02-10', 'valor_nominal' => 100, 'valor_saldo' => 100, 'status' => 'aberto']);

        $service = app(RenegociacaoService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/mesmo responsável financeiro/i');

        $service->simular([$t1->id, $t2->id], 0.0, 2, '2026-02-10');
    }

    /** T17 — Título cancelado ou renegociado tem ações incompatíveis bloqueadas */
    public function test_t17_titulo_cancelado_renegociado_bloqueado(): void
    {
        $tituloRenegociado = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => '2026-01-10',
            'valor_nominal' => 100.00,
            'valor_saldo' => 100.00,
            'status' => 'renegociado',
        ]);

        $this->assertFalse($tituloRenegociado->isElegivelBaixa());
        $this->assertFalse($tituloRenegociado->isElegivelEmissaoGateway());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/renegociado/i');

        app(BaixaTituloService::class)->realizarBaixa($tituloRenegociado, [
            'valor_pago' => 100.00,
            'forma_pagamento' => 'dinheiro',
            'data_pagamento' => '2026-01-10',
        ]);
    }

    /** T18 — Liquidação repetida de despesa não sobrescreve dados */
    public function test_t18_liquidacao_repetida_despesa_bloqueada(): void
    {
        $planoConta = PlanoConta::firstOrCreate(['id' => 1], [
            'codigo' => '3.1.01',
            'nome' => 'Material Didático',
            'tipo' => 'despesa',
            'aceita_lancamento' => true,
            'ativo' => true,
        ]);

        $contaBancaria = ContaBancaria::firstOrCreate(['id' => 1], [
            'escola_id' => 1,
            'banco_codigo' => '001',
            'nome' => 'Banco do Brasil - Principal',
            'agencia' => '1234',
            'conta' => '56789',
            'saldo_inicial' => 10000.00,
            'saldo_atual' => 10000.00,
            'ativo' => true,
        ]);

        $despesa = Lancamento::create([
            'tipo' => 'despesa',
            'descricao' => 'Conta de Luz',
            'valor' => 350.00,
            'data_competencia' => '2026-01-01',
            'data_vencimento' => '2026-01-15',
            'plano_conta_id' => $planoConta->id,
            'escola_id' => 1,
            'status' => 'a_pagar',
        ]);

        // Primeira liquidação
        $res = $this->post(route('financeiro.contas-pagar.liquidar', $despesa->id), [
            'data_pagamento' => '2026-01-14',
            'conta_bancaria_id' => $contaBancaria->id,
        ]);

        $this->assertEquals('pago', $despesa->fresh()->status);
        $this->assertEquals('2026-01-14', Carbon::parse($despesa->fresh()->data_pagamento)->format('Y-m-d'));

        // Segunda tentativa com outros dados
        $response = $this->post(route('financeiro.contas-pagar.liquidar', $despesa->id), [
            'data_pagamento' => '2026-01-20',
            'conta_bancaria_id' => $contaBancaria->id,
        ]);

        // Mantém a data original de liquidação
        $this->assertEquals('2026-01-14', Carbon::parse($despesa->fresh()->data_pagamento)->format('Y-m-d'));
    }

    /** T19 — Título prorrogado: consistência entre scope e isVencido */
    public function test_t19_titulo_prorrogado_consistente(): void
    {
        $ontem = Carbon::yesterday()->toDateString();
        $amanha = Carbon::tomorrow()->toDateString();

        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => $ontem,
            'data_prorrogacao' => $amanha,
            'valor_nominal' => 200.00,
            'valor_saldo' => 200.00,
            'status' => 'aberto',
        ]);

        $this->assertFalse($titulo->isVencido());

        // scopeVencidos não deve retornar este título
        $vencidos = Titulo::vencidos()->where('id', $titulo->id)->count();
        $this->assertEquals(0, $vencidos);
    }

    /** T20 — Usuário de outra escola: bloqueio de operações */
    public function test_t20_isolamento_por_escola(): void
    {
        $planoEscola2 = PlanoPagamento::create([
            'escola_id' => 2,
            'ano_letivo' => 2026,
            'nome' => 'Plano Escola 2',
            'valor_total' => 1000.00,
            'qtd_parcelas' => 1,
            'ativo' => true,
        ]);

        $contratoService = app(ContratoService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/divergente da escola/i');

        // Tenta associar plano da Escola 2 a contrato da Escola 1
        $contratoService->criarContratoEParcelas([
            'aluno_id' => 1,
            'matricula_id' => 1,
            'plano_id' => $planoEscola2->id,
            'ano_letivo' => 2026,
            'escola_id' => 1,
        ]);
    }

    /** T21 — Régua de cobrança com ativo=false */
    public function test_t21_regua_ativo_false_desativa_regra(): void
    {
        $regra = ReguaCobrancaRegra::create([
            'nome_regra' => 'Lembrete Vencimento',
            'tipo_gatilho' => 'antes_vencimento',
            'dias_gatilho' => 3,
            'canal' => 'whatsapp',
            'corpo_template' => 'Mensagem teste',
            'ativo' => true,
        ]);

        // Atualiza enviando ativo = 0 (ou "false")
        $this->post(route('financeiro.configuracoes.regua.atualizar', $regra->id), [
            'nome_regra' => 'Lembrete Vencimento',
            'canal' => 'whatsapp',
            'corpo_template' => 'Nova mensagem',
            'ativo' => 0,
        ]);

        $this->assertFalse((bool) $regra->fresh()->ativo);
    }

    /** T22 — Falha ao copiar Pix / Linha digitável: view implementa tratamento */
    public function test_t22_view_contem_script_copiar_clipboard(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => '2026-01-10',
            'valor_nominal' => 150.00,
            'valor_saldo' => 150.00,
            'status' => 'aberto',
        ]);

        CobrancaGateway::create([
            'titulo_id' => $titulo->id,
            'gateway' => 'asaas',
            'gateway_charge_id' => 'pay_12345',
            'status_externo' => 'PENDING',
            'linha_digitavel' => '12345678901234567890',
            'pix_copia_cola' => '00020126580014br.gov.bcb.pix...',
        ]);

        $response = $this->get(route('financeiro.titulos.show', $titulo->id));
        $response->assertStatus(200);
        $response->assertSee('copiarTextoParaClipboard');
    }

    /** T23 — Gateway cria cobrança, resposta falha: retentativa sem duplicação */
    public function test_t23_cobranca_gateway_unicidade(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => '2026-01-10',
            'valor_nominal' => 150.00,
            'valor_saldo' => 150.00,
            'status' => 'aberto',
        ]);

        CobrancaGateway::create([
            'titulo_id' => $titulo->id,
            'gateway' => 'asaas',
            'gateway_charge_id' => 'pay_first',
            'status_externo' => 'PENDING',
        ]);

        // Segunda tentativa com mesmo gateway_charge_id deve violar constraint de unicidade
        try {
            CobrancaGateway::create([
                'titulo_id' => $titulo->id,
                'gateway' => 'asaas',
                'gateway_charge_id' => 'pay_first',
                'status_externo' => 'PENDING',
            ]);
            $this->fail('Deveria ter violado constraint de unicidade');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            DB::beginTransaction();
            $this->assertTrue(true);
        }
    }

    /** T24 — Evento de webhook salvo para recuperação posterior */
    public function test_t24_evento_salvo_para_recuperacao(): void
    {
        config(['financeiro.gateways.asaas.webhook_token' => 'segredo']);

        $this->postJson('/financeiro/webhook/asaas', [
            'id' => 'evt_recuperavel',
            'event' => 'PAYMENT_RECEIVED',
            'payment' => ['id' => 'pay_rec', 'value' => 100.00]
        ], ['asaas-access-token' => 'segredo']);

        $event = WebhookEvent::where('event_id', 'evt_recuperavel')->first();
        $this->assertNotNull($event);
        $this->assertEquals('PAYMENT_RECEIVED', $event->event_type);
    }

    /** T25 — Relatórios DRE e Fluxo de Caixa reconciliados após estorno */
    public function test_t25_relatorios_reconciliados_apos_estorno(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => '2026-01-10',
            'valor_nominal' => 100.00,
            'valor_saldo' => 100.00,
            'status' => 'aberto',
        ]);

        $baixaService = app(BaixaTituloService::class);
        $pag = $baixaService->realizarBaixa($titulo, [
            'valor_pago' => 103.00, // R$ 100 principal + R$ 3 encargos
            'valor_juros' => 2.00,
            'valor_multa' => 1.00,
            'forma_pagamento' => 'dinheiro',
            'data_pagamento' => '2026-01-15',
        ]);

        // Antes do estorno: DRE e Fluxo de Caixa devem ambos reconhecer R$ 103
        $resDre = $this->get(route('financeiro.relatorios.dre', ['ano' => 2026]));
        $resDre->assertStatus(200);
        $resDre->assertViewHas('receitaLiquida', 103.00);

        // Estorna o pagamento
        $baixaService->estornarBaixa($pag, 'Estorno auditado', 1);

        // Após estorno: Receita reconhecida deve ser ZERO
        $resDreApos = $this->get(route('financeiro.relatorios.dre', ['ano' => 2026]));
        $resDreApos->assertViewHas('receitaLiquida', 0.00);

        $resFluxoApos = $this->get(route('financeiro.relatorios.fluxo-caixa', ['ano' => 2026]));
        $resFluxoApos->assertViewHas('totalReceitasAno', 0.00);
    }

    /** T26 — Todas as rotas registradas resolvem para ações existentes */
    public function test_t26_todas_rotas_possuem_acoes_existentes(): void
    {
        $routes = Route::getRoutes()->getRoutes();
        $rotasFinanceiro = array_filter($routes, function ($r) {
            return str_starts_with($r->getName() ?? '', 'financeiro.');
        });

        $this->assertNotEmpty($rotasFinanceiro);

        foreach ($rotasFinanceiro as $route) {
            $action = $route->getAction('uses');
            if (is_string($action)) {
                $parts = explode('@', $action);
                $controller = $parts[0];
                $method = $parts[1] ?? '__invoke';

                $this->assertTrue(
                    class_exists($controller),
                    "Controller {$controller} não existe para a rota {$route->getName()}."
                );

                $this->assertTrue(
                    method_exists($controller, $method),
                    "Método {$method} não existe no controller {$controller} para a rota {$route->getName()}."
                );
            }
        }
    }

    /** T27 — RateioResponsavelService: Distribuição proporcional, soma exata 100% e principal único */
    public function test_t27_rateio_service_distribuicao_e_validacoes(): void
    {
        AlunoResponsavel::where('aluno_id', 1)->delete();

        $resp2 = ResponsavelFinanceiro::firstOrCreate(['cpf_cnpj' => '98765432100'], [
            'nome' => 'Responsavel Teste 2',
            'email' => 'resp2@teste.com',
            'telefone_celular' => '11988888888',
            'cep' => '01001000',
            'logradouro' => 'Rua B',
            'numero' => '200',
            'bairro' => 'Bairro B',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'ativo' => true,
        ]);

        $service = app(\iEducar\Financeiro\Services\RateioResponsavelService::class);

        // Vínculo do primeiro responsável: 100%
        $service->vincularComAjusteRateio(1, 1, 100.0, true);
        $v1 = AlunoResponsavel::where('aluno_id', 1)->where('responsavel_id', 1)->first();
        $this->assertEquals(100.00, (float) $v1->percentual_rateio);
        $this->assertTrue((bool)$v1->responsavel_principal);

        // Vínculo do segundo responsável: ajusta proporcionalmente para 50/50
        $service->vincularComAjusteRateio(1, $resp2->id, 50.0, false);
        $v1->refresh();
        $v2 = AlunoResponsavel::where('aluno_id', 1)->where('responsavel_id', $resp2->id)->first();
        $this->assertEquals(50.00, (float) $v1->percentual_rateio);
        $this->assertEquals(50.00, (float) $v2->percentual_rateio);

        // Soma total deve ser rigorosamente 100.00%
        $soma = AlunoResponsavel::where('aluno_id', 1)->sum('percentual_rateio');
        $this->assertEquals(100.00, round((float) $soma, 2));

        // Rejeita rateio que não fecha em 100%
        $this->expectException(\InvalidArgumentException::class);
        $service->definirRateio(1, [
            1 => 60.0,
            $resp2->id => 30.0, // Soma = 90% != 100%
        ], 1);
    }

    /** T28 — CalculoEncargos: Dias de atraso sempre positivos e respeito à tolerância */
    public function test_t28_calculo_encargos_e_tolerancia(): void
    {
        $hoje = Carbon::today();
        $vencimentoPassado = $hoje->copy()->subDays(15);
        $vencimentoFuturo = $hoje->copy()->addDays(10);

        // Vencido há 15 dias: diasAtraso deve ser rigorosamente 15
        $diasAtraso = \iEducar\Financeiro\Support\CalculoEncargos::diasAtraso($vencimentoPassado->toDateString());
        $this->assertEquals(15, $diasAtraso);

        // A vencer no futuro: diasAtraso deve ser 0 (nunca negativo)
        $diasFuturo = \iEducar\Financeiro\Support\CalculoEncargos::diasAtraso($vencimentoFuturo->toDateString());
        $this->assertEquals(0, $diasFuturo);

        // Encargos calculados para R$ 1.000,00 com atraso de 15 dias: Multa 2% (20,00) + Juros 1% a.m. pro-rata (5,00)
        $multa = \iEducar\Financeiro\Support\CalculoEncargos::calcularMulta(1000.00, 15);
        $juros = \iEducar\Financeiro\Support\CalculoEncargos::calcularJuros(1000.00, 15);
        $this->assertEquals(20.00, $multa);
        $this->assertEquals(5.00, $juros);
    }

    /** T29 — Navegação Cruzada: Filtros contextuais por responsavel_id e contrato_id */
    public function test_t29_navegacao_cruzada_filtros_e_botoes(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-03',
            'data_vencimento' => '2026-03-10',
            'valor_nominal' => 300.00,
            'valor_saldo' => 300.00,
            'status' => 'aberto',
        ]);

        // Consulta filtrando por responsavel_id
        $resResp = $this->get(route('financeiro.titulos.index', ['responsavel_id' => 1]));
        $resResp->assertStatus(200);
        $resResp->assertSee('Responsavel Teste 1');
        $resResp->assertViewHas('responsavelId', 1);

        // Detalhe do título possui link para a ficha cadastral do responsável
        $resShow = $this->get(route('financeiro.titulos.show', $titulo->id));
        $resShow->assertStatus(200);
        $resShow->assertSee(route('financeiro.responsaveis.show', 1));
    }

    /** T30 — RBAC: Permissões de processo bloqueiam perfil não autorizado e liberam administrador */
    public function test_t30_rbac_permissao_por_perfil(): void
    {
        $adminUser = new class implements \Illuminate\Contracts\Auth\Authenticatable {
            use \Illuminate\Auth\Authenticatable;
            public $id = 1;
            public $tipo_usuario_id = 1;
            public function getAuthIdentifier() { return $this->id; }
        };

        $nonPrivilegedUser = new class implements \Illuminate\Contracts\Auth\Authenticatable {
            use \Illuminate\Auth\Authenticatable;
            public $id = 99;
            public $tipo_usuario_id = 99;
            public function getAuthIdentifier() { return $this->id; }
        };

        \iEducar\Financeiro\Http\Middleware\AutorizarProcessoFinanceiro::limparCache();

        // Insere o menu do processo na tabela de menus
        $menuId = DB::table('menus')->insertGetId([
            'name' => 'Baixa Manual',
            'process' => \iEducar\Financeiro\Constants\ProcessosFinanceiro::MENU_BAIXA_MANUAL,
        ]);

        // Administrador (tipo 1) é sempre autorizado
        $this->assertTrue(
            \iEducar\Financeiro\Http\Middleware\AutorizarProcessoFinanceiro::verificarPermissao($adminUser, 'MENU_BAIXA_MANUAL', 'cadastra')
        );

        // Usuário comum sem vínculo na tabela de permissões não tem acesso
        $this->assertFalse(
            \iEducar\Financeiro\Http\Middleware\AutorizarProcessoFinanceiro::verificarPermissao($nonPrivilegedUser, 'MENU_BAIXA_MANUAL', 'cadastra')
        );
    }

    /** T31 — API key do Gateway: Criptografia no banco e mascaramento na view */
    public function test_t31_api_key_criptografada_e_mascarada(): void
    {
        $rawSecret = '$sec_teste_chave_muito_secreta_9999';

        $gateway = \iEducar\Financeiro\Models\GatewayConfiguracao::updateOrCreate(
            ['gateway' => 'asaas'],
            [
                'environment' => 'sandbox',
                'api_key' => $rawSecret,
                'ativo' => true,
            ]
        );

        // 1. No banco de dados, o campo api_key deve estar cifrado (não texto puro)
        $rawInDb = DB::table('financeiro.gateway_configuracoes')->where('gateway', 'asaas')->value('api_key');
        $this->assertNotEquals($rawSecret, $rawInDb);

        // 2. No model Eloquent, o cast decriptografa de volta
        $this->assertEquals($rawSecret, $gateway->fresh()->api_key);

        // 3. Na view, a chave secreta NUNCA é renderizada em texto puro
        $response = $this->get(route('financeiro.configuracoes.gateway'));
        $response->assertStatus(200);
        $response->assertDontSee($rawSecret);
        $response->assertSee('••••••••');
    }

    /** T32 — Contas a Pagar: escola_id é persistido com integridade */
    public function test_t32_contas_pagar_escola_id_persistido(): void
    {
        $response = $this->post(route('financeiro.contas-pagar.store'), [
            'escola_id' => 1,
            'descricao' => 'Serviço de Manutenção Elétrica',
            'valor' => 450.00,
            'data_competencia' => '2026-03-01',
            'data_vencimento' => '2026-03-20',
            'plano_conta_id' => 1,
        ]);

        $response->assertRedirect(route('financeiro.contas-pagar.index'));

        $lancamento = Lancamento::where('descricao', 'Serviço de Manutenção Elétrica')->first();
        $this->assertNotNull($lancamento);
        $this->assertEquals(1, $lancamento->escola_id);
        $this->assertEquals(450.00, (float) $lancamento->valor);
    }

    /** T33 — Responsável Financeiro: Métodos de cadastro completo e guardas de finalidade */
    public function test_t33_responsavel_cadastro_incompleto_e_completo(): void
    {
        // Cadastro rápido com dados mínimos (apenas nome e CPF válido)
        $resp = ResponsavelFinanceiro::create([
            'nome' => 'Responsavel Cadastro Minimo',
            'cpf_cnpj' => '11144477735', // CPF matematicamente válido
            'ativo' => true,
        ]);

        $this->assertFalse($resp->cadastroCompleto());
        $this->assertTrue($resp->podeEmitirAsaas()); // Asaas requer nome e CPF
        $this->assertFalse($resp->podeReceberNotificacaoWhatsApp()); // Requer telefone celular
        $this->assertFalse($resp->podeReceberEmail()); // Requer email

        // Atualiza preenchendo todos os dados de contato e endereço
        $resp->update([
            'email' => 'completo@teste.com',
            'telefone_celular' => '11977776666',
            'cep' => '01310100',
            'logradouro' => 'Av Paulista',
            'numero' => '1000',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
        ]);

        $this->assertTrue($resp->fresh()->cadastroCompleto());
        $this->assertTrue($resp->fresh()->podeReceberNotificacaoWhatsApp());
        $this->assertTrue($resp->fresh()->podeReceberEmail());
    }

    /** T34 — CarneService: Elegibilidade para carnê Asaas (§7.2 / FEAT-01) */
    public function test_t34_carne_service_elegibilidade(): void
    {
        $contrato = Contrato::create([
            'numero_contrato' => 'CTR-CARNE-TESTE',
            'aluno_id' => 1,
            'matricula_id' => 1,
            'responsavel_financeiro_id' => 1,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 600.00,
            'valor_total_descontos' => 0.00,
            'valor_total_liquido' => 600.00,
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);

        $t1 = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 2,
            'competencia' => '2026-04',
            'data_vencimento' => Carbon::today()->addDays(30)->toDateString(),
            'valor_nominal' => 300.00,
            'valor_saldo' => 300.00,
            'status' => 'aberto',
        ]);

        $t2 = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 2,
            'total_parcelas' => 2,
            'competencia' => '2026-05',
            'data_vencimento' => Carbon::today()->addDays(60)->toDateString(),
            'valor_nominal' => 300.00,
            'valor_saldo' => 300.00,
            'status' => 'aberto',
        ]);

        $service = app(\iEducar\Financeiro\Services\CarneService::class);

        // Elegível: 2 parcelas mensais futuras do mesmo contrato
        $resElegivel = $service->verificarElegibilidade([$t1, $t2]);
        $this->assertTrue($resElegivel['elegivel']);
        $this->assertEmpty($resElegivel['motivos']);

        // Inelegível se uma das parcelas for alterada para status pago
        $t1->status = 'pago';
        $resInelegivel = $service->verificarElegibilidade([$t1, $t2]);
        $this->assertFalse($resInelegivel['elegivel']);
        $this->assertNotEmpty($resInelegivel['motivos']);
    }

    /** T35 — Sincronização de cobranças Asaas (Comando e Rota em Lote - T2.6 / BUG-019 / BUG-027.1) */
    public function test_t35_sincronizacao_cobrancas_comando_e_rota_lote(): void
    {
        $contrato = Contrato::firstOrCreate(['numero_contrato' => 'CTR-SYNC-TEST'], [
            'aluno_id' => 1,
            'matricula_id' => 1,
            'responsavel_financeiro_id' => 1,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 500.00,
            'valor_total_descontos' => 0.00,
            'valor_total_liquido' => 500.00,
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);

        $titulo = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-03',
            'data_vencimento' => Carbon::today()->addDays(10)->toDateString(),
            'valor_nominal' => 250.00,
            'valor_saldo' => 250.00,
            'status' => 'aberto',
        ]);

        $cobranca = CobrancaGateway::create([
            'titulo_id' => $titulo->id,
            'contrato_id' => $contrato->id,
            'gateway' => 'asaas',
            'gateway_charge_id' => 'pay_mock_test_123',
            'status_externo' => 'PENDING',
            'link_pagamento' => 'https://sandbox.asaas.com/i/test',
            'ativa' => true,
        ]);

        // Mock do driver Asaas via GatewayManager
        $mockDriver = \Mockery::mock(\iEducar\Financeiro\Gateways\Drivers\AsaasGateway::class);
        $mockDriver->shouldReceive('consultarCobranca')
            ->andReturn([
                'status' => 'RECEIVED',
                'value' => 250.00,
                'billingType' => 'BOLETO',
                'paymentDate' => Carbon::today()->toDateString(),
                'identificationField' => '00190.00009 01234.567890 12345.678901 1 99990000025000',
                'barCode' => '00191999900000250000000001234567890123456789',
            ]);

        $mockManager = \Mockery::mock(\iEducar\Financeiro\Gateways\GatewayManager::class);
        $mockManager->shouldReceive('driver')->with('asaas')->andReturn($mockDriver);
        $this->app->instance(\iEducar\Financeiro\Gateways\GatewayManager::class, $mockManager);

        // 1. Testa execução do comando de console
        $exitCode = \Illuminate\Support\Facades\Artisan::call('financeiro:sincronizar-asaas', ['--limit' => 5]);
        $this->assertEquals(0, $exitCode);

        $titulo->refresh();
        $this->assertEquals('pago', $titulo->status);
        $this->assertEquals(0.00, (float) $titulo->valor_saldo);
        $this->assertEquals('RECEIVED', $titulo->cobrancaGateway->status_externo);

        // 2. Testa rota de sincronização em lote pela web
        $titulo2 = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 2,
            'total_parcelas' => 2,
            'competencia' => '2026-04',
            'data_vencimento' => Carbon::today()->addDays(40)->toDateString(),
            'valor_nominal' => 250.00,
            'valor_saldo' => 250.00,
            'status' => 'aberto',
        ]);

        CobrancaGateway::create([
            'titulo_id' => $titulo2->id,
            'contrato_id' => $contrato->id,
            'gateway' => 'asaas',
            'gateway_charge_id' => 'pay_mock_test_456',
            'status_externo' => 'PENDING',
            'ativa' => true,
        ]);

        $response = $this->post(route('financeiro.titulos.sincronizar-lote-asaas'), [
            'titulo_ids' => [$titulo2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('pago', $titulo2->fresh()->status);
    }

    /** T36 — Atualização de status noturna de títulos (T4.1 / BUG-022) */
    public function test_t36_comando_atualizar_status_titulos(): void
    {
        $contrato = Contrato::firstOrCreate(['numero_contrato' => 'CTR-STATUS-TEST'], [
            'aluno_id' => 1,
            'matricula_id' => 1,
            'responsavel_financeiro_id' => 1,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 300.00,
            'valor_total_descontos' => 0.00,
            'valor_total_liquido' => 300.00,
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);

        // Título 1: Aberto e vencido há 5 dias
        $tVencido = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 3,
            'competencia' => '2026-02',
            'data_vencimento' => Carbon::today()->subDays(5)->toDateString(),
            'valor_nominal' => 150.00,
            'valor_saldo' => 150.00,
            'status' => 'aberto',
        ]);

        // Título 2: Parcial mas com saldo zerado residual
        $tQuitado = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 2,
            'total_parcelas' => 3,
            'competencia' => '2026-03',
            'data_vencimento' => Carbon::today()->subDays(2)->toDateString(),
            'valor_nominal' => 150.00,
            'valor_saldo' => 0.00,
            'status' => 'parcial',
        ]);

        $exitCode = \Illuminate\Support\Facades\Artisan::call('financeiro:atualizar-status');
        $this->assertEquals(0, $exitCode);

        $this->assertEquals('vencido', $tVencido->fresh()->status);
        $this->assertEquals('pago', $tQuitado->fresh()->status);
    }

    /** T37 — Caixa / Baixa Ágil: API de busca incremental e recálculo dinâmico (T3.1 / FEAT-02 / BUG-017) */
    public function test_t37_caixa_api_busca_e_calculo(): void
    {
        $contrato = Contrato::firstOrCreate(['numero_contrato' => 'CTR-CAIXA-TEST'], [
            'aluno_id' => 1,
            'matricula_id' => 1,
            'responsavel_financeiro_id' => 1,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 200.00,
            'valor_total_descontos' => 0.00,
            'valor_total_liquido' => 200.00,
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);

        $titulo = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-03',
            'data_vencimento' => Carbon::today()->addDays(5)->toDateString(),
            'valor_nominal' => 200.00,
            'valor_saldo' => 200.00,
            'status' => 'aberto',
        ]);

        // 1. Testa busca incremental por texto
        $resBusca = $this->getJson(route('financeiro.api.titulos.buscar', ['q' => 'Responsavel']));
        $resBusca->assertOk();
        $resBusca->assertJsonStructure(['data']);

        // 2. Testa API de cálculo de encargos e total a receber
        $resCalculo = $this->getJson(route('financeiro.api.titulos.calculo', ['id' => $titulo->id]));
        $resCalculo->assertOk();
        $resCalculo->assertJsonStructure([
            'titulo_id',
            'saldo',
            'dias_atraso',
            'multa',
            'juros',
            'desconto_pontualidade',
            'total_receber',
        ]);
        $this->assertEquals(200.00, $resCalculo->json('saldo'));
    }

    /** T38 — Disparo WhatsApp com resposta JSON e proteção contra ausência de telefone (T3.6 / BUG-033) */
    public function test_t38_whatsapp_notificacao_json_e_validacao_telefone(): void
    {
        $respSemTel = ResponsavelFinanceiro::create([
            'nome' => 'Responsavel Sem Telefone',
            'cpf_cnpj' => '22233344455',
            'ativo' => true,
        ]);

        $contrato = Contrato::create([
            'numero_contrato' => 'CTR-WPP-TEST',
            'aluno_id' => 1,
            'matricula_id' => 999,
            'responsavel_financeiro_id' => $respSemTel->id,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 100.00,
            'valor_total_descontos' => 0.00,
            'valor_total_liquido' => 100.00,
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);

        $titulo = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 1,
            'responsavel_financeiro_id' => $respSemTel->id,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-03',
            'data_vencimento' => Carbon::today()->toDateString(),
            'valor_nominal' => 100.00,
            'valor_saldo' => 100.00,
            'status' => 'aberto',
        ]);

        // Sem telefone celular: não deve dar 500, deve retornar 422 JSON
        $response = $this->getJson(route('financeiro.titulos.notificar-whatsapp', $titulo->id));
        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));

        // Atualiza com telefone válido
        $respSemTel->update(['telefone_celular' => '11988887777']);

        $responseOk = $this->getJson(route('financeiro.titulos.notificar-whatsapp', $titulo->id));
        $responseOk->assertOk();
        $this->assertTrue($responseOk->json('success'));
        $this->assertStringContainsString('api.whatsapp.com', $responseOk->json('url'));
    }

    /** T39 — Tela de Matrículas sem Contrato e Filtro por Ano em Títulos (T3.2 e T3.9 / BUG-014 / BUG-018) */
    public function test_t39_matriculas_sem_contrato_e_filtro_ano(): void
    {
        $responseMatriculas = $this->get(route('financeiro.contratos.matriculas-sem-contrato', ['ano_letivo' => 2026]));
        $responseMatriculas->assertOk();

        $responseTitulos = $this->get(route('financeiro.titulos.index', ['ano' => 2026]));
        $responseTitulos->assertOk();
    }

    /** T33 — Unicidade de Contratos por Matrícula/Ano, Snapshot e Cancelamento Auditado (T3.3 / BUG-023) */
    public function test_t33_contrato_duplicidade_e_snapshot(): void
    {
        $contratoService = app(ContratoService::class);

        $plano = PlanoPagamento::create([
            'nome' => 'Plano 2026 Teste Snapshot',
            'ano_letivo' => 2026,
            'escola_id' => 1,
            'valor_total' => 1200.00,
            'qtd_parcelas' => 2,
            'ativo' => true,
        ]);
        \iEducar\Financeiro\Models\PlanoItem::create([
            'plano_id' => $plano->id,
            'tipo_item' => 'mensalidade',
            'descricao' => 'Parcela 1',
            'numero_parcela' => 1,
            'valor' => 600.00,
            'mes_competencia' => 1,
            'dia_vencimento_padrao' => 10,
        ]);
        \iEducar\Financeiro\Models\PlanoItem::create([
            'plano_id' => $plano->id,
            'tipo_item' => 'mensalidade',
            'descricao' => 'Parcela 2',
            'numero_parcela' => 2,
            'valor' => 600.00,
            'mes_competencia' => 2,
            'dia_vencimento_padrao' => 10,
        ]);

        DB::table('pmieducar.matricula')->insertOrIgnore([
            'cod_matricula' => 10,
            'ref_cod_aluno' => 1,
            'ref_ref_cod_escola' => 1,
            'ano' => 2026,
            'ativo' => 1,
        ]);

        $dadosContrato = [
            'aluno_id' => 1,
            'matricula_id' => 10,
            'plano_id' => $plano->id,
            'ano_letivo' => 2026,
            'escola_id' => 1,
            'dia_vencimento' => 10,
            'responsavel_financeiro_id' => 1,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
        ];

        // 1. Cria primeiro contrato com snapshot
        $contrato = $contratoService->criarContratoEParcelas($dadosContrato);
        $this->assertNotNull($contrato->id);
        $this->assertNotEmpty($contrato->plano_snapshot);
        $this->assertEquals(2, count($contrato->plano_snapshot['itens']));

        // 2. Tentar criar segundo contrato ativo para a mesma matrícula no mesmo ano deve falhar
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Já existe um contrato ativo para a matrícula #10 no ano letivo 2026');
        $contratoService->criarContratoEParcelas($dadosContrato);
    }

    /** T33b — Cancelamento e Títulos Faltantes via Snapshot */
    public function test_t33b_cancelamento_e_titulos_faltantes(): void
    {
        $contratoService = app(ContratoService::class);

        DB::table('pmieducar.matricula')->insertOrIgnore([
            'cod_matricula' => 11,
            'ref_cod_aluno' => 1,
            'ref_ref_cod_escola' => 1,
            'ano' => 2026,
            'ativo' => 1,
        ]);

        $plano = PlanoPagamento::create([
            'nome' => 'Plano 2026 Teste 2',
            'ano_letivo' => 2026,
            'escola_id' => 1,
            'valor_total' => 600.00,
            'qtd_parcelas' => 1,
            'ativo' => true,
        ]);
        \iEducar\Financeiro\Models\PlanoItem::create([
            'plano_id' => $plano->id,
            'tipo_item' => 'mensalidade',
            'descricao' => 'Parcela Única',
            'numero_parcela' => 1,
            'valor' => 600.00,
            'mes_competencia' => 5,
            'dia_vencimento_padrao' => 10,
        ]);

        $contrato = $contratoService->criarContratoEParcelas([
            'aluno_id' => 1,
            'matricula_id' => 11,
            'plano_id' => $plano->id,
            'ano_letivo' => 2026,
            'escola_id' => 1,
            'dia_vencimento' => 10,
            'responsavel_financeiro_id' => 1,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
        ]);

        // Testa alias gerarTitulosFaltantes
        $gerados = $contratoService->gerarTitulosFaltantes($contrato);
        $this->assertEquals(0, $gerados, 'Títulos já existem, não deve gerar duplicatas');

        // Cancela contrato
        $contratoService->cancelarContrato($contrato, 'Rescisão a pedido do responsável');
        $contrato->refresh();
        $this->assertEquals('cancelado', $contrato->status);
        $this->assertNotNull($contrato->cancelado_em);

        // Cancelar novamente deve lançar exceção
        try {
            $contratoService->cancelarContrato($contrato, 'Tentativa duplicada');
            $this->fail('Deveria ter lançado exceção de contrato já cancelado.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('já se encontra cancelado', $e->getMessage());
        }
    }

    /** T34 — Copiar Plano para Novo Ano e Edição de Plano Sem Contratos (T3.4 / BUG-024 / FEAT-08) */
    public function test_t34_planos_copiar_e_editar(): void
    {
        $plano = PlanoPagamento::create([
            'nome' => 'Plano Fundamental 2026',
            'ano_letivo' => 2026,
            'escola_id' => 1,
            'valor_total' => 2400.00,
            'qtd_parcelas' => 2,
            'ativo' => true,
        ]);
        \iEducar\Financeiro\Models\PlanoItem::create([
            'plano_id' => $plano->id,
            'tipo_item' => 'mensalidade',
            'descricao' => 'Mensalidade 1',
            'numero_parcela' => 1,
            'valor' => 1200.00,
            'mes_competencia' => 1,
            'dia_vencimento_padrao' => 10,
        ]);

        // 1. Copia o plano para 2027
        $responseCopiar = $this->post(route('financeiro.planos.copiar', $plano->id), [
            'novo_ano_letivo' => 2027,
            'novo_nome' => 'Plano Fundamental 2027',
        ]);
        $responseCopiar->assertRedirect();

        $plano2027 = PlanoPagamento::where('nome', 'Plano Fundamental 2027')->first();
        $this->assertNotNull($plano2027);
        $this->assertEquals(2027, $plano2027->ano_letivo);
        $this->assertEquals(1, $plano2027->itens()->count());

        // 2. Edição de plano sem contratos
        $responseUpdate = $this->put(route('financeiro.planos.update', $plano2027->id), [
            'escola_id' => 1,
            'ano_letivo' => 2027,
            'nome' => 'Plano Fundamental 2027 Editado',
            'valor_total' => 3000.00,
            'qtd_parcelas' => 3,
            'dia_vencimento_padrao' => 15,
        ]);
        $responseUpdate->assertRedirect();
        $plano2027->refresh();
        $this->assertEquals('Plano Fundamental 2027 Editado', $plano2027->nome);
        $this->assertEquals(3, $plano2027->itens()->count());
    }

    /** T35 — Impedimentos na Exclusão e Restauração de Soft Delete em Responsáveis (T3.5 / BUG-025) */
    public function test_t35_responsavel_impedimentos_e_softdelete(): void
    {
        $resp = ResponsavelFinanceiro::create([
            'nome' => 'Resp Teste Exclusao',
            'cpf_cnpj' => '52998224725',
            'email' => 'resp.exclusao@teste.com',
            'telefone_celular' => '11977776666',
            'cep' => '01001000',
            'logradouro' => 'Rua B',
            'numero' => '20',
            'bairro' => 'Centro',
            'cidade' => 'SP',
            'estado' => 'SP',
            'ativo' => true,
        ]);

        // Cria título aberto para impedir exclusão
        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => $resp->id,
            'escola_id' => 1,
            'tipo' => 'taxa_avulsa',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-04',
            'data_vencimento' => '2026-04-10',
            'valor_nominal' => 50.00,
            'valor_saldo' => 50.00,
            'status' => 'aberto',
        ]);

        // 1. Tentar excluir deve falhar com erro
        $responseDelete = $this->delete(route('financeiro.responsaveis.destroy', $resp->id));
        $responseDelete->assertSessionHas('error');
        $this->assertDatabaseHas('financeiro.responsaveis_financeiros', ['id' => $resp->id, 'deleted_at' => null]);

        // Remove título e exclui
        $titulo->forceDelete();
        $this->delete(route('financeiro.responsaveis.destroy', $resp->id));
        $this->assertSoftDeleted('financeiro.responsaveis_financeiros', ['id' => $resp->id]);

        // 2. Restauração ao tentar cadastrar o mesmo CPF com flag restaurar
        $responseRestore = $this->post(route('financeiro.responsaveis.store'), [
            'nome' => 'Resp Teste Restaurado',
            'cpf_cnpj' => '52998224725',
            'email' => 'resp.restaurado@teste.com',
            'telefone_celular' => '11977776666',
            'cep' => '01001000',
            'logradouro' => 'Rua B',
            'numero' => '20',
            'bairro' => 'Centro',
            'cidade' => 'SP',
            'estado' => 'SP',
            'restaurar' => 1,
        ]);
        $responseRestore->assertRedirect();
        $this->assertDatabaseHas('financeiro.responsaveis_financeiros', [
            'id' => $resp->id,
            'deleted_at' => null,
            'nome' => 'Resp Teste Restaurado'
        ]);
    }

    /** T38 — Contas a Pagar: Liquidação, Conciliação Bancária, Extrato e Estorno (T3.8 / BUG-026 / FEAT-10) */
    public function test_t38_contas_pagar_liquidacao_saldo_e_estorno(): void
    {
        $conta = \iEducar\Financeiro\Models\ContaBancaria::firstOrCreate([
            'nome' => 'Conta Operacional Teste T38',
        ], [
            'tipo' => 'corrente',
            'saldo_inicial' => 1000.00,
            'saldo_atual' => 1000.00,
            'ativo' => true,
        ]);
        $conta->update(['saldo_atual' => 1000.00]);

        $planoConta = \iEducar\Financeiro\Models\PlanoConta::create([
            'codigo' => '2.1.09',
            'nome' => 'Material Didático Teste',
            'tipo' => 'despesa',
            'ativo' => true,
        ]);

        $despesa = \iEducar\Financeiro\Models\Lancamento::create([
            'tipo' => 'despesa',
            'descricao' => 'Compra de Livros Didáticos',
            'fornecedor_nome' => 'Editora Alpha',
            'valor' => 300.00,
            'data_competencia' => '2026-03-01',
            'data_vencimento' => '2026-03-15',
            'plano_conta_id' => $planoConta->id,
            'status' => 'a_pagar',
        ]);

        // 1. Tela show
        $responseShow = $this->get(route('financeiro.contas-pagar.show', $despesa->id));
        $responseShow->assertOk();
        $responseShow->assertSee('Compra de Livros Didáticos');

        // 2. Liquidação com débito bancário
        $responseLiquidar = $this->post(route('financeiro.contas-pagar.liquidar', $despesa->id), [
            'data_pagamento' => Carbon::today()->toDateString(),
            'conta_bancaria_id' => $conta->id,
            'valor_pago' => 300.00,
            'forma_pagamento' => 'pix',
        ]);
        $responseLiquidar->assertRedirect();

        $despesa->refresh();
        $conta->refresh();

        $this->assertEquals('pago', $despesa->status);
        $this->assertEquals(700.00, (float) $conta->saldo_atual, 'Saldo da conta bancária deve diminuir em R$ 300');

        // Confere registro em movimentacoes_conta
        $movSaida = \iEducar\Financeiro\Models\MovimentacaoConta::where('origem_id', $despesa->id)
            ->where('tipo', 'saida')
            ->first();
        $this->assertNotNull($movSaida);
        $this->assertEquals(300.00, (float) $movSaida->valor);
        $this->assertEquals(700.00, (float) $movSaida->saldo_posterior);

        // 3. Estorno da Liquidação
        $responseEstorno = $this->post(route('financeiro.contas-pagar.estornar', $despesa->id));
        $responseEstorno->assertRedirect();

        $despesa->refresh();
        $conta->refresh();

        $this->assertEquals('a_pagar', $despesa->status);
        $this->assertNull($despesa->data_pagamento);
        $this->assertEquals(1000.00, (float) $conta->saldo_atual, 'Saldo bancário deve ser recomposto para R$ 1000');

        $movEntrada = \iEducar\Financeiro\Models\MovimentacaoConta::where('origem_id', $despesa->id)
            ->where('tipo', 'entrada')
            ->first();
        $this->assertNotNull($movEntrada);
        $this->assertEquals(300.00, (float) $movEntrada->valor);
        $this->assertEquals(1000.00, (float) $movEntrada->saldo_posterior);
    }

    /** T42 — Resolução em Lote de Alunos e Turmas via AlunoLookup (T4.2 / BUG-030) */
    public function test_t42_aluno_lookup_eager_loading_e_memoizacao(): void
    {
        \iEducar\Financeiro\Support\AlunoLookup::limparCache();
        \iEducar\Financeiro\Support\AlunoLookup::definirNome(101, 'João da Silva Santos');
        \iEducar\Financeiro\Support\AlunoLookup::definirTurma(101, '5º Ano Fundamental A', 2026);

        $contrato = Contrato::firstOrCreate(['numero_contrato' => 'CTR-LOOKUP-TEST'], [
            'aluno_id' => 101,
            'matricula_id' => 101,
            'responsavel_financeiro_id' => 1,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 200.00,
            'valor_total_descontos' => 0.00,
            'valor_total_liquido' => 200.00,
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);

        $titulo = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 101,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-05',
            'data_vencimento' => '2026-05-10',
            'valor_nominal' => 200.00,
            'valor_saldo' => 200.00,
            'status' => 'aberto',
        ]);

        $this->assertEquals('João da Silva Santos', $titulo->aluno_nome);
        $this->assertEquals('5º Ano Fundamental A', $titulo->turma_nome);

        // Pre-carregamento em lote
        \iEducar\Financeiro\Support\AlunoLookup::carregarParaTitulos([$titulo], 2026);
        $this->assertEquals('João da Silva Santos', \iEducar\Financeiro\Support\AlunoLookup::nome(101));
        $this->assertEquals('5º Ano Fundamental A', \iEducar\Financeiro\Support\AlunoLookup::turma(101, 2026));
    }

    /** T43 — Conceitos Estritos de DRE, Fluxo de Caixa e Exportação CSV (T4.3 / BUG-021 / FEAT-11) */
    public function test_t43_dre_e_fluxo_caixa_conceitos_estritos_e_exportacao_csv(): void
    {
        $contrato = Contrato::firstOrCreate(['numero_contrato' => 'CTR-DRE-TEST'], [
            'aluno_id' => 1,
            'matricula_id' => 1,
            'responsavel_financeiro_id' => 1,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 100.00,
            'valor_total_descontos' => 0.00,
            'valor_total_liquido' => 100.00,
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);

        $titulo = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 1,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-06',
            'data_vencimento' => '2026-06-10',
            'valor_nominal' => 100.00,
            'valor_saldo' => 0.00,
            'status' => 'pago',
        ]);

        // Pagamento com nominal 100, desconto 10, juros/multa 3, valor_pago 93
        $pagamento = Pagamento::create([
            'titulo_id' => $titulo->id,
            'forma_pagamento' => 'pix',
            'data_pagamento' => '2026-06-12',
            'valor_pago' => 93.00,
            'valor_desconto_concedido' => 10.00,
            'valor_juros' => 2.00,
            'valor_multa' => 1.00,
            'estornado' => false,
        ]);

        // 1. Exportação DRE em CSV
        $responseDre = $this->get(route('financeiro.relatorios.dre', ['ano' => 2026, 'exportar' => 'csv']));
        $responseDre->assertOk();
        $this->assertStringContainsString('text/csv', $responseDre->headers->get('Content-Type'));

        // 2. Exportação Fluxo de Caixa em CSV
        $responseFluxo = $this->get(route('financeiro.relatorios.fluxo-caixa', ['ano' => 2026, 'exportar' => 'csv']));
        $responseFluxo->assertOk();
        $this->assertStringContainsString('text/csv', $responseFluxo->headers->get('Content-Type'));

        // 3. Verificação de Documento / Recibo público
        $hashValido = 'COMPROVANTE-TESTE-HASH-12345';
        $pagamento->update(['comprovante_hash' => $hashValido]);

        $responseVerif = $this->get(route('financeiro.verificar.documento', $hashValido));
        $responseVerif->assertOk();
        $responseVerif->assertJson(['valido' => true, 'id_pagamento' => $pagamento->id, 'valor_pago' => 93.00]);

        // Verificação com hash inválido
        $responseInvalido = $this->get(route('financeiro.verificar.documento', 'hash_inexistente_999'));
        $responseInvalido->assertStatus(404);
    }

    /** T44 — Inadimplência: Consulta Consultiva de Rematrícula (T4.4 / BUG-028) */
    public function test_t44_inadimplencia_check_service_e_api_rematricula(): void
    {
        $contrato = Contrato::firstOrCreate(['numero_contrato' => 'CTR-INAD-TEST'], [
            'aluno_id' => 102,
            'matricula_id' => 102,
            'responsavel_financeiro_id' => 1,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 500.00,
            'valor_total_descontos' => 0.00,
            'valor_total_liquido' => 500.00,
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);

        // Cria título vencido
        $titulo = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 102,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-01',
            'data_vencimento' => Carbon::today()->subDays(15)->toDateString(),
            'valor_nominal' => 500.00,
            'valor_saldo' => 500.00,
            'status' => 'vencido',
        ]);

        // 1. API pública de verificação de rematrícula
        $statusRematricula = \iEducar\Financeiro\Support\FinanceiroInadimplencia::alunoInadimplente(102);
        $this->assertTrue($statusRematricula['inadimplente']);
        $this->assertFalse($statusRematricula['pode_rematricular']);
        $this->assertEquals(500.00, $statusRematricula['total_debito']);
        $this->assertEquals(1, $statusRematricula['qtd_titulos_vencidos']);

        // 2. Inadimplência do responsável (não deve lançar RelationNotFoundException)
        $statusResp = \iEducar\Financeiro\Support\FinanceiroInadimplencia::responsavelInadimplente(1);
        $this->assertTrue($statusResp['inadimplente']);
        $this->assertGreaterThanOrEqual(500.00, $statusResp['total_debito']);

        // 3. Exportação de Inadimplência em CSV
        $responseInad = $this->get(route('financeiro.relatorios.inadimplencia', ['ano' => 2026, 'exportar' => 'csv']));
        $responseInad->assertOk();
        $this->assertStringContainsString('text/csv', $responseInad->headers->get('Content-Type'));
    }

    /** T45 — Régua de Cobrança: Canais Plugáveis e Janela de Tolerância (T4.5 / BUG-027) */
    public function test_t45_regua_cobranca_canais_e_tolerancia(): void
    {
        // 1. Testa drivers de canal de notificação
        $regra = \iEducar\Financeiro\Models\ReguaCobrancaRegra::create([
            'nome_regra' => 'Lembrete WhatsApp Teste',
            'tipo_gatilho' => 'antes_vencimento',
            'dias_gatilho' => -3,
            'canal' => 'whatsapp',
            'ativo' => true,
            'corpo_template' => 'Olá {responsavel}, lembrete do aluno {aluno}.',
        ]);

        $titulo = Titulo::where('status', 'aberto')->first();
        if (!$titulo) {
            $titulo = Titulo::create([
                'aluno_id' => 1,
                'responsavel_financeiro_id' => 1,
                'escola_id' => 1,
                'tipo' => 'mensalidade',
                'numero_parcela' => 1,
                'total_parcelas' => 1,
                'competencia' => '2026-07',
                'data_vencimento' => Carbon::today()->addDays(3)->toDateString(),
                'valor_nominal' => 180.00,
                'valor_saldo' => 180.00,
                'status' => 'aberto',
            ]);
        }

        $canalWa = new \iEducar\Financeiro\Services\ReguaCanais\WhatsAppLinkCanal();
        [$sucessoWa, $erroWa] = $canalWa->enviar($regra, $titulo, '11988887777', 'Teste de mensagem');
        $this->assertTrue($sucessoWa);
        $this->assertNull($erroWa);

        $canalMail = new \iEducar\Financeiro\Services\ReguaCanais\MailCanal();
        [$sucessoMail, $erroMail] = $canalMail->enviar($regra, $titulo, 'financeiro@teste.com', 'Teste de mensagem');
        $this->assertTrue($sucessoMail);
        $this->assertNull($erroMail);
    }

    /** T46 — Evento TituloPago: Payload Fiscal e Listener de NFS-e (T4.6 / BUG-029) */
    public function test_t46_titulo_pago_evento_payload_fiscal(): void
    {
        $resp = ResponsavelFinanceiro::first();
        $resp->update([
            'telefone_celular' => '11999998888',
            'logradouro' => 'Avenida Brasil',
            'numero' => '1500',
            'complemento' => 'Sala 12',
            'bairro' => 'Jardins',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'cep' => '01430000',
        ]);

        $titulo = Titulo::create([
            'aluno_id' => 1,
            'responsavel_financeiro_id' => $resp->id,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-08',
            'data_vencimento' => '2026-08-10',
            'valor_nominal' => 400.00,
            'valor_saldo' => 0.00,
            'status' => 'pago',
        ]);

        $pagamento = Pagamento::create([
            'titulo_id' => $titulo->id,
            'forma_pagamento' => 'pix',
            'data_pagamento' => '2026-08-10',
            'valor_pago' => 400.00,
            'estornado' => false,
        ]);

        $evento = new \iEducar\Financeiro\Events\TituloPago($titulo, $pagamento);

        $this->assertEquals('11999998888', $evento->payloadFiscal['tomador']['telefone']);
        $this->assertEquals('Avenida Brasil, nº 1500, Sala 12', $evento->payloadFiscal['tomador']['endereco']['logradouro']);
        $this->assertEquals('SP', $evento->payloadFiscal['tomador']['endereco']['estado']);
        $this->assertEquals(400.00, $evento->payloadFiscal['valor_servicos']);

        // Testa execução do listener sem erros
        $listener = new \iEducar\Financeiro\Listeners\EmitirNfseListener();
        $listener->handle($evento);
        $this->assertTrue(true);
    }

    /** T47 — Descontos com Escopo e Histórico em titulo_descontos (T4.7 / FEAT-06 / M11) */
    public function test_t47_descontos_com_escopo_e_registro_historico(): void
    {
        // 1. Cria desconto com escopo de aluno específico
        $descAluno = \iEducar\Financeiro\Models\Desconto::create([
            'nome' => 'Bolsa Aluno 105',
            'tipo' => 'percentual',
            'escopo' => 'aluno',
            'alvo_id' => 105,
            'valor_regra' => 20.00, // 20%
            'teto_valor' => 50.00,  // teto de R$ 50
            'cumulativo' => true,
            'vigencia_inicio' => '2026-01-01',
            'ativo' => true,
        ]);

        // Desconto para outro aluno (não deve ser aplicado)
        $descOutro = \iEducar\Financeiro\Models\Desconto::create([
            'nome' => 'Bolsa Outro Aluno',
            'tipo' => 'percentual',
            'escopo' => 'aluno',
            'alvo_id' => 999,
            'valor_regra' => 50.00,
            'vigencia_inicio' => '2026-01-01',
            'ativo' => true,
        ]);

        $titulo = Titulo::create([
            'aluno_id' => 105,
            'responsavel_financeiro_id' => 1,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-09',
            'data_vencimento' => '2026-09-10',
            'valor_nominal' => 300.00,
            'valor_saldo' => 300.00,
            'status' => 'aberto',
        ]);

        $service = new \iEducar\Financeiro\Services\DescontoService();
        $descontoCalculado = $service->calcularDescontoAplicavel($titulo, Carbon::parse('2026-09-05'));

        // 20% de 300 seria 60, mas o teto_valor é 50
        $this->assertEquals(50.00, $descontoCalculado);

        // 2. Aplica e registra formalmente no banco
        $aplicado = $service->aplicarERegistrar($titulo, Carbon::parse('2026-09-05'), 1, 'Concessão de bolsa anual');
        $this->assertEquals(50.00, $aplicado);

        $titulo->refresh();
        $this->assertEquals(50.00, (float) $titulo->valor_desconto);
        $this->assertEquals(250.00, (float) $titulo->valor_saldo);

        $this->assertDatabaseHas('financeiro.titulo_descontos', [
            'titulo_id' => $titulo->id,
            'desconto_id' => $descAluno->id,
            'valor_aplicado' => 50.00,
        ]);
    }

    /** T48 — Processamentos e Pendências: Painel e Resolução Manual (T4.8 / FEAT-05) */
    public function test_t48_processamentos_painel_e_acoes_pendencias(): void
    {
        // 1. Cria webhook com divergência
        $webhook = \iEducar\Financeiro\Models\WebhookEvent::create([
            'gateway' => 'asaas',
            'event_id' => 'evt_test_divergencia_t48',
            'event_type' => 'PAYMENT_RECEIVED',
            'payload' => ['id' => 'pay_test_123', 'value' => 150.00],
            'processado' => false,
            'requer_acao' => true,
            'erro' => 'Valor divergente do saldo esperado',
        ]);

        // 2. Acessa tela de processamentos
        $responseIndex = $this->get(route('financeiro.processamentos.index'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('Processamentos e Pendências');
        $responseIndex->assertSee('Valor divergente do saldo esperado');

        // 3. Ignora pendência com justificativa
        $responseIgnorar = $this->post(route('financeiro.processamentos.webhooks.ignorar', $webhook->id), [
            'motivo' => 'Diferença aceita pela diretoria conforme acordo',
        ]);
        $responseIgnorar->assertRedirect();

        $webhook->refresh();
        $this->assertTrue((bool) $webhook->processado);
        $this->assertFalse((bool) $webhook->requer_acao);
        $this->assertStringContainsString('Diferença aceita pela diretoria', $webhook->motivo_acao);
    }
}




