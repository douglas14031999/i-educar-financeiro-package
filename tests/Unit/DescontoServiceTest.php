<?php

namespace iEducar\Financeiro\Tests\Unit;

use PHPUnit\Framework\TestCase;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\DescontoService;

class DescontoServiceTest extends TestCase
{
    /**
     * Testa desconto de pontualidade pago antes do vencimento
     */
    public function testDescontoPontualidadeAntesDoVencimento()
    {
        $service = new DescontoService();

        $titulo = new Titulo([
            'valor_nominal' => 800.00,
            'valor_saldo' => 800.00,
            'data_vencimento' => '2026-05-10',
            'status' => 'aberto',
        ]);

        $desconto = $service->calcularDescontoPontualidade($titulo, '2026-05-08');

        // Política configurada padrão: 5% até o vencimento (5% de 800 = 40.00)
        $this->assertEquals(40.00, $desconto);
    }

    /**
     * Testa que desconto de pontualidade é zero se pago após o vencimento
     */
    public function testDescontoPontualidadeAposVencimentoRetornaZero()
    {
        $service = new DescontoService();

        $titulo = new Titulo([
            'valor_nominal' => 800.00,
            'valor_saldo' => 800.00,
            'data_vencimento' => '2026-05-10',
            'status' => 'vencido',
        ]);

        $desconto = $service->calcularDescontoPontualidade($titulo, '2026-05-11');

        $this->assertEquals(0.00, $desconto);
    }
}
