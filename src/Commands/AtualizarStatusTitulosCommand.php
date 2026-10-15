<?php

namespace iEducar\Financeiro\Commands;

use Carbon\Carbon;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AtualizarStatusTitulosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financeiro:atualizar-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza status de títulos vencidos e consolida títulos com saldo zerado';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $hoje = Carbon::today()->toDateString();
        $this->info("Executando atualização de status dos títulos para {$hoje}...");

        // 1. Marca títulos vencidos (aberto ou parcial cujo vencimento ou prorrogação é anterior a hoje)
        $titulosParaVencer = Titulo::whereIn('status', ['aberto', 'parcial'])
            ->where('valor_saldo', '>', 0.005)
            ->where(function ($q) use ($hoje) {
                $q->where(function ($sub) use ($hoje) {
                    $sub->whereNull('data_prorrogacao')
                        ->where('data_vencimento', '<', $hoje);
                })->orWhere(function ($sub) use ($hoje) {
                    $sub->whereNotNull('data_prorrogacao')
                        ->where('data_prorrogacao', '<', $hoje);
                });
            });

        $countVencidos = $titulosParaVencer->count();
        if ($countVencidos > 0) {
            $titulosParaVencer->update(['status' => 'vencido']);
            $this->info("{$countVencidos} título(s) atualizados para o status 'vencido'.");
            Log::info("financeiro:atualizar-status: {$countVencidos} títulos marcados como vencidos.");
        }

        // 2. Corrige títulos com saldo zerado (<= 0.005) que ainda não constam como 'pago'
        $titulosQuitados = Titulo::whereIn('status', ['aberto', 'parcial', 'vencido'])
            ->where('valor_saldo', '<=', 0.005)
            ->get();

        $countQuitados = $titulosQuitados->count();
        if ($countQuitados > 0) {
            foreach ($titulosQuitados as $titulo) {
                Log::notice("financeiro:atualizar-status: Título #{$titulo->id} com saldo R$ {$titulo->valor_saldo} corrigido para 'pago'.");
                $titulo->update([
                    'status' => 'pago',
                    'valor_saldo' => 0.00,
                ]);
            }
            $this->info("{$countQuitados} título(s) com saldo zerado consolidados para 'pago'.");
        }

        $this->info('Atualização de status finalizada com sucesso.');
        return 0;
    }
}
