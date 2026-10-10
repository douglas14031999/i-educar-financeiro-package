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

        $totalOriginal = (float) $titulos->sum('valor_saldo');

        if ($descontoValor >= $totalOriginal) {
            throw new Exception('O desconto concedido não pode ser maior ou igual ao total da dívida.');
        }

        $valorFinalAcordo = round($totalOriginal - $descontoValor, 2);
        $qtdParcelas = max(1, min(24, $qtdParcelas));
        $dataInicial = $primeiroVencimento ? Carbon::parse($primeiroVencimento) : Carbon::today()->addDays(5);

        // Divisão em parcelas com ajuste de centavos na 1ª parcela
        $valorBaseParcela = floor(($valorFinalAcordo / $qtdParcelas) * 100) / 100;
        $diferencaCentavos = round($valorFinalAcordo - ($valorBaseParcela * $qtdParcelas), 2);

        $parcelasProjetadas = [];
        for ($i = 1; $i <= $qtdParcelas; $i++) {
            $valorParcela = ($i === 1) ? round($valorBaseParcela + $diferencaCentavos, 2) : $valorBaseParcela;
            $dataVenc = (clone $dataInicial)->addMonths($i - 1);

            $parcelasProjetadas[] = [
                'numero_parcela' => $i,
                'total_parcelas' => $qtdParcelas,
                'data_vencimento' => $dataVenc->format('Y-m-d'),
                'data_vencimento_formatada' => $dataVenc->format('d/m/Y'),
                'valor' => $valorParcela,
            ];
        }

        return [
            'titulos' => $titulos,
            'total_original' => $totalOriginal,
            'desconto_concedido' => $descontoValor,
            'valor_acordo' => $valorFinalAcordo,
            'qtd_parcelas' => $qtdParcelas,
            'parcelas' => $parcelasProjetadas,
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
     * @return Renegociacao
     */
    public function confirmar(array $titulosIds, float $descontoValor = 0.0, int $qtdParcelas = 1, ?string $primeiroVencimento = null, ?int $usuarioId = null): Renegociacao
    {
        $simulacao = $this->simular($titulosIds, $descontoValor, $qtdParcelas, $primeiroVencimento);
        $titulosOriginais = $simulacao['titulos'];
        $primeiroTitulo = $titulosOriginais->first();

        return DB::transaction(function () use ($simulacao, $titulosOriginais, $primeiroTitulo, $usuarioId) {
            $ano = date('Y');
            $numeroAcordo = sprintf('ACR-%s-%05d', $ano, Renegociacao::whereYear('created_at', $ano)->count() + 1);

            // 1. Cria o registro do Acordo
            $renegociacao = Renegociacao::create([
                'numero_acordo' => $numeroAcordo,
                'aluno_id' => $primeiroTitulo->aluno_id,
                'responsavel_financeiro_id' => $primeiroTitulo->responsavel_financeiro_id,
                'valor_original' => $simulacao['total_original'],
                'valor_desconto_concedido' => $simulacao['desconto_concedido'],
                'valor_acordo' => $simulacao['valor_acordo'],
                'qtd_parcelas' => $simulacao['qtd_parcelas'],
                'status' => 'ativo',
                'usuario_id' => $usuarioId,
            ]);

            // 2. Vincula títulos originais e altera status para 'renegociado'
            foreach ($titulosOriginais as $titulo) {
                $renegociacao->titulosOriginais()->attach($titulo->id);

                $titulo->update([
                    'status' => 'renegociado',
                    'valor_saldo' => 0.00,
                    'observacoes' => trim($titulo->observacoes . " | Renegociado no acordo {$numeroAcordo}"),
                ]);
            }

            // 3. Gera os novos títulos da renegociação
            foreach ($simulacao['parcelas'] as $p) {
                Titulo::create([
                    'uuid' => (string) Str::uuid(),
                    'contrato_id' => $primeiroTitulo->contrato_id,
                    'renegociacao_id' => $renegociacao->id,
                    'aluno_id' => $primeiroTitulo->aluno_id,
                    'responsavel_financeiro_id' => $primeiroTitulo->responsavel_financeiro_id,
                    'escola_id' => $primeiroTitulo->escola_id,
                    'tipo' => 'renegociacao',
                    'numero_parcela' => $p['numero_parcela'],
                    'total_parcelas' => $p['total_parcelas'],
                    'competencia' => Carbon::parse($p['data_vencimento'])->format('Y-m'),
                    'data_vencimento' => $p['data_vencimento'],
                    'valor_nominal' => $p['valor'],
                    'valor_desconto' => 0.00,
                    'valor_multa' => 0.00,
                    'valor_juros' => 0.00,
                    'valor_pago' => 0.00,
                    'valor_saldo' => $p['valor'],
                    'status' => 'aberto',
                    'observacoes' => "Parcela {$p['numero_parcela']}/{$p['total_parcelas']} do Acordo {$numeroAcordo}",
                ]);
            }

            return $renegociacao;
        });
    }
}
