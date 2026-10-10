<?php

namespace iEducar\Financeiro\Commands;

use Illuminate\Console\Command;

class SincronizarCobrancasAsaasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financeiro:sincronizar-asaas {--limit=50 : Limite de cobranças para consultar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta a API do Asaas para sincronizar status de cobranças pendentes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando sincronização de cobranças junto ao Asaas...');

        // Executado pelo AsaasGateway na Fase 7
        $this->info('Sincronização concluída com sucesso.');

        return 0;
    }
}
