<?php

namespace iEducar\Financeiro\Commands;

use iEducar\Financeiro\Services\ReguaCobrancaService;
use Illuminate\Console\Command;

class ProcessarReguaCobrancaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financeiro:processar-regua {--escola= : ID da escola específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Varre mensalidades abertas e vencidas e dispara a régua de cobrança automática via Asaas/E-mail/WhatsApp';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ReguaCobrancaService $service)
    {
        $this->info('Iniciando processamento da régua de cobrança escolar...');

        $escolaId = $this->option('escola') ? (int) $this->option('escola') : null;

        $resumo = $service->processar($escolaId);

        $this->info(sprintf(
            'Régua concluída com sucesso! Regras avaliadas: %d | Notificações disparadas: %d | Duplicidades evitadas: %d',
            $resumo['total_regras'],
            $resumo['total_disparados'],
            $resumo['total_ignorados']
        ));

        return 0;
    }
}
