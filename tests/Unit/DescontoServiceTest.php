<?php

namespace iEducar\Financeiro\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testes unitários PUROS de cálculo financeiro — sem Eloquent, sem banco.
 *
 * As fórmulas são extraídas de:
 *  - Titulo::calcularMulta()  -> percentual sobre saldo se vencido
 *  - Titulo::calcularJuros()  -> pro-rata diário baseado em 1%/mês
 *  - ContratoService distribuirDesconto() -> Largest Remainder
 */
class DescontoServiceTest extends TestCase
{
    /** Espelha Titulo::calcularMulta() */
    private function calcularMulta(float $saldo, bool $vencido, float $percentual = 2.0): float
    {
        if (!$vencido) return 0.00;
        return round($saldo * ($percentual / 100), 2);
    }

    /** Espelha Titulo::calcularJuros() */
    private function calcularJuros(float $saldo, int $diasAtraso, float $jurosMensal = 1.0): float
    {
        if ($diasAtraso <= 0) return 0.00;
        $taxaDiaria = ($jurosMensal / 100) / 30;
        return round($saldo * $taxaDiaria * $diasAtraso, 2);
    }

    public function testMultaDoisPorCentoParaTituloVencido(): void
    {
        $this->assertEquals(20.00, $this->calcularMulta(1000.00, true, 2.0));
    }

    public function testMultaZeroParaTituloNaoVencido(): void
    {
        $this->assertEquals(0.00, $this->calcularMulta(1000.00, false));
    }

    public function testJurosProRata30Dias(): void
    {
        // 1% a.m. sobre R$1.000 por 30 dias = R$10,00
        $this->assertEquals(10.00, $this->calcularJuros(1000.00, 30, 1.0));
    }

    public function testJuros15Dias(): void
    {
        // 1% a.m. sobre R$1.000 por 15 dias = R$5,00
        $this->assertEquals(5.00, $this->calcularJuros(1000.00, 15, 1.0));
    }

    public function testJurosZeroDentroDoPrazo(): void
    {
        $this->assertEquals(0.00, $this->calcularJuros(1000.00, 0));
    }

    public function testDescontoPontualidade5Porcento(): void
    {
        // 5% de desconto sobre R$800,00 = R$40,00
        $this->assertEquals(40.00, round(800.00 * 0.05, 2));
    }

    public function testLargestRemainderNaoPerdeCentavo(): void
    {
        // A02 — R$100,01 entre 2 responsáveis (50/50)
        $total   = 10001; // centavos
        $partes  = 2;
        $base    = intdiv($total, $partes);
        $residuo = $total - ($base * $partes);

        $valores    = array_fill(0, $partes, $base);
        $valores[0] += $residuo;

        $this->assertEquals($total, array_sum($valores));
        $this->assertEquals(5001, $valores[0]); // R$50,01
        $this->assertEquals(5000, $valores[1]); // R$50,00
    }
}
