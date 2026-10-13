<?php

namespace iEducar\Financeiro\Tests\Unit;

use PHPUnit\Framework\TestCase;
use iEducar\Financeiro\Services\DescontoService;

/**
 * Teste PURO de DescontoService — sem Eloquent, sem banco.
 *
 * A09 — Unit tests não devem instanciar Eloquent Models diretamente.
 * Usamos stdClass para simular o contrato do Titulo (duck typing no service).
 */
class DescontoServiceTest extends TestCase
{
    private function makeTituloStub(array $attrs): object
    {
        $obj = new \stdClass();
        foreach ($attrs as $k => $v) {
            $obj->{$k} = $v;
        }
        return $obj;
    }

    public function testDescontoPontualidadeAntesDoVencimento(): void
    {
        $service = new DescontoService();

        $titulo = $this->makeTituloStub([
            'valor_nominal'   => 800.00,
            'valor_saldo'     => 800.00,
            'data_vencimento' => '2026-05-10',
            'status'          => 'aberto',
        ]);

        $desconto = $service->calcularDescontoPontualidade($titulo, '2026-05-08');

        // Política padrão: 5% até o vencimento (5% de 800 = 40.00)
        $this->assertEquals(40.00, $desconto);
    }

    public function testDescontoPontualidadeAposVencimentoRetornaZero(): void
    {
        $service = new DescontoService();

        $titulo = $this->makeTituloStub([
            'valor_nominal'   => 800.00,
            'valor_saldo'     => 800.00,
            'data_vencimento' => '2026-05-10',
            'status'          => 'vencido',
        ]);

        $desconto = $service->calcularDescontoPontualidade($titulo, '2026-05-11');

        $this->assertEquals(0.00, $desconto);
    }
}
