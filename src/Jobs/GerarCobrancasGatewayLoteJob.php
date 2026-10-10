<?php

namespace iEducar\Financeiro\Jobs;

use iEducar\Financeiro\Gateways\Drivers\AsaasGateway;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GerarCobrancasGatewayLoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array
     */
    protected $tituloIds;

    public function __construct(array $tituloIds)
    {
        $this->tituloIds = $tituloIds;
    }

    /**
     * Executa a emissão de cobranças digitais no Asaas em background
     *
     * @return void
     */
    public function handle()
    {
        $config = config('financeiro.gateways.asaas', []);
        $gateway = new AsaasGateway($config);

        $titulos = Titulo::with('responsavel')
            ->whereIn('id', $this->tituloIds)
            ->whereIn('status', ['aberto', 'parcial'])
            ->get();

        $sucessos = 0;
        $erros = 0;

        foreach ($titulos as $titulo) {
            try {
                // Emite no Asaas
                $gateway->criarCobranca($titulo);
                $sucessos++;
            } catch (\Throwable $e) {
                $erros++;
                Log::error("Erro ao emitir cobrança Asaas para o título #{$titulo->id}: " . $e->getMessage());
            }
        }

        Log::info(sprintf('Lote de cobranças Asaas concluído: %d emitidas com sucesso, %d falhas.', $sucessos, $erros));
    }
}
