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
        $this->expectException(\Illuminate\Database\QueryException::class);
        CobrancaGateway::create([
            'titulo_id' => $titulo->id,
            'gateway' => 'asaas',
            'gateway_charge_id' => 'pay_first',
            'status_externo' => 'PENDING',
        ]);
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
}
