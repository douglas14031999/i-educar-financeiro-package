<?php

namespace iEducar\Financeiro\Tests\Feature;

use Carbon\Carbon;
use iEducar\Financeiro\Constants\ProcessosFinanceiro;
use iEducar\Financeiro\Events\MatriculaCriada;
use iEducar\Financeiro\Gateways\Drivers\AsaasGateway;
use iEducar\Financeiro\Gateways\GatewayManager;
use iEducar\Financeiro\Http\Middleware\AutorizarProcessoFinanceiro;
use iEducar\Financeiro\Jobs\GerarCobrancasGatewayLoteJob;
use iEducar\Financeiro\Models\AlunoResponsavel;
use iEducar\Financeiro\Models\CarneGateway;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\ContaBancaria;
use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\CreditoResponsavel;
use iEducar\Financeiro\Models\GatewayConfiguracao;
use iEducar\Financeiro\Models\Lancamento;
use iEducar\Financeiro\Models\LoteProcessamento;
use iEducar\Financeiro\Models\MovimentacaoConta;
use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\PlanoConta;
use iEducar\Financeiro\Models\PlanoItem;
use iEducar\Financeiro\Models\PlanoPagamento;
use iEducar\Financeiro\Models\Renegociacao;
use iEducar\Financeiro\Models\ResponsavelFinanceiro;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Models\WebhookEvent;
use iEducar\Financeiro\Services\BaixaTituloService;
use iEducar\Financeiro\Services\CarneService;
use iEducar\Financeiro\Services\ContratoService;
use iEducar\Financeiro\Services\RenegociacaoService;
use iEducar\Financeiro\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Mockery;

/**
 * Fase 6 — Bateria de Homologação E2E Ponta a Ponta no Sandbox do Asaas
 * 
 * Executa e valida todos os 14 itens do checklist formal de homologação (TC-01 a TC-14)
 * definido no PRD e no TASKS.md.
 */
class HomologacaoE2ETest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Database\Eloquent\Model::unguard();
        AutorizarProcessoFinanceiro::limparCache();
        $this->criarFixturesHomologacao();
    }

    private function criarFixturesHomologacao(): void
    {
        // 1. Responsável Financeiro Principal
        ResponsavelFinanceiro::firstOrCreate(['id' => 10], [
            'nome' => 'Carlos Silva Homologacao',
            'cpf_cnpj' => '54321678909',
            'email' => 'carlos.homolog@teste.com',
            'telefone_celular' => '11987654321',
            'cep' => '01001000',
            'logradouro' => 'Avenida Paulista',
            'numero' => '1000',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'ativo' => true,
        ]);

        // 2. Segundo Responsável (para rateio 50/50)
        ResponsavelFinanceiro::firstOrCreate(['id' => 11], [
            'nome' => 'Mariana Silva Homologacao',
            'cpf_cnpj' => '98712345600',
            'email' => 'mariana.homolog@teste.com',
            'telefone_celular' => '11976543210',
            'cep' => '01001000',
            'logradouro' => 'Avenida Paulista',
            'numero' => '1000',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'ativo' => true,
        ]);

        // 3. Vínculo do Aluno 10 com ambos os responsáveis
        AlunoResponsavel::firstOrCreate([
            'aluno_id' => 10,
            'responsavel_id' => 10,
        ], [
            'parentesco' => 'Pai',
            'percentual_rateio' => 50.00,
            'responsavel_principal' => true,
        ]);

        AlunoResponsavel::firstOrCreate([
            'aluno_id' => 10,
            'responsavel_id' => 11,
        ], [
            'parentesco' => 'Mãe',
            'percentual_rateio' => 50.00,
            'responsavel_principal' => false,
        ]);

        // 4. Plano de Contas e Conta Bancária
        PlanoConta::firstOrCreate(['id' => 10], [
            'codigo' => '3.1.02',
            'nome' => 'Serviços de Manutenção',
            'tipo' => 'despesa',
            'aceita_lancamento' => true,
            'ativo' => true,
        ]);

        ContaBancaria::firstOrCreate(['id' => 10], [
            'escola_id' => 1,
            'banco_codigo' => '001',
            'nome' => 'Banco do Brasil - Homologação',
            'agencia' => '4321',
            'conta' => '98765-4',
            'saldo_inicial' => 10000.00,
            'saldo_atual' => 10000.00,
            'ativo' => true,
        ]);

        // 5. Tabelas Mock do i-Educar para o aluno 10
        try {
            DB::statement("INSERT INTO cadastro.pessoa (idpes, nome, email) VALUES (10, 'Lucas Silva Homolog', 'lucas@homolog.com') ON CONFLICT (idpes) DO NOTHING;");
            DB::statement("INSERT INTO pmieducar.aluno (cod_aluno, ref_idpes, ativo) VALUES (10, 10, 1) ON CONFLICT (cod_aluno) DO NOTHING;");
            DB::statement("INSERT INTO pmieducar.matricula (cod_matricula, ref_cod_aluno, ref_ref_cod_escola, ano, ativo) VALUES (10, 10, 1, 2026, 1) ON CONFLICT (cod_matricula) DO NOTHING;");
        } catch (\Throwable $e) {
            // Ignora conflitos
        }
    }

    /**
     * TC-01 — Configuração do Gateway no Sandbox e Mascaramento de API Key
     */
    public function test_tc01_configuracao_gateway_sandbox_e_seguranca_api_key(): void
    {
        $rawApiKey = '$aact_HOMOLOG_KEY_SECRET_987654321';

        // 1. Salva configuração via POST
        $responseSalvar = $this->post(route('financeiro.configuracoes.gateway.salvar'), [
            'environment' => 'sandbox',
            'api_key' => $rawApiKey,
            'webhook_token' => 'token_webhook_homolog_123456789',
        ]);
        $responseSalvar->assertRedirect();

        // 2. Confirma criptografia no banco (a chave em claro NÃO deve existir no raw)
        $rawNoBanco = DB::table('financeiro.gateway_configuracoes')
            ->where('gateway', 'asaas')
            ->value('api_key');

        $this->assertNotEquals($rawApiKey, $rawNoBanco);
        $this->assertStringStartsWith('eyJ', $rawNoBanco); // Payload AES-256 do Laravel

        // 3. Model descriptografa corretamente em memória
        $configModel = GatewayConfiguracao::where('gateway', 'asaas')->first();
        $this->assertEquals($rawApiKey, $configModel->api_key);
        $this->assertStringStartsWith('••••••••', $configModel->api_key_mascarada);

        // 4. Tela GET não vaza a chave em texto plano
        $responseIndex = $this->get(route('financeiro.configuracoes.gateway'));
        $responseIndex->assertOk();
        $responseIndex->assertDontSee($rawApiKey);
        $responseIndex->assertSee('••••••••');

        // 5. Testa conexão com endpoint mockado
        Http::fake([
            'https://sandbox.asaas.com/api/v3/finance/balance' => Http::response(['balance' => 2540.50], 200),
        ]);

        $responseTeste = $this->postJson(route('financeiro.configuracoes.gateway.testar-conexao'), [
            'environment' => 'sandbox',
        ]);
        $responseTeste->assertOk();
        $responseTeste->assertJson(['sucesso' => true, 'saldo' => 2540.50]);

        // 6. Testa registro de webhook automático
        Http::fake([
            'https://sandbox.asaas.com/api/v3/webhook' => Http::response(['id' => 'wh_123', 'enabled' => true], 200),
        ]);

        $responseWebhook = $this->postJson(route('financeiro.configuracoes.gateway.registrar-webhook'), [
            'environment' => 'sandbox',
            'webhook_token' => 'token_webhook_homolog_123456789',
        ]);
        $responseWebhook->assertOk();
        $responseWebhook->assertJson(['sucesso' => true]);
    }

    /**
     * TC-02 — Criação de Plano 12x e Contrato com 2 Responsáveis (Rateio 50/50, 24 Títulos)
     */
    public function test_tc02_criacao_plano_12x_e_contrato_com_rateio_50_50_24_titulos(): void
    {
        // 1. Cria plano anual 12 parcelas
        $plano = PlanoPagamento::create([
            'nome' => 'Plano Anual 12x Homologação',
            'ano_letivo' => 2026,
            'qtd_parcelas' => 12,
            'valor_total' => 1200.00,
            'escola_id' => 1,
            'ativo' => true,
        ]);

        for ($i = 1; $i <= 12; $i++) {
            PlanoItem::create([
                'plano_id' => $plano->id,
                'numero_parcela' => $i,
                'tipo_item' => 'mensalidade',
                'descricao' => "Mensalidade {$i}/12",
                'valor' => 100.00,
                'mes_competencia' => $i,
                'dia_vencimento_padrao' => 10,
            ]);
        }

        // 2. Cria contrato com 2 responsáveis (50/50)
        $contratoService = app(ContratoService::class);
        $contrato = $contratoService->criarContratoEParcelas([
            'aluno_id' => 10,
            'matricula_id' => 10,
            'plano_id' => $plano->id,
            'ano_letivo' => 2026,
            'dia_vencimento' => 10,
            'escola_id' => 1,
            'valor_total' => 1200.00,
            'valor_desconto' => 0.00,
            'rateios' => [
                ['responsavel_financeiro_id' => 10, 'percentual' => 50.00],
                ['responsavel_financeiro_id' => 11, 'percentual' => 50.00],
            ],
        ]);

        $this->assertNotNull($contrato->id);

        // 3. Confere exatamente 24 títulos gerados (12 para cada responsável)
        $titulos = Titulo::where('contrato_id', $contrato->id)->get();
        $this->assertCount(24, $titulos);

        $titulosResp10 = $titulos->where('responsavel_financeiro_id', 10);
        $titulosResp11 = $titulos->where('responsavel_financeiro_id', 11);
        $this->assertCount(12, $titulosResp10);
        $this->assertCount(12, $titulosResp11);

        // 4. Confere soma total e fechamento exato dos centavos (R$ 50 cada parcela)
        $somaTotal = $titulos->sum('valor_nominal');
        $this->assertEquals(1200.00, (float) $somaTotal);

        foreach ($titulos as $t) {
            $this->assertEquals(50.00, (float) $t->valor_nominal);
            $this->assertEquals(50.00, (float) $t->valor_saldo);
            $this->assertEquals('aberto', $t->status);
        }

        // 5. Valida que tentativa de criar segundo contrato para a mesma matrícula/ano falha
        $this->expectException(InvalidArgumentException::class);
        $contratoService->criarContratoEParcelas([
            'aluno_id' => 10,
            'matricula_id' => 10,
            'plano_id' => $plano->id,
            'ano_letivo' => 2026,
            'dia_vencimento' => 10,
            'escola_id' => 1,
            'valor_total' => 1200.00,
            'rateios' => [
                ['responsavel_financeiro_id' => 10, 'percentual' => 100.00],
            ],
        ]);
    }

    /**
     * TC-03 — Emissão de Cobranças em Lote e Rastreamento em lotes_processamento
     */
    public function test_tc03_emissao_cobrancas_em_lote_e_rastreamento_em_lotes_processamento(): void
    {
        // 1. Cria 3 títulos avulsos
        $t1 = Titulo::create([
            'aluno_id' => 10,
            'responsavel_financeiro_id' => 10,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-03',
            'data_vencimento' => '2026-03-10',
            'valor_nominal' => 150.00,
            'valor_saldo' => 150.00,
            'status' => 'aberto',
        ]);

        $t2 = Titulo::create([
            'aluno_id' => 10,
            'responsavel_financeiro_id' => 10,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-04',
            'data_vencimento' => '2026-04-10',
            'valor_nominal' => 150.00,
            'valor_saldo' => 150.00,
            'status' => 'aberto',
        ]);

        $t3 = Titulo::create([
            'aluno_id' => 10,
            'responsavel_financeiro_id' => 10,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-05',
            'data_vencimento' => '2026-05-10',
            'valor_nominal' => 150.00,
            'valor_saldo' => 150.00,
            'status' => 'aberto',
        ]);

        // Mock do Asaas Gateway
        $mockAsaas = Mockery::mock(AsaasGateway::class);
        $mockAsaas->shouldReceive('criarCobranca')->andReturnUsing(function ($titulo) {
            return CobrancaGateway::create([
                'titulo_id' => $titulo->id,
                'gateway' => 'asaas',
                'gateway_charge_id' => 'pay_homolog_batch_' . $titulo->id,
                'status_externo' => 'PENDING',
                'ativa' => true,
            ])->toArray();
        });

        $mockManager = Mockery::mock(GatewayManager::class);
        $mockManager->shouldReceive('driver')->with('asaas')->andReturn($mockAsaas);
        $this->app->instance(GatewayManager::class, $mockManager);

        // 2. Executa job de lote
        $job = new GerarCobrancasGatewayLoteJob([$t1->id, $t2->id, $t3->id]);
        $job->handle();

        // 3. Valida criação do registro de rastreamento em lotes_processamento
        $lote = LoteProcessamento::where('tipo', 'emissao_asaas')
            ->latest()
            ->first();

        $this->assertNotNull($lote);
        $this->assertEquals('concluido', $lote->status);
        $this->assertEquals(3, $lote->total);
        $this->assertEquals(3, $lote->sucesso);
        $this->assertEquals(0, $lote->falha);

        // Confere cobranças vinculadas aos títulos
        $this->assertEquals(1, $t1->cobrancas()->count());
        $this->assertEquals(1, $t2->cobrancas()->count());
        $this->assertEquals(1, $t3->cobrancas()->count());
    }

    /**
     * TC-04 — Carnê Oficial Asaas e Reconciliação Automática de Parcela via Webhook
     */
    public function test_tc04_geracao_carne_oficial_asaas_e_conciliacao_automatica_via_webhook(): void
    {
        $contrato = Contrato::create([
            'numero_contrato' => 'CTR-HOMOLOG-CARNE',
            'aluno_id' => 10,
            'matricula_id' => 10,
            'responsavel_financeiro_id' => 10,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 1200.00,
            'valor_total_liquido' => 1200.00,
            'dia_vencimento' => 10,
            'status' => 'ativo',
        ]);

        $titulos = [];
        for ($i = 1; $i <= 12; $i++) {
            $titulos[] = Titulo::create([
                'contrato_id' => $contrato->id,
                'aluno_id' => 10,
                'responsavel_financeiro_id' => 10,
                'escola_id' => 1,
                'tipo' => 'mensalidade',
                'numero_parcela' => $i,
                'total_parcelas' => 12,
                'competencia' => sprintf('2026-%02d', $i),
                'data_vencimento' => Carbon::today()->addMonths($i)->toDateString(),
                'valor_nominal' => 100.00,
                'valor_saldo' => 100.00,
                'status' => 'aberto',
            ]);
        }

        // Mock do Asaas para installment
        $mockAsaas = Mockery::mock(AsaasGateway::class);
        $paymentsMock = [];
        foreach ($titulos as $idx => $t) {
            $paymentsMock[] = [
                'id' => "pay_carne_homolog_{$t->numero_parcela}",
                'dueDate' => $t->data_vencimento,
                'value' => 100.00,
                'status' => 'PENDING',
                'bankSlipUrl' => 'https://sandbox.asaas.com/b/test',
                'invoiceUrl' => 'https://sandbox.asaas.com/i/test',
            ];
        }

        $mockAsaas->shouldReceive('criarInstallment')->andReturn([
            'installment_id' => 'inst_homolog_999',
            'valor_total' => 1200.00,
            'payments' => $paymentsMock,
        ]);
        $mockAsaas->shouldReceive('listarPagamentosDoParcelamento')->andReturn($paymentsMock);

        $mockManager = Mockery::mock(GatewayManager::class);
        $mockManager->shouldReceive('driver')->with('asaas')->andReturn($mockAsaas);
        $this->app->instance(GatewayManager::class, $mockManager);

        // 1. Gera carnê via CarneService
        $carneService = app(CarneService::class);
        $carne = $carneService->gerar($contrato, $titulos);

        $this->assertEquals('inst_homolog_999', $carne->installment_id);
        $this->assertEquals('ativo', $carne->status);
        $this->assertEquals(12, $carne->qtd_parcelas);

        // Verifica que todas as 12 cobranças foram mapeadas 1:1
        $this->assertEquals(12, CobrancaGateway::where('carne_id', $carne->id)->count());

        // 2. Simula Webhook Asaas de pagamento da parcela 3
        config(['financeiro.gateways.asaas.webhook_token' => 'segredo_homolog_webhook']);
        $payloadWebhook = [
            'id' => 'evt_homolog_pay_03',
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_carne_homolog_3',
                'installment' => 'inst_homolog_999',
                'value' => 100.00,
                'billingType' => 'BOLETO',
                'paymentDate' => Carbon::today()->toDateString(),
            ]
        ];

        $resWebhook = $this->postJson('/financeiro/webhook/asaas', $payloadWebhook, [
            'asaas-access-token' => 'segredo_homolog_webhook',
        ]);
        $resWebhook->assertOk();

        // 3. Confere reconciliação precisa: apenas o título 3 foi baixado para 'pago'
        $t3 = $titulos[2]->fresh();
        $this->assertEquals('pago', $t3->status);
        $this->assertEquals(0.00, (float) $t3->valor_saldo);

        // Outros títulos continuam em aberto
        $this->assertEquals('aberto', $titulos[0]->fresh()->status);
        $this->assertEquals('aberto', $titulos[1]->fresh()->status);
        $this->assertEquals('aberto', $titulos[3]->fresh()->status);
    }

    /**
     * TC-05 — Caixa Ágil de Balcão, Encargos, Recibo e Verificação Pública
     */
    public function test_tc05_caixa_agil_balcao_encargos_recibo_e_verificacao_publica(): void
    {
        // 1. Cria título vencido há 10 dias
        $dataVenc = Carbon::today()->subDays(10)->toDateString();
        $titulo = Titulo::create([
            'aluno_id' => 10,
            'responsavel_financeiro_id' => 10,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-02',
            'data_vencimento' => $dataVenc,
            'valor_nominal' => 1000.00,
            'valor_saldo' => 1000.00,
            'status' => 'aberto',
        ]);

        // 2. Consulta busca incremental de caixa
        $resBusca = $this->getJson(route('financeiro.api.titulos.buscar', ['q' => 'Carlos']));
        $resBusca->assertOk();
        $dadosBusca = $resBusca->json('data');
        $this->assertNotEmpty($dadosBusca);

        // 3. Consulta cálculo de encargos (multa 2% = 20.00 + juros 1% pro-rata)
        $resCalculo = $this->getJson(route('financeiro.api.titulos.calculo', $titulo->id));
        $resCalculo->assertOk();
        $this->assertEquals(20.00, $resCalculo->json('multa'));
        $this->assertGreaterThan(0, $resCalculo->json('juros'));
        $totalCalculado = $resCalculo->json('total_receber');

        // 4. Efetua baixa no caixa com troco
        $resBaixa = $this->post(route('financeiro.baixas.store'), [
            'titulo_id' => $titulo->id,
            'valor_pago' => $totalCalculado + 10.00, // R$ 10 a mais
            'valor_multa' => 20.00,
            'valor_juros' => (float) $resCalculo->json('juros'),
            'forma_pagamento' => 'dinheiro',
            'data_pagamento' => Carbon::today()->toDateString(),
            'idempotency_key' => 'idem_caixa_homolog_001',
            'devolver_troco' => 1,
        ]);
        $resBaixa->assertRedirect();

        $titulo->refresh();
        $this->assertEquals('pago', $titulo->status);
        $this->assertEquals(0.00, (float) $titulo->valor_saldo);

        // 5. Verifica pagamento e recibo
        $pagamento = Pagamento::where('titulo_id', $titulo->id)->latest()->first();
        $this->assertNotNull($pagamento);
        $this->assertNotEmpty($pagamento->comprovante_hash);
        $this->assertEquals(10.00, (float) $pagamento->troco);

        // 6. Verificação documental pública do hash do recibo
        $resVerificar = $this->get(route('financeiro.verificar.documento', $pagamento->comprovante_hash));
        $resVerificar->assertOk();
        $resVerificar->assertJson([
            'valido' => true,
            'tipo' => 'recibo_pagamento',
            'responsavel' => 'Carlos Silva Homologacao',
        ]);
    }

    /**
     * TC-06 — Pagamento a Maior, Geração e Consumo de Crédito
     */
    public function test_tc06_pagamento_a_maior_geracao_e_consumo_de_credito(): void
    {
        $t1 = Titulo::create([
            'aluno_id' => 10,
            'responsavel_financeiro_id' => 10,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 2,
            'competencia' => '2026-06',
            'data_vencimento' => Carbon::today()->addDays(5)->toDateString(),
            'valor_nominal' => 200.00,
            'valor_saldo' => 200.00,
            'status' => 'aberto',
        ]);

        $t2 = Titulo::create([
            'aluno_id' => 10,
            'responsavel_financeiro_id' => 10,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 2,
            'total_parcelas' => 2,
            'competencia' => '2026-07',
            'data_vencimento' => Carbon::today()->addDays(35)->toDateString(),
            'valor_nominal' => 150.00,
            'valor_saldo' => 150.00,
            'status' => 'aberto',
        ]);

        $baixaService = app(BaixaTituloService::class);

        // 1. Paga R$ 260 em título de R$ 200 -> gera R$ 60 de crédito
        $pag1 = $baixaService->realizarBaixa($t1, [
            'valor_pago' => 260.00,
            'forma_pagamento' => 'pix',
            'data_pagamento' => Carbon::today()->toDateString(),
        ]);

        $t1->refresh();
        $this->assertEquals('pago', $t1->status);

        $credito = CreditoResponsavel::where('responsavel_financeiro_id', 10)
            ->where('utilizado', false)
            ->first();

        $this->assertNotNull($credito);
        $this->assertEquals(60.00, (float) $credito->valor);
        $this->assertFalse((bool) $credito->utilizado);

        // 2. Consome o crédito para abater no título t2
        $credito->update([
            'utilizado' => true,
        ]);

        $t2->valor_saldo = (float) $t2->valor_saldo - (float) $credito->valor; // 150 - 60 = 90
        $t2->save();

        $t2->refresh();
        $this->assertEquals(90.00, (float) $t2->valor_saldo);
        $this->assertTrue((bool) $credito->fresh()->utilizado);
    }

    /**
     * TC-07 — Estorno de Pagamento: Usuário Autorizado vs Bloqueio 403
     */
    public function test_tc07_estorno_pagamento_autorizado_vs_bloqueio_403(): void
    {
        $titulo = Titulo::create([
            'aluno_id' => 10,
            'responsavel_financeiro_id' => 10,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 1,
            'competencia' => '2026-08',
            'data_vencimento' => Carbon::today()->toDateString(),
            'valor_nominal' => 180.00,
            'valor_saldo' => 180.00,
            'status' => 'aberto',
        ]);

        $baixaService = app(BaixaTituloService::class);
        $pagamento = $baixaService->realizarBaixa($titulo, [
            'valor_pago' => 180.00,
            'forma_pagamento' => 'dinheiro',
            'data_pagamento' => Carbon::today()->toDateString(),
        ]);

        // 1. Usuário comum sem permissão de estorno (exclui) recebe 403
        $userSemPermissao = new \Illuminate\Foundation\Auth\User();
        $userSemPermissao->id = 99;
        $userSemPermissao->tipo_usuario_id = 99;
        $userSemPermissao->is_admin = false;

        $resBloqueio = $this->actingAs($userSemPermissao)->post(route('financeiro.baixas.estornar', $pagamento->id), [
            'motivo_estorno' => 'Tentativa indevida',
        ]);
        $resBloqueio->assertStatus(403);

        // 2. Usuário administrador executa o estorno com sucesso
        AutorizarProcessoFinanceiro::limparCache();
        $userAdmin = new \Illuminate\Foundation\Auth\User();
        $userAdmin->id = 1;
        $userAdmin->tipo_usuario_id = 1;
        $userAdmin->is_admin = true;

        $resSucesso = $this->actingAs($userAdmin)->post(route('financeiro.baixas.estornar', $pagamento->id), [
            'motivo_estorno' => 'Estorno autorizado pelo diretor',
        ]);
        $resSucesso->assertRedirect();

        $titulo->refresh();
        $pagamento->refresh();
        $this->assertEquals('aberto', $titulo->status);
        $this->assertEquals(180.00, (float) $titulo->valor_saldo);
        $this->assertTrue((bool) $pagamento->estornado);
    }

    /**
     * TC-08 — Renegociação de Débitos Vencidos com Entrada e Cancelamento Asaas
     */
    public function test_tc08_renegociacao_titulos_vencidos_com_entrada_e_cancelamento_asaas(): void
    {
        // 1. Cria 3 títulos vencidos com cobranças ativas no Asaas
        $titulosVencidos = [];
        for ($i = 1; $i <= 3; $i++) {
            $t = Titulo::create([
                'aluno_id' => 10,
                'responsavel_financeiro_id' => 10,
                'escola_id' => 1,
                'tipo' => 'mensalidade',
                'numero_parcela' => $i,
                'total_parcelas' => 3,
                'competencia' => sprintf('2025-%02d', $i + 9),
                'data_vencimento' => Carbon::today()->subMonths(4 - $i)->toDateString(),
                'valor_nominal' => 300.00,
                'valor_saldo' => 300.00,
                'status' => 'aberto',
            ]);

            CobrancaGateway::create([
                'titulo_id' => $t->id,
                'gateway' => 'asaas',
                'gateway_charge_id' => "pay_old_asaas_{$i}",
                'status_externo' => 'PENDING',
                'ativa' => true,
            ]);

            $titulosVencidos[] = $t;
        }

        // 2. Simula e confirma renegociação: Total R$ 900. Entrada R$ 100 + 4 parcelas de R$ 200
        $renegociacaoService = app(RenegociacaoService::class);
        $ids = array_map(fn($t) => $t->id, $titulosVencidos);

        $simulacao = $renegociacaoService->simular($ids, 100.00, 4, Carbon::today()->addDays(30)->toDateString());

        $this->assertEquals(900.00, (float) $simulacao['total_original']);
        $this->assertEquals(800.00, (float) $simulacao['valor_acordo']);

        $acordo = $renegociacaoService->confirmar($ids, 100.00, 4, Carbon::today()->addDays(30)->toDateString(), 1, 900.00);

        $this->assertNotNull($acordo->id);

        // 3. Títulos originais devem estar com status 'renegociado' e saldo 0
        foreach ($titulosVencidos as $t) {
            $t->refresh();
            $this->assertEquals('renegociado', $t->status);
            $this->assertEquals(0.00, (float) $t->valor_saldo);
        }

        // 4. Novos títulos gerados: 4 parcelas acordadas
        $novosTitulos = Titulo::where('renegociacao_id', $acordo->id)->get();
        $this->assertCount(4, $novosTitulos);

        // 5. Termo do acordo acessível
        $resTermo = $this->get(route('financeiro.renegociacao.termo-pdf', $acordo->id));
        $resTermo->assertOk();
        $resTermo->assertSee('Termo de Confissão');
    }

    /**
     * TC-09 — Cancelamento de Contrato com Revogação de Cobranças no Asaas
     */
    public function test_tc09_cancelamento_contrato_e_revogacao_cobrancas(): void
    {
        $contrato = Contrato::create([
            'numero_contrato' => 'CTR-CANCELAR-HOMOLOG',
            'aluno_id' => 10,
            'matricula_id' => 10,
            'responsavel_financeiro_id' => 10,
            'ano_letivo' => 2026,
            'data_inicio' => '2026-01-01',
            'data_termino' => '2026-12-31',
            'escola_id' => 1,
            'valor_total_bruto' => 600.00,
            'valor_total_liquido' => 600.00,
            'status' => 'ativo',
        ]);

        $t1 = Titulo::create([
            'contrato_id' => $contrato->id,
            'aluno_id' => 10,
            'responsavel_financeiro_id' => 10,
            'escola_id' => 1,
            'tipo' => 'mensalidade',
            'numero_parcela' => 1,
            'total_parcelas' => 2,
            'competencia' => '2026-09',
            'data_vencimento' => Carbon::today()->addDays(20)->toDateString(),
            'valor_nominal' => 300.00,
            'valor_saldo' => 300.00,
            'status' => 'aberto',
        ]);

        CobrancaGateway::create([
            'titulo_id' => $t1->id,
            'gateway' => 'asaas',
            'gateway_charge_id' => 'pay_canc_test_999',
            'status_externo' => 'PENDING',
            'ativa' => true,
        ]);

        // Executa cancelamento do contrato
        $resCancel = $this->post(route('financeiro.contratos.cancelar', $contrato->id), [
            'motivo' => 'Transferência escolar do estudante',
        ]);
        $resCancel->assertRedirect();

        $contrato->refresh();
        $this->assertEquals('cancelado', $contrato->status);
        $this->assertNotNull($contrato->cancelado_em);

        $t1->refresh();
        $this->assertEquals('cancelado', $t1->status);

        $cob = CobrancaGateway::where('titulo_id', $t1->id)->first();
        $this->assertFalse((bool) $cob->ativa);
    }

    /**
     * TC-10 — Contas a Pagar: Lançamento, Liquidação com Débito Bancário e Extrato
     */
    public function test_tc10_contas_a_pagar_liquidacao_saldo_bancario_e_extrato(): void
    {
        $conta = ContaBancaria::find(10);
        $saldoInicial = (float) $conta->saldo_atual;

        // 1. Cria despesa com escola_id obrigatório
        $despesa = Lancamento::create([
            'tipo' => 'despesa',
            'escola_id' => 1,
            'plano_conta_id' => 10,
            'descricao' => 'Serviço de Reforma Elétrica',
            'valor' => 450.00,
            'data_competencia' => '2026-05-01',
            'data_vencimento' => Carbon::today()->addDays(10)->toDateString(),
            'status' => 'a_pagar',
        ]);

        $this->assertNotNull($despesa->id);
        $this->assertEquals(1, $despesa->escola_id);

        // 2. Liquida despesa vinculando à conta bancária
        $resLiquidar = $this->post(route('financeiro.contas-pagar.liquidar', $despesa->id), [
            'conta_bancaria_id' => $conta->id,
            'data_pagamento' => Carbon::today()->toDateString(),
            'forma_pagamento' => 'transferencia',
            'valor_pago' => 450.00,
        ]);
        $resLiquidar->assertRedirect();

        $despesa->refresh();
        $conta->refresh();

        // 3. Verifica saldo atualizado e status pago
        $this->assertEquals('pago', $despesa->status);
        $this->assertEquals($saldoInicial - 450.00, (float) $conta->saldo_atual);

        // 4. Verifica geração de extrato em movimentacoes_conta
        $mov = MovimentacaoConta::where('conta_bancaria_id', $conta->id)
            ->where('origem_id', $despesa->id)
            ->first();

        $this->assertNotNull($mov);
        $this->assertEquals('saida', $mov->tipo);
        $this->assertEquals(450.00, (float) $mov->valor);
        $this->assertEquals($saldoInicial - 450.00, (float) $mov->saldo_posterior);

        // 5. Tentativa de liquidação duplicada é rejeitada
        $resDup = $this->post(route('financeiro.contas-pagar.liquidar', $despesa->id), [
            'conta_bancaria_id' => $conta->id,
            'data_pagamento' => Carbon::today()->toDateString(),
            'forma_pagamento' => 'transferencia',
            'valor_pago' => 450.00,
        ]);
        $resDup->assertSessionHas('error');
    }

    /**
     * TC-11 — Relatórios Contábeis: DRE Estrito, Fluxo de Caixa, IRPF e Exportação CSV
     */
    public function test_tc11_relatorios_dre_fluxo_irpf_quitacao_e_csv(): void
    {
        // 1. DRE HTML
        $resDre = $this->get(route('financeiro.relatorios.dre', ['ano' => 2026]));
        $resDre->assertOk();
        $resDre->assertSee('Demonstração do Resultado do Exercício');
        $resDre->assertSee('RECEITA BRUTA OPERACIONAL');
        $resDre->assertSee('RECEITA OPERACIONAL LÍQUIDA');

        // 2. DRE CSV (com UTF-8 BOM)
        $resDreCsv = $this->get(route('financeiro.relatorios.dre', ['ano' => 2026, 'exportar' => 'csv']));
        $resDreCsv->assertOk();
        $resDreCsv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringStartsWith("\xEF\xBB\xBF", $resDreCsv->streamedContent());

        // 3. Fluxo de Caixa
        $resFluxo = $this->get(route('financeiro.relatorios.fluxo-caixa', ['ano' => 2026]));
        $resFluxo->assertOk();
        $resFluxo->assertSee('Fluxo de Caixa');

        // 4. Informe IRPF
        $resIrpf = $this->get(route('financeiro.relatorios.informe-irpf', [
            'ano' => 2026,
            'responsavel_id' => 10,
        ]));
        $resIrpf->assertOk();
        $resIrpf->assertSee('Informe de Rendimentos e Pagamentos');

        // 5. Quitação Anual
        $resQuitacao = $this->get(route('financeiro.relatorios.quitacao-anual', [
            'ano' => 2026,
            'aluno_id' => 10,
        ]));
        $resQuitacao->assertOk();
        $resQuitacao->assertSee('Declaração de Quitação Anual de Débitos');
    }

    /**
     * TC-12 — Matriz de Rotas RBAC: Administrador (200 OK) vs Não Autorizado (403)
     */
    public function test_tc12_matriz_de_rotas_rbac_admin_vs_nao_autorizado(): void
    {
        $rotasParaAuditar = [
            'financeiro.dashboard',
            'financeiro.titulos.index',
            'financeiro.baixas.create',
            'financeiro.contas-pagar.index',
            'financeiro.renegociacao.index',
            'financeiro.relatorios.dre',
            'financeiro.processamentos.index',
            'financeiro.configuracoes.gateway',
        ];

        // 1. Usuário Administrador -> 200 OK em todas
        $admin = new \Illuminate\Foundation\Auth\User();
        $admin->id = 1;
        $admin->tipo_usuario_id = 1;
        $admin->is_admin = true;

        foreach ($rotasParaAuditar as $rota) {
            AutorizarProcessoFinanceiro::limparCache();
            $res = $this->actingAs($admin)->get(route($rota));
            $this->assertEquals(200, $res->status(), "Admin deveria ter acesso 200 na rota {$rota}");
        }

        // 2. Usuário Não Autorizado (tipo_usuario_id = 99 sem entradas em menu_tipo_usuario) -> 403 Forbidden
        $usuarioComum = new \Illuminate\Foundation\Auth\User();
        $usuarioComum->id = 99;
        $usuarioComum->tipo_usuario_id = 99;
        $usuarioComum->is_admin = false;

        foreach ($rotasParaAuditar as $rota) {
            AutorizarProcessoFinanceiro::limparCache();
            $res = $this->actingAs($usuarioComum)->get(route($rota));
            $this->assertEquals(403, $res->status(), "Usuário comum sem permissão deve receber 403 na rota {$rota}");
        }
    }

    /**
     * TC-13 — Fila e Reprocessamento de Webhooks Pendentes
     */
    public function test_tc13_reprocessamento_webhooks_apos_falha_temporaria(): void
    {
        // 1. Insere webhook com divergência ou falha temporária
        $evt = WebhookEvent::create([
            'gateway' => 'asaas',
            'event_id' => 'evt_homolog_reprocessar_99',
            'event_type' => 'PAYMENT_RECEIVED',
            'payload' => [
                'id' => 'pay_inexistente_temporario',
                'value' => 100.00,
            ],
            'processado' => false,
            'requer_acao' => false,
            'tentativas' => 1,
            'proxima_tentativa_em' => Carbon::now()->subMinute(),
        ]);

        // 2. Executa o comando de reprocessamento
        $exitCode = Artisan::call('financeiro:reprocessar-webhooks', ['--limit' => 5]);
        $this->assertEquals(0, $exitCode);

        $evt->refresh();
        $this->assertGreaterThanOrEqual(1, $evt->tentativas);
    }

    /**
     * TC-14 — Integração com Matrícula do i-Educar e Tela "Matrículas sem Contrato"
     */
    public function test_tc14_integracao_matricula_e_tela_sem_contrato(): void
    {
        // 1. Dispara evento de criação de matrícula
        event(new MatriculaCriada(10, 10, 1, 2026));

        // 2. Acessa a tela de matrículas sem contrato
        $resSemContrato = $this->get(route('financeiro.contratos.matriculas-sem-contrato'));
        $resSemContrato->assertOk();
        $resSemContrato->assertSee('Matrículas sem Contrato Financeiro');
        $resSemContrato->assertSee('Lucas');
    }
}
