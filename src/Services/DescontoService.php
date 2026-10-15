<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Desconto;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\DB;

class DescontoService
{
    /**
     * Calcula o desconto aplicável para liquidação de um título respeitando escopos e vigências (FEAT-06)
     *
     * @param Titulo $titulo
     * @param Carbon|null $dataPagamento
     * @return float
     */
    public function calcularDescontoAplicavel(Titulo $titulo, ?Carbon $dataPagamento = null): float
    {
        $detalhes = $this->calcularDescontosDetalhados($titulo, $dataPagamento);
        return $detalhes['total'];
    }

    /**
     * Calcula de forma discriminada todos os descontos aplicáveis ao título
     *
     * @param Titulo $titulo
     * @param Carbon|null $dataPagamento
     * @return array{total: float, itens: array}
     */
    public function calcularDescontosDetalhados(Titulo $titulo, ?Carbon $dataPagamento = null): array
    {
        $data = $dataPagamento ? (clone $dataPagamento) : Carbon::today();
        $valorBase = (float) $titulo->valor_saldo;

        if ($valorBase <= 0.005) {
            return ['total' => 0.00, 'itens' => []];
        }

        // Filtra descontos ativos, dentro da vigência e que tenham escopo aderente ao título
        $descontosAtivos = Desconto::aplicavelAoTitulo($titulo)
            ->where('vigencia_inicio', '<=', $data->format('Y-m-d'))
            ->where(function ($q) use ($data) {
                $q->whereNull('vigencia_fim')->orWhere('vigencia_fim', '>=', $data->format('Y-m-d'));
            })
            ->get();

        if ($descontosAtivos->isEmpty()) {
            return ['total' => 0.00, 'itens' => []];
        }

        $descontoTotalCumulativo = 0.00;
        $maiorNaoCumulativo = null;
        $maiorValorNaoCumulativo = 0.00;
        $itensCumulativos = [];

        foreach ($descontosAtivos as $desc) {
            $valorCalculado = $desc->calcularValorDesconto($valorBase, $data, $titulo);

            if ($valorCalculado <= 0) {
                continue;
            }

            if ($desc->cumulativo) {
                $descontoTotalCumulativo += $valorCalculado;
                $itensCumulativos[] = [
                    'desconto' => $desc,
                    'valor' => $valorCalculado,
                ];
            } else {
                if ($valorCalculado > $maiorValorNaoCumulativo) {
                    $maiorValorNaoCumulativo = $valorCalculado;
                    $maiorNaoCumulativo = [
                        'desconto' => $desc,
                        'valor' => $valorCalculado,
                    ];
                }
            }
        }

        $itensFinais = $itensCumulativos;
        $totalCalculado = $descontoTotalCumulativo;

        if ($maiorNaoCumulativo !== null) {
            $itensFinais[] = $maiorNaoCumulativo;
            $totalCalculado += $maiorValorNaoCumulativo;
        }

        $totalFinal = round(min($valorBase, $totalCalculado), 2);

        return [
            'total' => $totalFinal,
            'itens' => $itensFinais,
        ];
    }

    /**
     * Aplica o desconto e registra formalmente o histórico na tabela titulo_descontos (FEAT-06 / M11)
     *
     * @param Titulo $titulo
     * @param Carbon|null $dataPagamento
     * @param int|null $usuarioId
     * @param string|null $motivo
     * @return float Valor total de desconto aplicado
     */
    public function aplicarERegistrar(Titulo $titulo, ?Carbon $dataPagamento = null, ?int $usuarioId = null, ?string $motivo = null): float
    {
        $detalhes = $this->calcularDescontosDetalhados($titulo, $dataPagamento);
        $totalDesconto = $detalhes['total'];

        if ($totalDesconto <= 0) {
            return 0.00;
        }

        DB::transaction(function () use ($titulo, $detalhes, $usuarioId, $motivo, $totalDesconto) {
            foreach ($detalhes['itens'] as $item) {
                DB::table('financeiro.titulo_descontos')->insert([
                    'titulo_id' => $titulo->id,
                    'desconto_id' => $item['desconto']->id ?? null,
                    'valor_aplicado' => $item['valor'],
                    'concedido_por_usuario_id' => $usuarioId,
                    'motivo' => $motivo ?? ($item['desconto']->nome ?? 'Desconto ou Bolsa Concedida'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Atualiza os campos do título
            $novoDesconto = round((float) $titulo->valor_desconto + $totalDesconto, 2);
            $novoSaldo = max(0.00, round((float) $titulo->valor_saldo - $totalDesconto, 2));

            $titulo->valor_desconto = $novoDesconto;
            $titulo->valor_saldo = $novoSaldo;
            $titulo->save();
        });

        return $totalDesconto;
    }

    /**
     * Calcula o desconto por pontualidade aplicável
     *
     * @param Titulo $titulo
     * @param Carbon|null $dataPagamento
     * @return float
     */
    public function calcularDescontoPontualidade(Titulo $titulo, ?Carbon $dataPagamento = null): float
    {
        return $this->calcularDescontoAplicavel($titulo, $dataPagamento);
    }
}
