<?php

namespace iEducar\Financeiro\Tests\Unit;

use PHPUnit\Framework\TestCase;
use iEducar\Financeiro\Models\Titulo;

class CalculoJurosMultaTest extends TestCase
{
    /**
     * Testa cálculo de multa de 2% para título vencido
     */
    public function testCalculoMultaDoisPorCento()
    {
        $titulo = new Titulo([
            'valor_nominal' => 1000.00,
            'valor_saldo' => 1000.00,
            'data_vencimento' => date('Y-m-d', strtotime('-10 days')),
            'status' => 'vencido',
        ]);

        $multa = $titulo->calcularMulta();

        // 2% de 1000 = 20.00
        $this->assertEquals(20.00, $multa);
    }

    /**
     * Testa que título dentro do vencimento não tem juros nem multa
     */
    public function testTituloNaoVencidoNaoPossuiEncargos()
    {
        $titulo = new Titulo([
            'valor_nominal' => 1000.00,
            'valor_saldo' => 1000.00,
            'data_vencimento' => date('Y-m-d', strtotime('+5 days')),
            'status' => 'aberto',
        ]);

        $this->assertEquals(0.00, $titulo->calcularMulta());
        $this->assertEquals(0.00, $titulo->calcularJuros());
    }

    /**
     * Testa cálculo de juros pro-rata de 1% ao mês (30 dias)
     */
    public function testCalculoJurosProRata()
    {
        $titulo = new Titulo([
            'valor_nominal' => 1000.00,
            'valor_saldo' => 1000.00,
            'data_vencimento' => date('Y-m-d', strtotime('-30 days')),
            'status' => 'vencido',
        ]);

        $juros = $titulo->calcularJuros();

        // 1% de 1000 por 30 dias = 10.00
        $this->assertGreaterThanOrEqual(9.90, $juros);
        $this->assertLessThanOrEqual(10.10, $juros);
    }
}
