<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Desconto;
use iEducar\Financeiro\Models\Titulo;

class DescontoService
{
    /**
     * Calcula o desconto aplicável para liquidação de um título
     *
     * @param Titulo $titulo
     * @param Carbon|null $dataPagamento
     * @return float
     */
    public function calcularDescontoAplicavel(Titulo $titulo, ?Carbon $dataPagamento = null): float
    {
        $data = $dataPagamento ?? Carbon::today();
        $valorBase = $titulo->valor_saldo;

        $descontosAtivos = Desconto::where('ativo', true)
            ->where('vigencia_inicio', '<=', $data)
            ->where(function ($q) use ($data) {
                $q->whereNull('vigencia_fim')->orWhere('vigencia_fim', '>=', $data);
            })
            ->get();

        if ($descontosAtivos->isEmpty()) {
            return 0.00;
        }

        $descontoTotal = 0.00;
        $maiorDescontoNaoCumulativo = 0.00;

        foreach ($descontosAtivos as $desc) {
            $valorCalculado = 0.00;

            if ($desc->tipo === 'percentual') {
                $valorCalculado = round($valorBase * ($desc->valor_regra / 100), 2);
            } elseif ($desc->tipo === 'valor_fixo') {
                $valorCalculado = min($valorBase, $desc->valor_regra);
            } elseif ($desc->tipo === 'pontualidade') {
                // Pontualidade: se pago até a data de vencimento
                if ($data->lte($titulo->data_vencimento)) {
                    $valorCalculado = round($valorBase * ($desc->valor_regra / 100), 2);
                }
            }

            if ($desc->cumulativo) {
                $descontoTotal += $valorCalculado;
            } else {
                if ($valorCalculado > $maiorDescontoNaoCumulativo) {
                    $maiorDescontoNaoCumulativo = $valorCalculado;
                }
            }
        }

        return round(min($valorBase, $descontoTotal + $maiorDescontoNaoCumulativo), 2);
    }
}
