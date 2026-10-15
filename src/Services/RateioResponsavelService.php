<?php

namespace iEducar\Financeiro\Services;

use iEducar\Financeiro\Models\AlunoResponsavel;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RateioResponsavelService
{
    /**
     * Define e valida os percentuais de rateio dos responsáveis financeiros de um aluno.
     *
     * BUG-009: Garante soma estrita de 100,00%, exatamente um responsável principal
     * e percentuais positivos em transação segura com lock pessimista.
     *
     * @param  int        $alunoId
     * @param  array      $rateios                 Array associativo [responsavel_id => percentual]
     * @param  int|null   $responsavelPrincipalId ID do responsável principal
     * @return void
     * @throws InvalidArgumentException
     */
    public function definirRateio(int $alunoId, array $rateios, ?int $responsavelPrincipalId = null): void
    {
        if (empty($rateios)) {
            throw new InvalidArgumentException('Ao menos um responsável deve ser informado para o rateio.');
        }

        $soma = 0.00;
        foreach ($rateios as $respId => $pct) {
            $pctFloat = (float) $pct;
            if ($pctFloat <= 0) {
                throw new InvalidArgumentException("O percentual de rateio do responsável #{$respId} deve ser superior a zero.");
            }
            $soma += $pctFloat;
        }

        if (abs(round($soma, 2) - 100.00) > 0.01) {
            throw new InvalidArgumentException(sprintf(
                'A soma dos percentuais de rateio deve ser exatamente 100,00%%. Soma atual informada: %.2f%%.',
                $soma
            ));
        }

        // Se principal não especificado, escolhe o com maior percentual
        if (!$responsavelPrincipalId) {
            arsort($rateios);
            $responsavelPrincipalId = (int) array_key_first($rateios);
        }

        if (!array_key_exists($responsavelPrincipalId, $rateios)) {
            throw new InvalidArgumentException('O responsável principal informado não consta na lista de rateio.');
        }

        DB::transaction(function () use ($alunoId, $rateios, $responsavelPrincipalId) {
            // Bloqueia os registros de vínculos existentes do aluno
            $vinculosAtuais = AlunoResponsavel::where('aluno_id', $alunoId)
                ->lockForUpdate()
                ->get()
                ->keyBy('responsavel_id');

            // Zera principal em todos antes de aplicar
            AlunoResponsavel::where('aluno_id', $alunoId)->update(['responsavel_principal' => false]);

            foreach ($rateios as $respId => $pct) {
                $isPrincipal = ((int) $respId === (int) $responsavelPrincipalId);
                $pctFinal    = round((float) $pct, 2);

                if ($vinculosAtuais->has($respId)) {
                    $vinculosAtuais->get($respId)->update([
                        'percentual_rateio'     => $pctFinal,
                        'responsavel_principal' => $isPrincipal,
                    ]);
                } else {
                    AlunoResponsavel::create([
                        'aluno_id'              => $alunoId,
                        'responsavel_id'        => $respId,
                        'parentesco'            => 'Responsável',
                        'percentual_rateio'     => $pctFinal,
                        'responsavel_principal' => $isPrincipal,
                    ]);
                }
            }
        });
    }

    /**
     * Ajusta automaticamente os rateios existentes para acomodar a vinculação de um novo responsável.
     *
     * @param  int         $alunoId
     * @param  int         $novoResponsavelId
     * @param  string|null $parentesco
     * @param  float|null  $percentualDesejado
     * @return AlunoResponsavel
     */
    public function vincularComAjusteRateio(
        int $alunoId,
        int $novoResponsavelId,
        ?string $parentesco = 'Responsável',
        ?float $percentualDesejado = null
    ): AlunoResponsavel {
        return DB::transaction(function () use ($alunoId, $novoResponsavelId, $parentesco, $percentualDesejado) {
            $vinculosExistentes = AlunoResponsavel::where('aluno_id', $alunoId)
                ->where('responsavel_id', '!=', $novoResponsavelId)
                ->lockForUpdate()
                ->get();

            // Caso 1: Primeiro e único responsável do aluno
            if ($vinculosExistentes->isEmpty()) {
                return AlunoResponsavel::updateOrCreate(
                    [
                        'aluno_id'       => $alunoId,
                        'responsavel_id' => $novoResponsavelId,
                    ],
                    [
                        'parentesco'            => $parentesco ?: 'Responsável',
                        'percentual_rateio'     => 100.00,
                        'responsavel_principal' => true,
                    ]
                );
            }

            // Caso 2: Já existem responsáveis vinculados. Distribui de forma coerente.
            $qtdTotal = $vinculosExistentes->count() + 1;

            if ($percentualDesejado !== null && $percentualDesejado > 0 && $percentualDesejado < 100) {
                $pctNovo = round($percentualDesejado, 2);
                $pctRestante = round(100.00 - $pctNovo, 2);

                // Rateia o restante proporcionalmente entre os existentes
                $somaExistente = $vinculosExistentes->sum('percentual_rateio') ?: 100;
                $acumulado = 0.00;
                $totalExistentes = $vinculosExistentes->count();

                foreach ($vinculosExistentes as $i => $v) {
                    if ($i === $totalExistentes - 1) {
                        // Último absorve o resíduo para fechar exato
                        $v->percentual_rateio = round($pctRestante - $acumulado, 2);
                    } else {
                        $fatia = round(($v->percentual_rateio / $somaExistente) * $pctRestante, 2);
                        $v->percentual_rateio = $fatia;
                        $acumulado += $fatia;
                    }
                    $v->save();
                }
            } else {
                // Divisão igualitária padrão (ex: 50/50 para 2, 33.34/33.33/33.33 para 3)
                $fatiaPadrao = round(100.00 / $qtdTotal, 2);
                $acumulado = 0.00;

                foreach ($vinculosExistentes as $v) {
                    $v->percentual_rateio = $fatiaPadrao;
                    $v->save();
                    $acumulado += $fatiaPadrao;
                }
                $pctNovo = round(100.00 - $acumulado, 2);
            }

            // Mantém o principal atual se houver, ou define o novo como principal
            $temPrincipal = $vinculosExistentes->contains(fn($v) => (bool) $v->responsavel_principal);

            return AlunoResponsavel::updateOrCreate(
                [
                    'aluno_id'       => $alunoId,
                    'responsavel_id' => $novoResponsavelId,
                ],
                [
                    'parentesco'            => $parentesco ?: 'Responsável',
                    'percentual_rateio'     => $pctNovo,
                    'responsavel_principal' => !$temPrincipal,
                ]
            );
        });
    }
}
