<?php

namespace iEducar\Financeiro\Tests\Integration;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\Renegociacao;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\BaixaTituloService;
use iEducar\Financeiro\Services\RenegociacaoService;
use iEducar\Financeiro\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase;

    private RenegociacaoService $renegociacaoService;
    private BaixaTituloService  $baixaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renegociacaoService = app(RenegociacaoService::class);
        $this->baixaService        = app(BaixaTituloService::class);
    }

    /** @test */
    public function confirmar_renegociacao_falha_se_titulo_foi_pago_entre_simulacao_e_confirmacao(): void
    {
        // Arrange — título em aberto elegível para renegociação
        $titulo = Titulo::factory()->create([
            'status'       => 'aberto',
            'valor_saldo'  => 500.00,
            'escola_id'    => 1,
            'responsavel_financeiro_id' => 1,
        ]);

        // Simula renegociação (snapshot dos títulos e valores)
        $proposta = $this->renegociacaoService->simular(
            tituloIds:       [$titulo->id],
            novasCondicoes:  ['parcelas' => 3, 'dia_vencimento' => 10],
            responsavelId:   1,
            escolaId:        1,
        );

        // Entre simulação e confirmação: título é pago por outro operador
        $this->baixaService->realizarBaixa($titulo, [
            'valor_pago'     => 500.00,
            'forma_pagamento' => 'dinheiro',
            'data_pagamento' => Carbon::today()->toDateString(),
            'usuario_id'     => 1,
        ]);

        // Assert — confirmação deve falhar pois o título foi alterado
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/pago|liquidado|estado alterado/i');

        $this->renegociacaoService->confirmar($proposta['proposta_id'] ?? $proposta->id ?? null, 1, 1);
    }

    /** @test */
    public function renegociacao_de_responsaveis_distintos_e_bloqueada(): void
    {
        // A13 — títulos de responsáveis diferentes não podem ser renegociados juntos
        $titulo1 = Titulo::factory()->create(['responsavel_financeiro_id' => 1, 'status' => 'aberto', 'escola_id' => 1]);
        $titulo2 = Titulo::factory()->create(['responsavel_financeiro_id' => 2, 'status' => 'aberto', 'escola_id' => 1]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/responsável|responsavel/i');

        $this->renegociacaoService->simular(
            tituloIds:      [$titulo1->id, $titulo2->id],
            novasCondicoes: ['parcelas' => 3, 'dia_vencimento' => 10],
            responsavelId:  1,
            escolaId:       1,
        );
    }
}
