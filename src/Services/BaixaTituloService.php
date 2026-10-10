<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BaixaTituloService
{
    /**
     * Realiza liquidação (integral ou parcial) de um título financeiro
     *
     * @param Titulo $titulo
     * @param array $dados
     * @return Pagamento
     */
    public function realizarBaixa(Titulo $titulo, array $dados): Pagamento
    {
        return DB::transaction(function () use ($titulo, $dados) {
            if ($titulo->status === 'pago') {
                throw new InvalidArgumentException('Este título já se encontra totalmente quitado.');
            }
            if ($titulo->status === 'cancelado') {
                throw new InvalidArgumentException('Não é possível dar baixa em um título cancelado.');
            }

            $valorPago = (float) $dados['valor_pago'];
            if ($valorPago <= 0) {
                throw new InvalidArgumentException('O valor de pagamento deve ser superior a zero.');
            }

            $dataPagamento = Carbon::parse($dados['data_pagamento'] ?? Carbon::today());
            $descontoConcedido = (float) ($dados['valor_desconto_concedido'] ?? 0.00);
            $valorJuros = (float) ($dados['valor_juros'] ?? 0.00);
            $valorMulta = (float) ($dados['valor_multa'] ?? 0.00);
            $origem = $dados['origem_baixa'] ?? 'manual';
            $forma = $dados['forma_pagamento'] ?? 'dinheiro';
            $usuarioId = $dados['usuario_id'] ?? null;

            // Gera hash anti-fraude de comprovante
            $comprovanteHash = strtoupper(hash('sha256', sprintf(
                '%d|%s|%.2f|%s|%s',
                $titulo->id,
                $dataPagamento->format('Y-m-d'),
                $valorPago,
                $forma,
                Str::random(10)
            )));

            $pagamento = Pagamento::create([
                'titulo_id' => $titulo->id,
                'forma_pagamento' => $forma,
                'data_pagamento' => $dataPagamento,
                'data_compensacao' => $dataPagamento,
                'valor_pago' => $valorPago,
                'valor_juros' => $valorJuros,
                'valor_multa' => $valorMulta,
                'valor_desconto_concedido' => $descontoConcedido,
                'origem_baixa' => $origem,
                'usuario_id' => $usuarioId,
                'comprovante_hash' => substr($comprovanteHash, 0, 32),
                'estornado' => false,
            ]);

            // Atualiza saldo devedor do título:
            // Abate: valor efetivo que amortiza a dívida = (valorPago + desconto) - (juros + multa)
            $amortizacao = max(0, ($valorPago + $descontoConcedido) - ($valorJuros + $valorMulta));
            $novoSaldo = max(0, round($titulo->valor_saldo - $amortizacao, 2));

            $titulo->valor_saldo = $novoSaldo;
            $titulo->valor_pago = round($titulo->valor_pago + $valorPago, 2);
            $titulo->valor_juros = round($titulo->valor_juros + $valorJuros, 2);
            $titulo->valor_multa = round($titulo->valor_multa + $valorMulta, 2);
            $titulo->valor_desconto = round($titulo->valor_desconto + $descontoConcedido, 2);

            if ($novoSaldo <= 0.01) {
                $titulo->status = 'pago';
                $titulo->valor_saldo = 0.00;
            } else {
                $titulo->status = 'parcial';
            }

            $titulo->save();

            // Dispara evento de título pago para integrações fiscais (NFS-e) e auditoria
            event(new \iEducar\Financeiro\Events\TituloPago($titulo, $pagamento));

            return $pagamento;
        });
    }


    /**
     * Estorna um pagamento preservando histórico contábil auditado
     *
     * @param Pagamento $pagamento
     * @param string $motivo
     * @param int|null $usuarioId
     * @return void
     */
    public function estornarBaixa(Pagamento $pagamento, string $motivo, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($pagamento, $motivo, $usuarioId) {
            if ($pagamento->estornado) {
                throw new InvalidArgumentException('Este pagamento já foi estornado anteriormente.');
            }

            $titulo = $pagamento->titulo;

            // Restaura o saldo do título
            $amortizacao = max(0, ($pagamento->valor_pago + $pagamento->valor_desconto_concedido) - ($pagamento->valor_juros + $pagamento->valor_multa));
            $titulo->valor_saldo = round($titulo->valor_saldo + $amortizacao, 2);
            $titulo->valor_pago = max(0, round($titulo->valor_pago - $pagamento->valor_pago, 2));
            $titulo->valor_juros = max(0, round($titulo->valor_juros - $pagamento->valor_juros, 2));
            $titulo->valor_multa = max(0, round($titulo->valor_multa - $pagamento->valor_multa, 2));
            $titulo->valor_desconto = max(0, round($titulo->valor_desconto - $pagamento->valor_desconto_concedido, 2));

            if ($titulo->valor_pago > 0) {
                $titulo->status = 'parcial';
            } else {
                $titulo->status = $titulo->isVencido() ? 'vencido' : 'aberto';
            }
            $titulo->save();

            // Marca o pagamento como estornado
            $pagamento->update([
                'estornado' => true,
                'motivo_estorno' => $motivo,
                'data_estorno' => Carbon::now(),
                'estornado_por_usuario_id' => $usuarioId,
            ]);
        });
    }
}
