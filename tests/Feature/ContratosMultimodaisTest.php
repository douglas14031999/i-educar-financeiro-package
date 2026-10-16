<?php

namespace iEducar\Financeiro\Tests\Feature;

use Carbon\Carbon;
use iEducar\Financeiro\Models\AlunoResponsavel;
use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\PlanoItem;
use iEducar\Financeiro\Models\PlanoPagamento;
use iEducar\Financeiro\Models\ResponsavelFinanceiro;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\BaixaTituloService;
use iEducar\Financeiro\Services\ContratoService;
use iEducar\Financeiro\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class ContratosMultimodaisTest extends TestCase
{
    use DatabaseTransactions;

    protected ContratoService $contratoService;
    protected BaixaTituloService $baixaService;
    protected int $alunoId = 1;
    protected int $escolaId = 1;
    protected int $anoLetivo = 2026;
    protected PlanoPagamento $plano1x;
    protected PlanoPagamento $plano2x;
    protected ResponsavelFinanceiro $responsavel;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Database\Eloquent\Model::unguard();

        $this->contratoService = app(ContratoService::class);
        $this->baixaService = app(BaixaTituloService::class);

        // Responsável por CPF único
        $this->responsavel = ResponsavelFinanceiro::firstOrCreate(['cpf_cnpj' => '11122233344'], [
            'nome' => 'Responsavel Multimodal Teste',
            'telefone_celular' => '11987654321',
            'email' => 'resp.multimodal@teste.com',
            'ativo' => true,
        ]);

        AlunoResponsavel::firstOrCreate([
            'aluno_id' => $this->alunoId,
            'responsavel_id' => $this->responsavel->id,
        ], [
            'parentesco' => 'Mãe',
            'percentual_rateio' => 100.00,
            'responsavel_principal' => true,
        ]);

        // Plano 1x (À vista)
        $this->plano1x = PlanoPagamento::firstOrCreate([
            'nome' => 'Plano Anuidade À Vista',
            'ano_letivo' => $this->anoLetivo,
            'escola_id' => $this->escolaId,
        ], [
            'valor_total' => 1200.00,
            'ativo' => true,
        ]);

        if ($this->plano1x->itens()->count() === 0) {
            PlanoItem::create([
                'plano_id' => $this->plano1x->id,
                'tipo_item' => 'mensalidade',
                'numero_parcela' => 1,
                'mes_competencia' => 1,
                'valor' => 1200.00,
                'descricao' => 'Parcela Única Anuidade',
            ]);
        }

        // Plano 2x (Parcelado)
        $this->plano2x = PlanoPagamento::firstOrCreate([
            'nome' => 'Plano 2 Parcelas',
            'ano_letivo' => $this->anoLetivo,
            'escola_id' => $this->escolaId,
        ], [
            'valor_total' => 1000.00,
            'ativo' => true,
        ]);

        if ($this->plano2x->itens()->count() === 0) {
            PlanoItem::create([
                'plano_id' => $this->plano2x->id,
                'tipo_item' => 'mensalidade',
                'numero_parcela' => 1,
                'mes_competencia' => 1,
                'valor' => 500.00,
                'descricao' => 'Parcela 1/2',
            ]);
            PlanoItem::create([
                'plano_id' => $this->plano2x->id,
                'tipo_item' => 'mensalidade',
                'numero_parcela' => 2,
                'mes_competencia' => 2,
                'valor' => 500.00,
                'descricao' => 'Parcela 2/2',
            ]);
        }
    }

    protected function registrarMatricula(int $matriculaId): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE TABLE IF NOT EXISTS pmieducar_matricula (
                cod_matricula INTEGER PRIMARY KEY,
                ref_ref_cod_escola INTEGER,
                ref_cod_aluno INTEGER,
                ano INTEGER,
                ativo INTEGER DEFAULT 1
            )");
            DB::table('pmieducar_matricula')->updateOrInsert(
                ['cod_matricula' => $matriculaId],
                ['ref_ref_cod_escola' => $this->escolaId, 'ref_cod_aluno' => $this->alunoId, 'ano' => $this->anoLetivo]
            );
        } else {
            DB::table('pmieducar.matricula')->updateOrInsert(
                ['cod_matricula' => $matriculaId],
                ['ref_ref_cod_escola' => $this->escolaId, 'ref_cod_aluno' => $this->alunoId, 'ano' => $this->anoLetivo, 'ativo' => 1]
            );
        }
    }

    /**
     * Modalidade 1: Dinheiro à vista deve liquidar o título e quitar o contrato imediatamente
     */
    public function test_contrato_dinheiro_nasce_quitado_com_titulo_pago(): void
    {
        $matId = 2001;
        $this->registrarMatricula($matId);

        $dados = [
            'aluno_id' => $this->alunoId,
            'matricula_id' => $matId,
            'escola_id' => $this->escolaId,
            'ano_letivo' => $this->anoLetivo,
            'plano_id' => $this->plano1x->id,
            'dia_vencimento' => 10,
            'forma_pagamento' => 'dinheiro',
        ];

        $contrato = $this->contratoService->criarContratoEParcelas($dados);

        $this->assertEquals('quitado', $contrato->status);
        $this->assertEquals('dinheiro', $contrato->forma_pagamento);
        $this->assertNotNull($contrato->quitado_em);

        $titulo = $contrato->titulos()->first();
        $this->assertNotNull($titulo);
        $this->assertEquals('pago', $titulo->status);
        $this->assertEquals(0.00, (float) $titulo->valor_saldo);
        $this->assertEquals(1200.00, (float) $titulo->valor_pago);
    }

    /**
     * Modalidade 2: Cartão Presencial (Maquininha POS) deve registrar bandeira e NSU e nascer quitado
     */
    public function test_contrato_cartao_presencial_pos_nasce_quitado_com_nsu_e_bandeira(): void
    {
        $matId = 2002;
        $this->registrarMatricula($matId);

        $dados = [
            'aluno_id' => $this->alunoId,
            'matricula_id' => $matId,
            'escola_id' => $this->escolaId,
            'ano_letivo' => $this->anoLetivo,
            'plano_id' => $this->plano1x->id,
            'dia_vencimento' => 10,
            'forma_pagamento' => 'cartao_presencial',
            'cartao_bandeira' => 'Mastercard',
            'cartao_nsu' => 'NSU-789012',
            'cartao_tipo' => 'credito_vista',
        ];

        $contrato = $this->contratoService->criarContratoEParcelas($dados);

        $this->assertEquals('quitado', $contrato->status);
        $this->assertEquals('cartao_presencial', $contrato->forma_pagamento);
        $this->assertEquals('Mastercard', $contrato->cartao_bandeira);
        $this->assertEquals('NSU-789012', $contrato->cartao_nsu);
        $this->assertNotNull($contrato->quitado_em);

        $titulo = $contrato->titulos()->first();
        $this->assertEquals('pago', $titulo->status);
        $this->assertEquals(0.00, (float) $titulo->valor_saldo);
    }

    /**
     * Modalidade 3: Cartão de Crédito Online (Link Asaas) nasce como aguardando_pagamento
     */
    public function test_contrato_cartao_credito_link_nasce_aguardando_pagamento(): void
    {
        $matId = 2003;
        $this->registrarMatricula($matId);

        $dados = [
            'aluno_id' => $this->alunoId,
            'matricula_id' => $matId,
            'escola_id' => $this->escolaId,
            'ano_letivo' => $this->anoLetivo,
            'plano_id' => $this->plano1x->id,
            'dia_vencimento' => 10,
            'forma_pagamento' => 'cartao_credito',
            'cartao_parcelas' => 12,
        ];

        $contrato = $this->contratoService->criarContratoEParcelas($dados);

        $this->assertEquals('aguardando_pagamento', $contrato->status);
        $this->assertEquals('cartao_credito', $contrato->forma_pagamento);
        $this->assertNull($contrato->quitado_em);

        $titulo = $contrato->titulos()->first();
        $this->assertEquals('aberto', $titulo->status);
        $this->assertEquals(1200.00, (float) $titulo->valor_saldo);
    }

    /**
     * Modalidade 4: PIX Instantâneo nasce como aguardando_pagamento
     */
    public function test_contrato_pix_nasce_aguardando_pagamento(): void
    {
        $matId = 2004;
        $this->registrarMatricula($matId);

        $dados = [
            'aluno_id' => $this->alunoId,
            'matricula_id' => $matId,
            'escola_id' => $this->escolaId,
            'ano_letivo' => $this->anoLetivo,
            'plano_id' => $this->plano1x->id,
            'dia_vencimento' => 10,
            'forma_pagamento' => 'pix',
        ];

        $contrato = $this->contratoService->criarContratoEParcelas($dados);

        $this->assertEquals('aguardando_pagamento', $contrato->status);
        $this->assertEquals('pix', $contrato->forma_pagamento);
    }

    /**
     * Modalidade 6: Carnê Escolar nasce como ativo e transiciona para quitado ao liquidar todas as parcelas
     */
    public function test_contrato_carne_fica_ativo_e_transiciona_para_quitado_apos_todas_parcelas_pagas(): void
    {
        $matId = 2005;
        $this->registrarMatricula($matId);

        $dados = [
            'aluno_id' => $this->alunoId,
            'matricula_id' => $matId,
            'escola_id' => $this->escolaId,
            'ano_letivo' => $this->anoLetivo,
            'plano_id' => $this->plano2x->id,
            'dia_vencimento' => 10,
            'forma_pagamento' => 'carne',
        ];

        $contrato = $this->contratoService->criarContratoEParcelas($dados);

        // 1. Carnê nasce ATIVO
        $this->assertEquals('ativo', $contrato->status);
        $this->assertEquals('carne', $contrato->forma_pagamento);
        $this->assertNull($contrato->quitado_em);

        $titulos = $contrato->titulos()->orderBy('numero_parcela')->get();
        $this->assertCount(2, $titulos);

        // 2. Liquida apenas a primeira parcela -> Contrato deve PERMANECER ATIVO
        $this->baixaService->realizarBaixa($titulos[0], [
            'valor_pago' => 500.00,
            'forma_pagamento' => 'boleto',
            'data_pagamento' => Carbon::today(),
        ]);

        $contrato->refresh();
        $this->assertEquals('ativo', $contrato->status);
        $this->assertNull($contrato->quitado_em);

        // 3. Liquida a segunda (e última) parcela -> Contrato deve TRANSICIONAR PARA QUITADO
        $this->baixaService->realizarBaixa($titulos[1], [
            'valor_pago' => 500.00,
            'forma_pagamento' => 'boleto',
            'data_pagamento' => Carbon::today(),
        ]);

        $contrato->refresh();
        $this->assertEquals('quitado', $contrato->status);
        $this->assertNotNull($contrato->quitado_em);
    }

    /**
     * Teste da view de checkout
     */
    public function test_tela_checkout_contrato_renderiza_com_sucesso(): void
    {
        $matId = 2006;
        $this->registrarMatricula($matId);

        $dados = [
            'aluno_id' => $this->alunoId,
            'matricula_id' => $matId,
            'escola_id' => $this->escolaId,
            'ano_letivo' => $this->anoLetivo,
            'plano_id' => $this->plano1x->id,
            'dia_vencimento' => 10,
            'forma_pagamento' => 'dinheiro',
        ];

        $contrato = $this->contratoService->criarContratoEParcelas($dados);

        $userAdmin = new \Illuminate\Foundation\Auth\User();
        $userAdmin->id = 1;
        $userAdmin->tipo_usuario_id = 1;
        $userAdmin->is_admin = true;

        $response = $this->actingAs($userAdmin)->get(route('financeiro.contratos.checkout', $contrato->id));


        $response->assertStatus(200);
        $response->assertSee('Checkout e Fechamento Financeiro da Matrícula');
        $response->assertSee($contrato->numero_contrato);
    }
}
