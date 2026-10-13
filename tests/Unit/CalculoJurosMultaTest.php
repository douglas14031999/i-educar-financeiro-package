<?php

namespace iEducar\Financeiro\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testes unitários PUROS para cálculos de juros e multa.
 * Não usa Eloquent — testa apenas as fórmulas financeiras.
 */
class CalculoJurosMultaTest extends TestCase
{
    private function calcularJuros(float $saldo, int $dias, float $taxaMensal = 1.0): float
    {
        if ($dias <= 0) return 0.00;
        return round($saldo * ($taxaMensal / 100 / 30) * $dias, 2);
    }

    private function calcularMulta(float $saldo, bool $vencido, float $pct = 2.0): float
    {
        return $vencido ? round($saldo * ($pct / 100), 2) : 0.00;
    }

    public function testJurosMensalAplicado(): void
    {
        $this->assertEquals(10.00, $this->calcularJuros(1000.00, 30, 1.0));
    }

    public function testMultaFixaAplicada(): void
    {
        $this->assertEquals(10.00, $this->calcularMulta(500.00, true, 2.0));
    }

    public function testSemJurosDentroDoPrazo(): void
    {
        $this->assertEquals(0.00, $this->calcularJuros(800.00, 0, 1.0));
    }

    public function testSemMultaDentroDoPrazo(): void
    {
        $this->assertEquals(0.00, $this->calcularMulta(1000.00, false));
    }

    public function testJurosProporcional15Dias(): void
    {
        // 1200 * (0.01/30) * 15 = 6.00
        $this->assertEquals(6.00, $this->calcularJuros(1200.00, 15, 1.0));
    }
}
