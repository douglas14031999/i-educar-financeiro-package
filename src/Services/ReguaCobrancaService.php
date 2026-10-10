<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use iEducar\Financeiro\Jobs\EnviarNotificacaoCobrancaJob;
use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\ReguaEnvioLog;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\Log;

class ReguaCobrancaService
{
    /**
     * Processa todas as regras ativas da régua de cobrança
     *
     * @param int|null $escolaId
     * @return array Resumo do processamento
     */
    public function processar(?int $escolaId = null): array
    {
        $hoje = Carbon::today();
        $queryRegras = ReguaCobrancaRegra::ativas();

        if ($escolaId) {
            $queryRegras->paraEscola($escolaId);
        }

        $regras = $queryRegras->get();
        $totalDisparados = 0;
        $totalIgnorados = 0;

        foreach ($regras as $regra) {
            $dias = $regra->dias_gatilho;

            // Calcula a data de vencimento alvo
            if ($dias < 0) {
                // Dias antes do vencimento (Ex: -7 -> vence daqui a 7 dias)
                $dataAlvo = (clone $hoje)->addDays(abs($dias))->format('Y-m-d');
            } elseif ($dias === 0) {
                // No vencimento (Hoje)
                $dataAlvo = $hoje->format('Y-m-d');
            } else {
                // Dias após o vencimento (Ex: 3 -> venceu há 3 dias)
                $dataAlvo = (clone $hoje)->subDays($dias)->format('Y-m-d');
            }

            // Busca títulos não liquidados com a data alvo
            $queryTitulos = Titulo::with(['responsavel', 'cobrancaGateway'])
                ->whereIn('status', ['aberto', 'parcial', 'vencido'])
                ->whereDate('data_vencimento', $dataAlvo);

            if ($regra->escola_id) {
                $queryTitulos->where('escola_id', $regra->escola_id);
            } elseif ($escolaId) {
                $queryTitulos->where('escola_id', $escolaId);
            }

            $titulos = $queryTitulos->get();

            foreach ($titulos as $titulo) {
                // Verifica se já foi disparada notificação para esta regra e título hoje
                $jaEnviadoHoje = ReguaEnvioLog::where('regra_id', $regra->id)
                    ->where('titulo_id', $titulo->id)
                    ->whereDate('enviado_em', $hoje->format('Y-m-d'))
                    ->exists();

                if ($jaEnviadoHoje) {
                    $totalIgnorados++;
                    continue;
                }

                // Dispara o Job assíncrono de notificação
                EnviarNotificacaoCobrancaJob::dispatch($regra, $titulo);
                $totalDisparados++;
            }
        }

        Log::info("Processamento da régua finalizado: {$totalDisparados} disparos agendados, {$totalIgnorados} ignorados por duplicidade no dia.");

        return [
            'total_regras' => $regras->count(),
            'total_disparados' => $totalDisparados,
            'total_ignorados' => $totalIgnorados,
        ];
    }
}
