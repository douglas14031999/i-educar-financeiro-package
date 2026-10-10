<?php

namespace iEducar\Financeiro\Listeners;

use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\PlanoPagamento;
use iEducar\Financeiro\Services\ContratoService;
use Illuminate\Support\Facades\Log;

class CriarContratoAposMatricula
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
     * Trata o evento de criação de matrícula do i-Educar
     *
     * @param mixed $event
     * @return void
     */
    public function handle($event)
    {
        try {
            $matriculaId = is_object($event) ? ($event->matriculaId ?? $event->cod_matricula ?? null) : ($event['matriculaId'] ?? null);
            $alunoId = is_object($event) ? ($event->alunoId ?? $event->ref_cod_aluno ?? null) : ($event['alunoId'] ?? null);
            $anoLetivo = is_object($event) ? ($event->ano ?? date('Y')) : date('Y');
            $escolaId = is_object($event) ? ($event->escolaId ?? 1) : 1;

            if (!$matriculaId || !$alunoId) {
                return;
            }

            // Verifica se já existe contrato para esta matrícula
            if (Contrato::where('matricula_id', $matriculaId)->exists()) {
                return;
            }

            // Busca plano padrão para o ano/escola se disponível
            $plano = PlanoPagamento::where('ano_letivo', $anoLetivo)
                ->where('escola_id', $escolaId)
                ->where('ativo', true)
                ->first();

            if ($plano) {
                $this->contratoService->criarContratoEParcelas([
                    'matricula_id' => $matriculaId,
                    'aluno_id' => $alunoId,
                    'ano_letivo' => $anoLetivo,
                    'escola_id' => $escolaId,
                    'plano_id' => $plano->id,
                    'dia_vencimento' => config('financeiro.politicas.dia_vencimento_padrao', 10),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Erro ao processar contrato automático após matrícula: ' . $e->getMessage());
        }
    }
}
