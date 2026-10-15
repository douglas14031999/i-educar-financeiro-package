<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Jobs\GerarCobrancasGatewayLoteJob;
use iEducar\Financeiro\Jobs\ProcessarWebhookJob;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\LoteProcessamento;
use iEducar\Financeiro\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProcessamentoController extends Controller
{
    /**
     * Painel de Acompanhamento de Processamentos e Pendências Operacionais (FEAT-05)
     */
    public function index(Request $request)
    {
        // 1. Lotes de Emissão em Massa e Processamento Assíncrono
        $lotes = LoteProcessamento::orderBy('created_at', 'desc')->paginate(15, ['*'], 'lotes_page');

        // 2. Webhooks com Erro ou Requerendo Ação Manual
        $webhooksPendentes = WebhookEvent::where(function ($q) {
                $q->where('requer_acao', true)
                  ->orWhere(function ($sq) {
                      $sq->where('processado', false)->whereNotNull('erro');
                  });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'webhooks_page');

        // 3. Indicadores rápidos para o topo do painel
        $counts = [
            'lotes_ativos' => LoteProcessamento::whereIn('status', ['pendente', 'processando'])->count(),
            'lotes_falhos' => LoteProcessamento::where('status', 'falha')->count(),
            'webhooks_requer_acao' => WebhookEvent::where('requer_acao', true)->count(),
            'webhooks_erros' => WebhookEvent::where('processado', false)->whereNotNull('erro')->count(),
        ];

        return view('financeiro::processamentos.index', compact('lotes', 'webhooksPendentes', 'counts'));
    }

    /**
     * Reprocessa um webhook específico que tenha falhado
     */
    public function reprocessarWebhook(int $id)
    {
        $webhook = WebhookEvent::findOrFail($id);

        $webhook->update([
            'processado' => false,
            'requer_acao' => false,
            'erro' => null,
            'tentativas' => 0,
        ]);

        ProcessarWebhookJob::dispatch($webhook);

        return redirect()->back()->with('success', "Webhook #{$webhook->id} colocado na fila para reprocessamento.");
    }

    /**
     * Ignora uma pendência de webhook com justificativa de auditoria
     */
    public function ignorarWebhook(Request $request, int $id)
    {
        $request->validate([
            'motivo' => 'required|string|min:5|max:255',
        ]);

        $webhook = WebhookEvent::findOrFail($id);

        $webhook->update([
            'processado' => true,
            'requer_acao' => false,
            'motivo_acao' => 'Ignorado manualmente: ' . $request->input('motivo'),
            'erro' => 'Ignorado pelo operador: ' . $request->input('motivo'),
        ]);

        return redirect()->back()->with('success', "Pendência do Webhook #{$webhook->id} foi resolvida e marcada como ignorada.");
    }

    /**
     * Tenta reprocessar um lote assíncrono que teve falha
     */
    public function reprocessarLote(int $id)
    {
        $lote = LoteProcessamento::findOrFail($id);

        if ($lote->status !== 'falha' && $lote->status !== 'concluido_com_erros') {
            return redirect()->back()->with('warning', 'Apenas lotes com falha ou inconsistências podem ser reprocessados.');
        }

        $lote->update([
            'status' => 'processando',
            'erros' => array_merge((array) $lote->erros, ['Reprocessamento iniciado em ' . now()]),
        ]);

        // Se houver títulos no payload do lote, re-despacha o job
        $payload = (array) $lote->payload;
        $tituloIds = $payload['titulo_ids'] ?? [];

        if (!empty($tituloIds)) {
            GerarCobrancasGatewayLoteJob::dispatch($tituloIds);
        }

        return redirect()->back()->with('success', "Lote #{$lote->id} foi reiniciado para processamento.");
    }
}
