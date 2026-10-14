<?php

namespace iEducar\Financeiro\Tests\Integration;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\Renegociacao;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\BaixaTituloService;
use iEducar\Financeiro\Services\RenegociacaoService;
use iEducar\Financeiro\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

/**
 * P-07 / T18 — Pagamento durante Renegociação
 *
 * Garante que se um pagamento chegar entre a simulação e a confirmação
 * da renegociação, a proposta de renegociação seja invalidada.
 *
 * Regra de negócio: uma renegociação simulada deve ser rejeitada na confirmação
 * se qualquer título incluído foi alterado (pago, baixado parcialmente, etc.)
 * desde o momento da simulação.
 */
class PagamentoDuranteRenegociacaoTest extends TestCase
{
    use DatabaseTransactions;

    private RenegociacaoService $renegociacaoService;
    private BaixaTituloService  $baixaService;

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

        \iEducar\Financeiro\Models\ResponsavelFinanceiro::firstOrCreate(['id' => 2], [
            'nome' => 'Responsavel Teste 2',
            'cpf_cnpj' => '98765432100',
            'email' => 'teste2@teste.com',
            'telefone_celular' => '11988888888',
            'cep' => '01001000',
            'logradouro' => 'Rua Teste 2',
            'numero' => '200',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'ativo' => true,
        ]);

        $this->renegociacaoService = app(RenegociacaoService::class);
        $this->baixaService        = app(BaixaTituloService::class);
    }

    /** @test */
    public function confirmar_renegociacao_falha_se_titulo_foi_pago_entre_simulacao_e_confirmacao(): void
    {
        // Arrange — título em aberto elegível para renegociação
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
            'valor_saldo'               => 500.00,
            'status'                    => 'aberto',
        ]);

        // Simula renegociação (snapshot dos títulos e valores)
        $simulacao = $this->renegociacaoService->simular([$titulo->id], 0.0, 3, Carbon::today()->addDays(10)->toDateString());

        // Entre simulação e confirmação: título é pago por outro operador
        $this->baixaService->realizarBaixa($titulo, [
            'valor_pago'      => 500.00,
            'forma_pagamento' => 'dinheiro',
            'data_pagamento'  => Carbon::today()->toDateString(),
            'usuario_id'      => 1,
        ]);

        // Assert — confirmação deve falhar pois o título foi alterado
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/elegível|alterado/i');

        $this->renegociacaoService->confirmar([$titulo->id], 0.0, 3, null, 1, (float) $simulacao['total_original']);
    }

    /** @test */
    public function renegociacao_de_responsaveis_distintos_e_bloqueada(): void
    {
        // A13 — títulos de responsáveis diferentes não podem ser renegociados juntos
        $titulo1 = Titulo::create(['aluno_id' => 1, 'responsavel_financeiro_id' => 1, 'status' => 'aberto', 'escola_id' => 1, 'tipo' => 'mensalidade', 'numero_parcela' => 1, 'total_parcelas' => 1, 'competencia' => '2026-01', 'data_vencimento' => '2026-01-10', 'valor_nominal' => 100, 'valor_saldo' => 100]);
        $titulo2 = Titulo::create(['aluno_id' => 1, 'responsavel_financeiro_id' => 2, 'status' => 'aberto', 'escola_id' => 1, 'tipo' => 'mensalidade', 'numero_parcela' => 2, 'total_parcelas' => 1, 'competencia' => '2026-02', 'data_vencimento' => '2026-02-10', 'valor_nominal' => 100, 'valor_saldo' => 100]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/responsável|responsavel/i');

        $this->renegociacaoService->simular([$titulo1->id, $titulo2->id], 0.0, 3);
    }
}
