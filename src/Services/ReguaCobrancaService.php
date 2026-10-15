<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use iEducar\Financeiro\Jobs\EnviarNotificacaoCobrancaJob;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\ReguaEnvioLog;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Support\AlunoLookup;
use Illuminate\Support\Facades\Log;

class ReguaCobrancaService
{
    /**
     * Processa todas as regras ativas da régua de cobrança respeitando janela de tolerância
     *
     * @param int|null $escolaId
     * @return array Resumo do processamento
     */
    public function processar(?int $escolaId = null): array
    {
        $hoje = Carbon::today();
        $horaAtual = (int) now()->format('H');

        // BUG-027.10: Respeitar horário comercial (07h às 22h)
        if ($horaAtual >= 22 || $horaAtual < 7) {
            Log::info("Processamento da régua ignorado: execução fora do horário comercial ({$horaAtual}h).");
            return [
                'total_regras' => 0,
                'total_disparados' => 0,
                'total_ignorados' => 0,
                'motivo' => 'Fora do horário comercial permitido (07h às 22h)',
            ];
        }

        $queryRegras = ReguaCobrancaRegra::ativas();

        if ($escolaId) {
            $queryRegras->paraEscola($escolaId);
        }

        $regras = $queryRegras->get();
        $totalDisparados = 0;
        $totalIgnorados = 0;
        $diasTolerancia = (int) config('financeiro.politicas.dias_tolerancia', 2);

        foreach ($regras as $regra) {
            $dias = (int) $regra->dias_gatilho;

            // BUG-027.7: Janela de tolerância para cobrir eventuais falhas do scheduler
            // Se dias < 0 (antes do vencimento): janela de hoje até hoje + tolerância
            // Se dias >= 0 (após vencimento): janela de (alvo - tolerância) até alvo
            if ($dias < 0) {
                $dataAlvoInicio = (clone $hoje)->addDays(abs($dias))->format('Y-m-d');
                $dataAlvoFim    = (clone $hoje)->addDays(abs($dias) + $diasTolerancia)->format('Y-m-d');
            } elseif ($dias === 0) {
                $dataAlvoInicio = (clone $hoje)->subDays($diasTolerancia)->format('Y-m-d');
                $dataAlvoFim    = $hoje->format('Y-m-d');
            } else {
                $dataAlvoInicio = (clone $hoje)->subDays($dias + $diasTolerancia)->format('Y-m-d');
                $dataAlvoFim    = (clone $hoje)->subDays($dias)->format('Y-m-d');
            }

            // Busca títulos não liquidados dentro da janela de disparo
            $queryTitulos = Titulo::with(['responsavel', 'cobrancaGateway'])
                ->whereIn('status', ['aberto', 'parcial', 'vencido'])
                ->where('valor_saldo', '>', 0.005)
                ->whereBetween('data_vencimento', [$dataAlvoInicio, $dataAlvoFim]);

            if ($regra->escola_id) {
                $queryTitulos->where('escola_id', $regra->escola_id);
            } elseif ($escolaId) {
                $queryTitulos->where('escola_id', $escolaId);
            }

            $titulos = $queryTitulos->get();
            AlunoLookup::carregarParaTitulos($titulos);

            foreach ($titulos as $titulo) {
                // BUG-027.6: Considera já enviado apenas se houve envio com SUCESSO prévio para esta regra e título
                $jaEnviadoComSucesso = ReguaEnvioLog::where('regra_id', $regra->id)
                    ->where('titulo_id', $titulo->id)
                    ->where('sucesso', true)
                    ->exists();

                if ($jaEnviadoComSucesso) {
                    $totalIgnorados++;
                    continue;
                }

                // Dispara o Job assíncrono de notificação
                EnviarNotificacaoCobrancaJob::dispatch($regra, $titulo);
                $totalDisparados++;
            }
        }

        Log::info("Processamento da régua finalizado: {$totalDisparados} disparos agendados, {$totalIgnorados} ignorados por envio anterior.");

        return [
            'total_regras' => $regras->count(),
            'total_disparados' => $totalDisparados,
            'total_ignorados' => $totalIgnorados,
        ];
    }
}
