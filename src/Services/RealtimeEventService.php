<?php

namespace iEducar\Financeiro\Services;

use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RealtimeEventService
{
    private const CACHE_PREFIX = 'financeiro:realtime:';
    private const RECENT_EVENTS_KEY = 'financeiro:realtime:events:list';
    private const TTL_MINUTES = 3;

    /**
     * Dispara evento de pagamento confirmado para consumo via SSE.
     */
    public static function dispararPagamentoConfirmado(Titulo $titulo, ?Pagamento $pagamento = null, string $origem = 'webhook'): array
    {
        $titulo->refresh();
        $responsavelNome = $titulo->responsavelFinanceiro?->nome;
        if (! $responsavelNome && $titulo->responsavel_financeiro_id) {
            $titulo->loadMissing('responsavelFinanceiro');
            $responsavelNome = $titulo->responsavelFinanceiro?->nome;
        }

        $valorNominal = (float) $titulo->valor_nominal;
        $valorPago = $pagamento ? (float) $pagamento->valor_pago : (float) ($valorNominal - (float) $titulo->valor_saldo);
        if ($valorPago <= 0) {
            $valorPago = $valorNominal;
        }

        $nowMs = (int) (microtime(true) * 1000);

        $payload = [
            'id' => (string) Str::uuid(),
            'tipo' => 'PAGAMENTO_CONFIRMADO',
            'origem' => $origem,
            'timestamp' => now()->toIso8601String(),
            'timestamp_ms' => $nowMs,
            'titulo' => [
                'id' => (int) $titulo->id,
                'numero_parcela' => (int) $titulo->numero_parcela,
                'total_parcelas' => (int) $titulo->total_parcelas,
                'valor_nominal' => $valorNominal,
                'valor_nominal_formatado' => 'R$ ' . number_format($valorNominal, 2, ',', '.'),
                'saldo' => (float) $titulo->valor_saldo,
                'saldo_formatado' => 'R$ ' . number_format((float) $titulo->valor_saldo, 2, ',', '.'),
                'status' => (string) $titulo->status,
                'status_formatado' => strtoupper((string) $titulo->status),
                'descricao' => (string) ($titulo->observacoes ?: "Parcela {$titulo->numero_parcela}/{$titulo->total_parcelas}"),
                'competencia' => (string) ($titulo->competencia ?: now()->format('m/Y')),
                'aluno_id' => (int) $titulo->aluno_id,
                'responsavel_nome' => (string) ($responsavelNome ?: 'Responsável Financeiro'),
            ],
            'pagamento' => [
                'id' => $pagamento ? (int) $pagamento->id : null,
                'valor_pago' => $valorPago,
                'valor_pago_formatado' => 'R$ ' . number_format($valorPago, 2, ',', '.'),
                'data_pagamento' => $pagamento && $pagamento->data_pagamento
                    ? $pagamento->data_pagamento->format('d/m/Y')
                    : now()->format('d/m/Y'),
                'forma_pagamento' => $pagamento ? (string) $pagamento->forma_pagamento : 'PIX/Boleto',
            ],
            'contrato_id' => $titulo->contrato_id ? (int) $titulo->contrato_id : null,
            'contrato_status' => $titulo->contrato?->status,
        ];

        // 1. Armazenar evento específico do título
        Cache::put(self::CACHE_PREFIX . "titulo:{$titulo->id}", $payload, now()->addMinutes(self::TTL_MINUTES));

        // 1.1 Se vinculado a contrato, armazenar evento específico do contrato
        if ($titulo->contrato_id) {
            Cache::put(self::CACHE_PREFIX . "contrato:{$titulo->contrato_id}", $payload, now()->addMinutes(self::TTL_MINUTES));
        }

        // 2. Adicionar na lista de eventos recentes para telas gerais (Dashboard, Listagem de Títulos)
        $eventos = Cache::get(self::RECENT_EVENTS_KEY, []);
        if (! is_array($eventos)) {
            $eventos = [];
        }

        // Manter apenas eventos dos últimos 3 minutos e no máximo 30 itens
        $corteMs = $nowMs - (self::TTL_MINUTES * 60 * 1000);
        $eventos = array_filter($eventos, fn ($e) => is_array($e) && isset($e['timestamp_ms']) && $e['timestamp_ms'] >= $corteMs);

        // Prepend novo evento
        array_unshift($eventos, $payload);
        $eventos = array_slice($eventos, 0, 30);

        Cache::put(self::RECENT_EVENTS_KEY, $eventos, now()->addMinutes(self::TTL_MINUTES));

        return $payload;
    }

    /**
     * Busca novos eventos ocorridos após determinado timestamp em milissegundos.
     *
     * @param int $sinceTimestampMs Timestamp em ms da última verificação
     * @param int|null $tituloId Filtro opcional por ID de título específico
     * @param int|null $contratoId Filtro opcional por ID de contrato específico
     * @return array Lista de eventos ocorridos após o timestamp
     */
    public static function obterNovosEventos(int $sinceTimestampMs, ?int $tituloId = null, ?int $contratoId = null): array
    {
        if ($tituloId) {
            $eventoTitulo = Cache::get(self::CACHE_PREFIX . "titulo:{$tituloId}");
            if (is_array($eventoTitulo) && isset($eventoTitulo['timestamp_ms']) && $eventoTitulo['timestamp_ms'] > $sinceTimestampMs) {
                return [$eventoTitulo];
            }
            return [];
        }

        if ($contratoId) {
            $eventoContrato = Cache::get(self::CACHE_PREFIX . "contrato:{$contratoId}");
            if (is_array($eventoContrato) && isset($eventoContrato['timestamp_ms']) && $eventoContrato['timestamp_ms'] > $sinceTimestampMs) {
                return [$eventoContrato];
            }
            return [];
        }

        $eventos = Cache::get(self::RECENT_EVENTS_KEY, []);
        if (! is_array($eventos)) {
            return [];
        }

        $novos = [];
        foreach ($eventos as $evento) {
            if (is_array($evento) && isset($evento['timestamp_ms']) && $evento['timestamp_ms'] > $sinceTimestampMs) {
                $novos[] = $evento;
            }
        }

        // Ordenar por ordem cronológica crescente para entrega suave
        usort($novos, fn ($a, $b) => ($a['timestamp_ms'] ?? 0) <=> ($b['timestamp_ms'] ?? 0));

        return $novos;
    }
}
