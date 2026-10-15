<?php

namespace iEducar\Financeiro\Jobs;

use iEducar\Financeiro\Gateways\GatewayManager;
use iEducar\Financeiro\Models\LoteProcessamento;
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

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    protected array $tituloIds;
    protected ?string $competencia;
    protected ?int $escolaId;
    protected ?int $usuarioId;

    public function __construct(
        $param1 = [],
        ?string $param2 = null,
        ?int $param3 = null,
        ?int $param4 = null
    ) {
        // Compatibilidade polimórfica com chamadas legadas e novas:
        // dispatch('asaas', $competencia, $escolaId) ou dispatch($tituloIds, $competencia, $escolaId, $usuarioId)
        if (is_string($param1)) {
            $this->tituloIds   = [];
            $this->competencia = $param2;
            $this->escolaId    = $param3;
            $this->usuarioId   = $param4;
        } else {
            $this->tituloIds   = (array) $param1;
            $this->competencia = $param2;
            $this->escolaId    = $param3;
            $this->usuarioId   = $param4;
        }
    }

    /**
     * Executa a emissão de cobranças digitais no Asaas em background (BUG-002)
     */
    public function handle()
    {
        /** @var \iEducar\Financeiro\Gateways\Drivers\AsaasGateway $gateway */
        $gateway = app(GatewayManager::class)->driver('asaas');

        $lote = null;
        if (class_exists(LoteProcessamento::class)) {
            try {
                $lote = LoteProcessamento::create([
                    'tipo'        => 'emissao_asaas',
                    'parametros'  => [
                        'titulo_ids'  => $this->tituloIds,
                        'competencia' => $this->competencia,
                        'escola_id'   => $this->escolaId,
                    ],
                    'status'      => 'processando',
                    'usuario_id'  => $this->usuarioId,
                    'iniciado_em' => now(),
                ]);
            } catch (\Throwable $e) {}
        }

        $query = Titulo::with(['responsavel', 'cobrancaGateway'])
            ->whereIn('status', ['aberto', 'parcial', 'vencido'])
            ->where('valor_saldo', '>=', 0.005);

        if (!empty($this->tituloIds)) {
            $query->whereIn('id', $this->tituloIds);
        } elseif (!empty($this->competencia)) {
            $query->where('competencia', $this->competencia);
            if ($this->escolaId) {
                $query->where('escola_id', $this->escolaId);
            }
        }

        $total = 0;
        $sucessos = 0;
        $erros = 0;
        $ignorados = 0;
        $detalhesErros = [];

        $query->chunkById(50, function ($titulos) use ($gateway, &$total, &$sucessos, &$erros, &$ignorados, &$detalhesErros) {
            foreach ($titulos as $titulo) {
                $total++;

                // Ignora se já tem cobrança ativa
                if ($titulo->cobrancaGateway && !empty($titulo->cobrancaGateway->gateway_charge_id) && $titulo->cobrancaGateway->ativa) {
                    $ignorados++;
                    continue;
                }

                // Ignora se o responsável não possui CPF/CNPJ
                if (!$titulo->responsavel || empty($titulo->responsavel->cpf_cnpj)) {
                    $erros++;
                    $detalhesErros[] = [
                        'titulo_id' => $titulo->id,
                        'motivo'    => 'Responsável sem CPF/CNPJ cadastrado.',
                    ];
                    continue;
                }

                try {
                    $gateway->criarCobranca($titulo);
                    $sucessos++;
                } catch (\Throwable $e) {
                    $erros++;
                    $detalhesErros[] = [
                        'titulo_id' => $titulo->id,
                        'motivo'    => $e->getMessage(),
                    ];
                    Log::error("Erro no lote ao emitir cobrança Asaas para título #{$titulo->id}: " . $e->getMessage());
                }
            }
        });

        if ($lote) {
            try {
                $lote->update([
                    'total'         => $total,
                    'sucesso'       => $sucessos,
                    'falha'         => $erros,
                    'ignorados'     => $ignorados,
                    'status'        => 'concluido',
                    'finalizado_em' => now(),
                    'erros'         => $detalhesErros,
                ]);
            } catch (\Throwable $e) {}
        }

        Log::info(sprintf(
            'Lote de cobranças Asaas concluído: %d total, %d sucessos, %d falhas, %d ignorados.',
            $total,
            $sucessos,
            $erros,
            $ignorados
        ));
    }
}
