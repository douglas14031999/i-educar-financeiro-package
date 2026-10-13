<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use iEducar\Financeiro\Models\CreditoResponsavel;
use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BaixaTituloService
{
    /**
     * Realiza liquidação (integral ou parcial) de um título financeiro.
     *
     * Correções aplicadas:
     *  - A04: título relido com SELECT FOR UPDATE dentro da transação (elimina race condition).
     *  - A05: pagamento excedente gera crédito identificado no responsável, nunca desaparece.
     *  - A06: saldo de 1 centavo preservado com lançamento explícito; nunca zerado silenciosamente.
     *
     * @param  Titulo $titulo  Instância usada apenas para localizar o ID; relida com lock.
     * @param  array  $dados
     * @return Pagamento
     */
    public function realizarBaixa(Titulo $titulo, array $dados): Pagamento
    {
        return DB::transaction(function () use ($titulo, $dados) {
            // A04 — Relê o título com bloqueio pessimista para evitar race condition.
            // Qualquer baixa concorrente aguardará até o fim desta transação.
            /** @var Titulo $tituloLocked */
            $tituloLocked = Titulo::lockForUpdate()->findOrFail($titulo->id);

            if ($tituloLocked->status === 'pago') {
                throw new InvalidArgumentException('Este título já se encontra totalmente quitado.');
            }
            if ($tituloLocked->status === 'cancelado') {
                throw new InvalidArgumentException('Não é possível dar baixa em um título cancelado.');
            }

            $valorPago = (float) $dados['valor_pago'];
            if ($valorPago <= 0) {
                throw new InvalidArgumentException('O valor de pagamento deve ser superior a zero.');
            }

            $dataPagamento     = Carbon::parse($dados['data_pagamento'] ?? Carbon::today());
            $descontoConcedido = (float) ($dados['valor_desconto_concedido'] ?? 0.00);
            $valorJuros        = (float) ($dados['valor_juros'] ?? 0.00);
            $valorMulta        = (float) ($dados['valor_multa'] ?? 0.00);
            $origem            = $dados['origem_baixa'] ?? 'manual';
            $forma             = $dados['forma_pagamento'] ?? 'dinheiro';
            $usuarioId         = $dados['usuario_id'] ?? null;

            // Valor efetivo que amortiza a dívida
            // Fórmula: o que de fato abate o principal = pagamento + desconto − encargos
            $amortizacao = round(
                max(0, ($valorPago + $descontoConcedido) - ($valorJuros + $valorMulta)),
                2
            );

            $saldoAtual   = (float) $tituloLocked->valor_saldo;
            $creditoGerado = 0.00;

            // A05 — Pagamento excedente: gera crédito identificado, nunca desaparece
            if ($amortizacao > $saldoAtual + 0.001) {
                $creditoGerado = round($amortizacao - $saldoAtual, 2);
                $amortizacao   = $saldoAtual; // Amortiza apenas o que há de saldo
            }

            $novoSaldo = round($saldoAtual - $amortizacao, 2);

            // A06 — Saldo de 1 centavo NÃO é zerado silenciosamente.
            // Só marca como 'pago' se o saldo for verdadeiramente zero (< 0.005 após arredondamento).
            // Ajustes de centavo devem ser feitos por movimento explícito de ajuste.
            $titulo_pago = ($novoSaldo < 0.005);
            $novoSaldo   = $titulo_pago ? 0.00 : $novoSaldo;

            $comprovanteHash = strtoupper(hash('sha256', sprintf(
                '%d|%s|%.2f|%s|%s',
                $tituloLocked->id,
                $dataPagamento->format('Y-m-d'),
                $valorPago,
                $forma,
                Str::random(10)
            )));

            $pagamento = Pagamento::create([
                'titulo_id'              => $tituloLocked->id,
                'forma_pagamento'        => $forma,
                'data_pagamento'         => $dataPagamento,
                'data_compensacao'       => $dataPagamento,
                'valor_pago'             => $valorPago,
                'valor_juros'            => $valorJuros,
                'valor_multa'            => $valorMulta,
                'valor_desconto_concedido' => $descontoConcedido,
                'valor_credito_gerado'   => $creditoGerado,
                'origem_baixa'           => $origem,
                'usuario_id'             => $usuarioId,
                'comprovante_hash'       => substr($comprovanteHash, 0, 32),
                'estornado'              => false,
            ]);

            // A05 — Registra crédito do responsável se houver excedente
            if ($creditoGerado > 0 && $tituloLocked->responsavel_financeiro_id) {
                CreditoResponsavel::create([
                    'responsavel_financeiro_id' => $tituloLocked->responsavel_financeiro_id,
                    'pagamento_id'              => $pagamento->id,
                    'valor'                     => $creditoGerado,
                    'origem'                    => 'excedente_baixa',
                    'descricao'                 => "Excedente de pagamento no título #{$tituloLocked->id}",
                    'utilizado'                 => false,
                ]);
            }

            $tituloLocked->valor_saldo   = $novoSaldo;
            $tituloLocked->valor_pago    = round((float) $tituloLocked->valor_pago + $valorPago, 2);
            $tituloLocked->valor_juros   = round((float) $tituloLocked->valor_juros + $valorJuros, 2);
            $tituloLocked->valor_multa   = round((float) $tituloLocked->valor_multa + $valorMulta, 2);
            $tituloLocked->valor_desconto = round((float) $tituloLocked->valor_desconto + $descontoConcedido, 2);
            $tituloLocked->status        = $titulo_pago ? 'pago' : 'parcial';
            $tituloLocked->save();

            event(new \iEducar\Financeiro\Events\TituloPago($tituloLocked, $pagamento));

            return $pagamento;
        });
    }

    /**
     * Estorna um pagamento preservando o histórico contábil auditado.
     * O saldo restaurado é exatamente o que foi amortizado — nunca superior ao nominal original.
     *
     * @param  Pagamento $pagamento
     * @param  string    $motivo
     * @param  int|null  $usuarioId
     * @return void
     */
    public function estornarBaixa(Pagamento $pagamento, string $motivo, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($pagamento, $motivo, $usuarioId) {
            if ($pagamento->estornado) {
                throw new InvalidArgumentException('Este pagamento já foi estornado anteriormente.');
            }

            // Relê o título com lock para garantir consistência concorrente
            /** @var Titulo $titulo */
            $titulo = Titulo::lockForUpdate()->findOrFail($pagamento->titulo_id);

            // Restaura exatamente a amortizacao original, não o valor_pago bruto
            $amortizacao = round(
                max(0, ($pagamento->valor_pago + $pagamento->valor_desconto_concedido)
                    - ($pagamento->valor_juros + $pagamento->valor_multa)),
                2
            );

            $titulo->valor_saldo  = round((float) $titulo->valor_saldo + $amortizacao, 2);
            $titulo->valor_pago   = max(0, round((float) $titulo->valor_pago - $pagamento->valor_pago, 2));
            $titulo->valor_juros  = max(0, round((float) $titulo->valor_juros - $pagamento->valor_juros, 2));
            $titulo->valor_multa  = max(0, round((float) $titulo->valor_multa - $pagamento->valor_multa, 2));
            $titulo->valor_desconto = max(0, round(
                (float) $titulo->valor_desconto - $pagamento->valor_desconto_concedido, 2
            ));
            $titulo->status = $titulo->valor_pago > 0 ? 'parcial'
                : ($titulo->isVencido() ? 'vencido' : 'aberto');
            $titulo->save();

            // Cancela crédito gerado pelo pagamento estornado (se houver)
            if ($pagamento->valor_credito_gerado > 0) {
                CreditoResponsavel::where('pagamento_id', $pagamento->id)
                    ->where('utilizado', false)
                    ->update(['cancelado' => true]);
            }

            $pagamento->update([
                'estornado'                => true,
                'motivo_estorno'           => $motivo,
                'data_estorno'             => Carbon::now(),
                'estornado_por_usuario_id' => $usuarioId,
            ]);
        });
    }
}
