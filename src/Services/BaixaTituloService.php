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
            // Idempotência por chave
            if (!empty($dados['idempotency_key'])) {
                $pagamentoExistente = Pagamento::where('idempotency_key', $dados['idempotency_key'])->first();
                if ($pagamentoExistente) {
                    return $pagamentoExistente;
                }
            }

            // A04 — Relê o título com bloqueio pessimista para evitar race condition.
            // Qualquer baixa concorrente aguardará até o fim desta transação.
            /** @var Titulo $tituloLocked */
            $tituloLocked = Titulo::lockForUpdate()->findOrFail($titulo->id);

            if (!$tituloLocked->isElegivelBaixa()) {
                throw new \iEducar\Financeiro\Exceptions\TituloNaoBaixavelException(sprintf(
                    'Não é possível dar baixa em um título com status "%s".',
                    $tituloLocked->status
                ));
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
            $devolverTroco     = !empty($dados['devolver_troco']);

            // Valor efetivo que amortiza a dívida
            // Fórmula: o que de fato abate o principal = pagamento + desconto − encargos
            $amortizacao = round(
                max(0, ($valorPago + $descontoConcedido) - ($valorJuros + $valorMulta)),
                2
            );

            $saldoAtual    = (float) $tituloLocked->valor_saldo;
            $creditoGerado = 0.00;
            $trocoDevolvido = 0.00;

            // A05 — Pagamento excedente: gera crédito identificado ou devolve troco
            if ($amortizacao > $saldoAtual + 0.001) {
                $excedente = round($amortizacao - $saldoAtual, 2);
                $amortizacao = $saldoAtual; // Amortiza apenas o que há de saldo

                if ($devolverTroco) {
                    $trocoDevolvido = $excedente;
                } else {
                    $creditoGerado = $excedente;
                }
            }

            $novoSaldo = round($saldoAtual - $amortizacao, 2);

            // A06 — Saldo de 1 centavo NÃO é zerado silenciosamente.
            // Só marca como 'pago' se o saldo for verdadeiramente zero (< 0.005 após arredondamento).
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
                'titulo_id'                => $tituloLocked->id,
                'forma_pagamento'          => $forma,
                'data_pagamento'           => $dataPagamento,
                'data_compensacao'         => $dataPagamento,
                'valor_pago'               => $valorPago,
                'valor_amortizado'         => $amortizacao,
                'valor_juros'              => $valorJuros,
                'valor_multa'              => $valorMulta,
                'valor_desconto_concedido' => $descontoConcedido,
                'valor_credito_gerado'     => $creditoGerado,
                'troco'                    => $trocoDevolvido,
                'origem_baixa'             => $origem,
                'usuario_id'               => $usuarioId,
                'idempotency_key'          => $dados['idempotency_key'] ?? null,
                'lote_baixa_uuid'          => $dados['lote_baixa_uuid'] ?? null,
                'comprovante_hash'         => substr($comprovanteHash, 0, 32),
                'estornado'                => false,
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

            // Dispara notificacao SSE em tempo real para as telas ativas
            \iEducar\Financeiro\Services\RealtimeEventService::dispararPagamentoConfirmado($tituloLocked, $pagamento, $origem);

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
            // Relê e bloqueia o pagamento com lock exclusivo
            /** @var Pagamento $pagamentoLocked */
            $pagamentoLocked = Pagamento::lockForUpdate()->findOrFail($pagamento->id);

            if ($pagamentoLocked->estornado) {
                throw new InvalidArgumentException('Este pagamento já foi estornado anteriormente.');
            }

            // Tratamento explícito de crédito já utilizado (FIN-002)
            if ($pagamentoLocked->valor_credito_gerado > 0) {
                $creditoUtilizado = CreditoResponsavel::where('pagamento_id', $pagamentoLocked->id)
                    ->where('utilizado', true)
                    ->exists();

                if ($creditoUtilizado) {
                    throw new \RuntimeException(
                        "Não é possível estornar este pagamento pois o crédito excedente de R$ " .
                        number_format($pagamentoLocked->valor_credito_gerado, 2, ',', '.') .
                        " gerado por ele já foi utilizado pelo responsável."
                    );
                }

                // Cancela o crédito pendente não utilizado
                CreditoResponsavel::where('pagamento_id', $pagamentoLocked->id)
                    ->where('utilizado', false)
                    ->update(['cancelado' => true]);
            }

            // Relê o título com lock para garantir consistência concorrente
            /** @var Titulo $titulo */
            $titulo = Titulo::lockForUpdate()->findOrFail($pagamentoLocked->titulo_id);

            // Restaura exatamente a amortização efetivamente aplicada na baixa
            $amortizacao = $pagamentoLocked->valor_amortizado > 0
                ? (float) $pagamentoLocked->valor_amortizado
                : round(
                    max(0, ($pagamentoLocked->valor_pago + $pagamentoLocked->valor_desconto_concedido)
                        - ($pagamentoLocked->valor_juros + $pagamentoLocked->valor_multa)
                        - (float) $pagamentoLocked->valor_credito_gerado),
                    2
                );

            $titulo->valor_saldo  = round((float) $titulo->valor_saldo + $amortizacao, 2);
            $titulo->valor_pago   = max(0, round((float) $titulo->valor_pago - $pagamentoLocked->valor_pago, 2));
            $titulo->valor_juros  = max(0, round((float) $titulo->valor_juros - $pagamentoLocked->valor_juros, 2));
            $titulo->valor_multa  = max(0, round((float) $titulo->valor_multa - $pagamentoLocked->valor_multa, 2));
            $titulo->valor_desconto = max(0, round(
                (float) $titulo->valor_desconto - $pagamentoLocked->valor_desconto_concedido, 2
            ));
            $titulo->status = $titulo->valor_pago > 0 ? 'parcial'
                : ($titulo->isVencido() ? 'vencido' : 'aberto');
            $titulo->save();

            $pagamentoLocked->update([
                'estornado'                => true,
                'motivo_estorno'           => $motivo,
                'data_estorno'             => Carbon::now(),
                'estornado_por_usuario_id' => $usuarioId,
            ]);
        });
    }

    /**
     * Aplica crédito disponível de um responsável financeiro para amortizar/liquidar um título (BUG-004).
     *
     * @param  Titulo             $titulo
     * @param  CreditoResponsavel $credito
     * @param  int|null           $usuarioId
     * @return Pagamento
     */
    public function aplicarCredito(Titulo $titulo, CreditoResponsavel $credito, ?int $usuarioId = null): Pagamento
    {
        return DB::transaction(function () use ($titulo, $credito, $usuarioId) {
            /** @var CreditoResponsavel $creditoLocked */
            $creditoLocked = CreditoResponsavel::lockForUpdate()->findOrFail($credito->id);

            if ($creditoLocked->utilizado || $creditoLocked->cancelado) {
                throw new InvalidArgumentException('Este crédito não está disponível para utilização.');
            }

            if ((int) $creditoLocked->responsavel_financeiro_id !== (int) $titulo->responsavel_financeiro_id) {
                throw new InvalidArgumentException('O crédito selecionado pertence a outro responsável financeiro.');
            }

            $valorDisponivel = (float) $creditoLocked->valor;
            $saldoTitulo     = (float) $titulo->valor_saldo;

            if ($valorDisponivel <= 0) {
                throw new InvalidArgumentException('O valor do crédito deve ser superior a zero.');
            }

            $valorAbater = round(min($valorDisponivel, $saldoTitulo), 2);

            $pagamento = $this->realizarBaixa($titulo, [
                'valor_pago'       => $valorAbater,
                'forma_pagamento'  => 'credito_saldo',
                'origem_baixa'     => 'aplicacao_credito',
                'usuario_id'       => $usuarioId,
                'data_pagamento'   => Carbon::today(),
            ]);

            $creditoLocked->utilizado = true;
            $creditoLocked->save();

            // Se sobrou saldo no crédito, cria um novo crédito residual
            $saldoRestante = round($valorDisponivel - $valorAbater, 2);
            if ($saldoRestante > 0.005) {
                CreditoResponsavel::create([
                    'responsavel_financeiro_id' => $creditoLocked->responsavel_financeiro_id,
                    'pagamento_id'              => $creditoLocked->pagamento_id,
                    'valor'                     => $saldoRestante,
                    'origem'                    => 'residuo_credito',
                    'descricao'                 => "Saldo remanescente após abatimento no título #{$titulo->id}",
                    'utilizado'                 => false,
                    'cancelado'                 => false,
                ]);
            }

            return $pagamento;
        });
    }
}
