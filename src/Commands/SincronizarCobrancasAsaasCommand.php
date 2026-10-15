<?php

namespace iEducar\Financeiro\Commands;

use iEducar\Financeiro\Gateways\GatewayManager;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Services\BaixaTituloService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
    public function handle(GatewayManager $gatewayManager, BaixaTituloService $baixaService)
    {
        $limit = (int) $this->option('limit') ?: 50;
        $this->info("Iniciando sincronização de até {$limit} cobranças ativas junto ao Asaas...");

        $gateway = $gatewayManager->driver('asaas');

        $titulos = Titulo::with(['cobrancaGateway', 'responsavel'])
            ->whereIn('status', ['aberto', 'parcial', 'vencido'])
            ->whereHas('cobrancaGateway', function ($q) {
                $q->whereNotNull('gateway_charge_id')
                  ->where('ativa', true);
            })
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        if ($titulos->isEmpty()) {
            $this->info('Nenhuma cobrança pendente para sincronizar.');
            return 0;
        }

        $processados = 0;
        $baixados = 0;
        $erros = 0;

        foreach ($titulos as $titulo) {
            $processados++;
            $chargeId = $titulo->cobrancaGateway->gateway_charge_id;

            try {
                $dados = $gateway->consultarCobranca($chargeId);
                if (empty($dados)) {
                    continue;
                }

                $statusExterno = $dados['status'] ?? null;
                $updateData = [
                    'status_externo' => $statusExterno,
                    'payload_resposta' => $dados,
                ];

                if (!empty($dados['identificationField'])) {
                    $updateData['linha_digitavel'] = $dados['identificationField'];
                }
                if (!empty($dados['barCode'])) {
                    $updateData['codigo_barras'] = $dados['barCode'];
                }
                if (!empty($dados['pixQrCode'])) {
                    $updateData['pix_qr_code_base64'] = $dados['pixQrCode'];
                }
                if (!empty($dados['pixCopiaCola'])) {
                    $updateData['pix_copia_cola'] = $dados['pixCopiaCola'];
                }

                $titulo->cobrancaGateway->update($updateData);

                if (in_array($statusExterno, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH']) && !$titulo->isLiquidado()) {
                    $baixaService->realizarBaixa($titulo, [
                        'valor_pago' => (float) ($dados['value'] ?? $titulo->valor_saldo),
                        'forma_pagamento' => ($dados['billingType'] ?? 'BOLETO') === 'PIX' ? 'pix' : 'boleto',
                        'origem_baixa' => 'sincronizacao_gateway',
                        'data_pagamento' => $dados['paymentDate'] ?? date('Y-m-d'),
                        'observacoes' => "Baixa confirmada via sincronização automática com Asaas (ID {$chargeId})"
                    ]);
                    $baixados++;
                }

                // Respeita rate limit do Asaas (máx 100 req/min)
                usleep(100000); // 100ms
            } catch (\Throwable $e) {
                $erros++;
                Log::error("Erro ao sincronizar cobrança {$chargeId} do título #{$titulo->id}: " . $e->getMessage());
                $this->error("Falha ao sincronizar título #{$titulo->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sincronização concluída: {$processados} processados, {$baixados} baixados, {$erros} falhas.");
        return 0;
    }
}
