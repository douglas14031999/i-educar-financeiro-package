<?php

namespace iEducar\Financeiro\Support;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Titulo;

class CalculoEncargos
{
    /**
     * Calcula a quantidade de dias corridos de atraso com precisão (BUG-013 / Carbon 3)
     *
     * @param  Carbon|string      $dataVencimento
     * @param  Carbon|string|null $dataReferencia
     * @param  int                $diasTolerancia
     * @return int
     */
    public static function diasAtraso($dataVencimento, $dataReferencia = null, int $diasTolerancia = 0): int
    {
        $venc = Carbon::parse($dataVencimento)->startOfDay();
        $ref  = $dataReferencia ? Carbon::parse($dataReferencia)->startOfDay() : Carbon::today()->startOfDay();

        // Se ainda não venceu
        if ($venc->gte($ref)) {
            return 0;
        }

        // Dias corridos entre vencimento e data de referência
        $dias = (int) abs($ref->diffInDays($venc));

        // Aplica dias de tolerância da configuração
        if ($dias <= $diasTolerancia) {
            return 0;
        }

        return max(0, $dias);
    }

    /**
     * Calcula o valor da multa moratória (padrão 2.00% legal)
     */
    public static function calcularMulta(float $saldo, int $diasAtraso, ?float $percentualMulta = null): float
    {
        if ($diasAtraso <= 0 || $saldo <= 0) {
            return 0.00;
        }

        $taxa = $percentualMulta ?? (float) config('financeiro.politicas.multa_percentual', 2.00);
        return round($saldo * ($taxa / 100.0), 2);
    }

    /**
     * Calcula os juros moratórios pro-rata die (padrão 1.00% a.m. / 30 dias)
     */
    public static function calcularJuros(float $saldo, int $diasAtraso, ?float $jurosMensal = null): float
    {
        if ($diasAtraso <= 0 || $saldo <= 0) {
            return 0.00;
        }

        $taxaMes = $jurosMensal ?? (float) config('financeiro.politicas.juros_mensal_percentual', 1.00);
        $taxaDiaria = ($taxaMes / 100.0) / 30.0;

        return round($saldo * $taxaDiaria * $diasAtraso, 2);
    }

    /**
     * Calcula demonstrativo completo de encargos para um título
     */
    public static function calcularTotais(Titulo $titulo, $dataReferencia = null): array
    {
        $vencimentoEfetivo = $titulo->data_prorrogacao ?? $titulo->data_vencimento;
        $diasTolerancia     = (int) config('financeiro.politicas.dias_tolerancia', 0);
        $diasAtraso         = static::diasAtraso($vencimentoEfetivo, $dataReferencia, $diasTolerancia);

        $saldo = (float) $titulo->valor_saldo;
        $multa = static::calcularMulta($saldo, $diasAtraso);
        $juros = static::calcularJuros($saldo, $diasAtraso);
        $total = round($saldo + $multa + $juros, 2);

        return [
            'saldo'          => $saldo,
            'dias_atraso'    => $diasAtraso,
            'multa'          => $multa,
            'juros'          => $juros,
            'total_devido'   => $total,
        ];
    }
}
