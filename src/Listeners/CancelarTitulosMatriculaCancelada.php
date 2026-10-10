<?php

namespace iEducar\Financeiro\Listeners;

use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Services\ContratoService;
use Illuminate\Support\Facades\Log;

class CancelarTitulosMatriculaCancelada
{
    /**
     * @var ContratoService
     */
    protected $contratoService;

    public function __construct(ContratoService $contratoService)
    {
        $this->contratoService = $contratoService;
    }

    /**
     * Trata o cancelamento de matrícula: cancela apenas parcelas futuras não vencidas
     *
     * @param mixed $event
     * @return void
     */
    public function handle($event)
    {
        try {
            $matriculaId = is_object($event) ? ($event->matriculaId ?? $event->cod_matricula ?? null) : ($event['matriculaId'] ?? null);
            $motivo = is_object($event) ? ($event->motivo ?? 'Cancelamento de matrícula no i-Educar') : 'Cancelamento de matrícula';

            if (!$matriculaId) {
                return;
            }

            $contrato = Contrato::where('matricula_id', $matriculaId)
                ->where('status', 'ativo')
                ->first();

            if ($contrato) {
                $this->contratoService->cancelarContrato($contrato, $motivo);
            }
        } catch (\Throwable $e) {
            Log::warning('Erro ao processar cancelamento financeiro de matrícula: ' . $e->getMessage());
        }
    }
}
