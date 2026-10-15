<?php

namespace iEducar\Financeiro\Commands;

use iEducar\Financeiro\Jobs\ProcessarWebhookJob;
use iEducar\Financeiro\Models\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReprocessarWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financeiro:reprocessar-webhooks {--limit=50 : Quantidade máxima de eventos para reprocessar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocessa eventos de webhook pendentes ou que falharam anteriormente';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit = (int) ($this->option('limit') ?: 50);

        $eventos = WebhookEvent::where('processado', false)
            ->where('requer_acao', false)
            ->where('tentativas', '<', 10)
            ->where(function ($query) {
                $query->whereNull('proxima_tentativa_em')
                    ->orWhere('proxima_tentativa_em', '<=', now());
            })
            ->limit($limit)
            ->get();

        if ($eventos->isEmpty()) {
            $this->info('Nenhum evento de webhook pendente para reprocessar.');
            return 0;
        }

        $this->info(sprintf('Reprocessando %d evento(s) de webhook...', $eventos->count()));

        $sucessos = 0;
        foreach ($eventos as $evento) {
            try {
                $evento->increment('tentativas');
                // Backoff exponencial simples: 2^tentativas minutos
                $minutosEspera = min(1440, pow(2, $evento->tentativas));
                $evento->update([
                    'proxima_tentativa_em' => now()->addMinutes($minutosEspera),
                ]);

                ProcessarWebhookJob::dispatch($evento->id);
                $sucessos++;
            } catch (\Throwable $e) {
                Log::error("Erro ao reenfileirar webhook #{$evento->id}: " . $e->getMessage());
                $evento->update([
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        $this->info("{$sucessos} evento(s) de webhook reenfileirados com sucesso.");

        return 0;
    }
}
