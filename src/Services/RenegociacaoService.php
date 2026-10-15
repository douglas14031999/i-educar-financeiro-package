<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use Exception;
use iEducar\Financeiro\Models\Renegociacao;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RenegociacaoService
{
    /**
     * Simula as condições de um acordo de dívida
     *
     * @param array $titulosIds
     * @param float $descontoValor
     * @param int $qtdParcelas
     * @param string|null $primeiroVencimento
     * @return array
     */
    public function simular(array $titulosIds, float $descontoValor = 0.0, int $qtdParcelas = 1, ?string $primeiroVencimento = null): array
    {
        $titulos = Titulo::with(['responsavel'])
            ->whereIn('id', $titulosIds)
            ->whereIn('status', ['aberto', 'parcial', 'vencido'])
            ->get();

        if ($titulos->isEmpty()) {
            throw new Exception('Nenhum título elegível encontrado para renegociação.');
        }

        // A13 — Todos os títulos devem pertencer ao mesmo responsável financeiro
        $responsaveisIds = $titulos->pluck('responsavel_financeiro_id')->unique();
        if ($responsaveisIds->count() > 1) {
            throw new Exception(
                'Todos os títulos de uma renegociação devem pertencer ao mesmo responsável financeiro. ' .
                'IDs encontrados: ' . $responsaveisIds->implode(', ')
            );
        }

        $totalOriginal = (float) $titulos->sum('valor_saldo');

        if ($descontoValor >= $totalOriginal) {
            throw new Exception('O desconto concedido não pode ser maior ou igual ao total da dívida.');
        }

        $valorFinalAcordo = round($totalOriginal - $descontoValor, 2);
        $qtdParcelas      = max(1, min(24, $qtdParcelas));
        $dataInicial      = $primeiroVencimento ? Carbon::parse($primeiroVencimento) : Carbon::today()->addDays(5);

        // Distribui o valor entre as parcelas com Largest Remainder (mesmo algoritmo do ContratoService)
        // Garante que simulacao e confirmacao produzam exatamente os mesmos valores
        $totalCentavos = (int) round($valorFinalAcordo * 100);
        $baseCentavos  = (int) floor($totalCentavos / $qtdParcelas);
        $residuo       = $totalCentavos - ($baseCentavos * $qtdParcelas);

        $parcelasProjetadas = [];
        for ($i = 1; $i <= $qtdParcelas; $i++) {
            // Distribui o resíduo nas primeiras parcelas (uma unidade de centavo cada)
            $centavosExtra = ($i <= $residuo) ? 1 : 0;
            $valorParcela  = round(($baseCentavos + $centavosExtra) / 100, 2);
            $dataVenc      = (clone $dataInicial)->addMonths($i - 1);

            $parcelasProjetadas[] = [
                'numero_parcela'           => $i,
                'total_parcelas'           => $qtdParcelas,
                'data_vencimento'          => $dataVenc->format('Y-m-d'),
                'data_vencimento_formatada' => $dataVenc->format('d/m/Y'),
                'valor'                    => $valorParcela,
            ];
        }

        return [
            'titulos'           => $titulos,
            'total_original'    => $totalOriginal,
            'desconto_concedido' => $descontoValor,
            'valor_acordo'      => $valorFinalAcordo,
            'qtd_parcelas'      => $qtdParcelas,
            'parcelas'          => $parcelasProjetadas,
        ];
    }

    /**
     * Confirma o acordo de renegociação (Novação de Dívida)
     *
     * @param array $titulosIds
     * @param float $descontoValor
     * @param int $qtdParcelas
     * @param string|null $primeiroVencimento
     * @param int|null $usuarioId
     * @param float|null $valorOriginalEsperado
     * @return Renegociacao
     */
    public function confirmar(array $titulosIds, float $descontoValor = 0.0, int $qtdParcelas = 1, ?string $primeiroVencimento = null, ?int $usuarioId = null, ?float $valorOriginalEsperado = null): Renegociacao
    {
        // 1. Simulação prévia das condições
        $simulacao = $this->simular($titulosIds, $descontoValor, $qtdParcelas, $primeiroVencimento);
        $totalEsperado = $valorOriginalEsperado !== null ? (float) $valorOriginalEsperado : (float) $simulacao['total_original'];

        return DB::transaction(function () use ($titulosIds, $simulacao, $totalEsperado, $descontoValor, $qtdParcelas, $primeiroVencimento, $usuarioId) {
            // FIN-009: Reler e bloquear todos os títulos com lock exclusivo dentro da transação
            $titulosLocked = Titulo::lockForUpdate()
                ->with(['cobrancaGateway'])
                ->whereIn('id', $titulosIds)
                ->get();

            if ($titulosLocked->count() !== count(array_unique($titulosIds))) {
                throw new Exception('Um ou mais títulos selecionados não foram encontrados para confirmação.');
            }

            // Valida elegibilidade sob lock: nenhum pode estar pago, cancelado ou renegociado
            foreach ($titulosLocked as $t) {
                if (!in_array($t->status, ['aberto', 'parcial', 'vencido']) || (float) $t->valor_saldo < 0.01) {
                    throw new Exception("O título #{$t->id} não está mais elegível para renegociação (Status: {$t->status}).");
                }
            }

            // Validação de mesma escola (Isolamento Multiescola)
            $escolasIds = $titulosLocked->pluck('escola_id')->filter()->unique();
            if ($escolasIds->count() > 1) {
                throw new Exception('Todos os títulos da renegociação devem pertencer à mesma escola.');
            }
            $escolaId = $escolasIds->first();

            // Validação de mesmo responsável financeiro
            $responsaveisIds = $titulosLocked->pluck('responsavel_financeiro_id')->unique();
            if ($responsaveisIds->count() > 1) {
                throw new Exception('Todos os títulos da renegociação devem pertencer ao mesmo responsável financeiro.');
            }
            $responsavelId = $responsaveisIds->first();

            // Valida se o saldo total mudou após a simulação (ex: pagamento concorrente)
            $saldoAtualTotal = (float) $titulosLocked->sum('valor_saldo');
            if (abs($saldoAtualTotal - $totalEsperado) > 0.001) {
                throw new Exception(
                    'O saldo devedor dos títulos foi alterado após a simulação. ' .
                    'Por favor, realize uma nova simulação antes de confirmar o acordo.'
                );
            }

            // Verifica vínculo de aluno e contrato
            $alunosIds = $titulosLocked->pluck('aluno_id')->filter()->unique();
            $alunoIdUnico = $alunosIds->count() === 1 ? $alunosIds->first() : null;

            $contratosIds = $titulosLocked->pluck('contrato_id')->filter()->unique();
            $contratoIdUnico = $contratosIds->count() === 1 ? $contratosIds->first() : null;

            // Numeração segura do acordo
            $ano = date('Y');
            $proximoNumero = (int) DB::table('financeiro.renegociacoes')
                ->where('numero_acordo', 'like', "ACR-{$ano}-%")
                ->count() + 1;
            $numeroAcordo = sprintf('ACR-%s-%05d', $ano, $proximoNumero);

            // 1. Cria o registro da Novação
            $renegociacao = Renegociacao::create([
                'numero_acordo'            => $numeroAcordo,
                'aluno_id'                 => $alunoIdUnico,
                'responsavel_financeiro_id'=> $responsavelId,
                'valor_original'           => $simulacao['total_original'],
                'valor_desconto_concedido' => $simulacao['desconto_concedido'],
                'valor_acordo'             => $simulacao['valor_acordo'],
                'qtd_parcelas'             => $simulacao['qtd_parcelas'],
                'status'                   => 'ativo',
                'usuario_id'               => $usuarioId,
            ]);

            // 2. Vincula títulos originais, zera saldo, marca como renegociado e cancela cobranças externas
            foreach ($titulosLocked as $titulo) {
                $renegociacao->titulosOriginais()->attach($titulo->id);

                if ($titulo->cobrancaGateway && !empty($titulo->cobrancaGateway->gateway_charge_id)) {
                    try {
                        \iEducar\Financeiro\Jobs\CancelarCobrancaGatewayJob::dispatch($titulo->cobrancaGateway->id);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Erro ao despachar cancelamento Asaas para cobrança #{$titulo->cobrancaGateway->id} na renegociação: " . $e->getMessage());
                    }
                }

                $titulo->update([
                    'status'      => 'renegociado',
                    'valor_saldo' => 0.00,
                    'observacoes' => trim($titulo->observacoes . " | Renegociado no acordo {$numeroAcordo}"),
                ]);
            }

            // 3. Gera os novos títulos da renegociação
            foreach ($simulacao['parcelas'] as $p) {
                Titulo::create([
                    'uuid'                      => (string) Str::uuid(),
                    'contrato_id'               => $contratoIdUnico,
                    'renegociacao_id'           => $renegociacao->id,
                    'aluno_id'                  => $alunoIdUnico,
                    'responsavel_financeiro_id' => $responsavelId,
                    'escola_id'                 => $escolaId,
                    'tipo'                      => 'renegociacao',
                    'numero_parcela'            => $p['numero_parcela'],
                    'total_parcelas'            => $p['total_parcelas'],
                    'competencia'               => Carbon::parse($p['data_vencimento'])->format('Y-m'),
                    'data_vencimento'           => $p['data_vencimento'],
                    'valor_nominal'             => $p['valor'],
                    'valor_desconto'            => 0.00,
                    'valor_multa'               => 0.00,
                    'valor_juros'               => 0.00,
                    'valor_pago'                => 0.00,
                    'valor_saldo'               => $p['valor'],
                    'status'                    => 'aberto',
                    'observacoes'               => "Parcela {$p['numero_parcela']}/{$p['total_parcelas']} do Acordo {$numeroAcordo}",
                ]);
            }

            return $renegociacao;
        });
    }
}
