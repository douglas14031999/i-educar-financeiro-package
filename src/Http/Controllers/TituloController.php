<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Models\Titulo;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TituloController extends Controller
{
    /**
     * Listagem de Títulos / Mensalidades
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $competencia = $request->input('competencia');
        $busca = $request->input('busca');

        $query = Titulo::with(['responsavel', 'cobrancaGateway']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($competencia) {
            $query->where('competencia', $competencia);
        }

        if ($busca) {
            $query->where(function ($q) use ($busca) {
                $q->whereHas('responsavel', function ($rq) use ($busca) {
                    $rq->where('nome', 'ilike', "%{$busca}%")
                       ->orWhere('cpf_cnpj', 'like', "%" . preg_replace('/\D/', '', $busca) . "%");
                })->orWhere('observacoes', 'ilike', "%{$busca}%");
            });
        }

        $titulos = $query->orderBy('data_vencimento')->paginate(20);

        return view('financeiro::titulos.index', compact('titulos', 'status', 'competencia', 'busca'));
    }

    /**
     * Detalhes de um Título
     */
    public function show($id)
    {
        $titulo = Titulo::with(['contrato', 'responsavel', 'pagamentos', 'cobrancaGateway'])->findOrFail($id);

        return view('financeiro::titulos.show', compact('titulo'));
    }

    /**
     * Emissão e Visualização do Recibo Oficial para Impressão
     */
    public function imprimirRecibo($id)
    {
        $titulo = Titulo::with(['contrato', 'responsavel', 'pagamentos'])->findOrFail($id);

        $ultimoPagamento = $titulo->pagamentos()->where('estornado', false)->first();

        return view('financeiro::titulos.recibo', compact('titulo', 'ultimoPagamento'));
    }

    /**
     * Emite cobrança individual no Asaas (Boleto híbrido + Pix)
     */
    public function emitirAsaas($id)
    {
        $titulo = Titulo::with(['responsavel'])->findOrFail($id);

        if ($titulo->isLiquidado()) {
            return redirect()->back()->with('error', 'Este título já se encontra liquidado.');
        }

        try {
            /** @var \iEducar\Financeiro\Gateways\GatewayManager $manager */
            $manager = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
            $dados = $manager->driver('asaas')->criarCobranca($titulo);

            return redirect()->route('financeiro.titulos.show', $titulo->id)
                ->with('success', 'Cobrança Asaas (Boleto/Pix) gerada com sucesso! Linha digitável e Pix disponíveis.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erro ao emitir cobrança no Asaas: ' . $e->getMessage());
        }
    }

    /**
     * Dispara emissão em lote de títulos para uma competência específica
     */
    public function emitirLoteAsaas(Request $request)
    {
        $request->validate([
            'competencia' => 'required|string',
            'escola_id' => 'nullable|integer',
        ]);

        $competencia = $request->input('competencia');
        $escolaId = $request->input('escola_id');

        \iEducar\Financeiro\Jobs\GerarCobrancasGatewayLoteJob::dispatch('asaas', $competencia, $escolaId);

        return redirect()->back()->with('success', "Processamento em lote agendado para a competência {$competencia}. Os boletos e Pix estão sendo gerados em segundo plano.");
    }

    /**
     * Sincroniza o status do título consultando diretamente o Asaas
     */
    public function sincronizarStatus($id)
    {
        $titulo = Titulo::with(['cobrancaGateway'])->findOrFail($id);

        if (!$titulo->cobrancaGateway || !$titulo->cobrancaGateway->gateway_charge_id) {
            return redirect()->back()->with('error', 'Este título não possui registro ativo de cobrança no Asaas.');
        }

        try {
            /** @var \iEducar\Financeiro\Gateways\GatewayManager $manager */
            $manager = app(\iEducar\Financeiro\Gateways\GatewayManager::class);
            $dados = $manager->driver('asaas')->consultarCobranca($titulo->cobrancaGateway->gateway_charge_id);

            if (!empty($dados)) {
                $statusExterno = $dados['status'] ?? null;
                $titulo->cobrancaGateway->update([
                    'status_externo' => $statusExterno,
                    'payload_resposta' => $dados,
                ]);

                // Se no gateway já consta como recebido mas local ainda não foi baixado
                if (in_array($statusExterno, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH']) && !$titulo->isLiquidado()) {
                    /** @var \iEducar\Financeiro\Services\BaixaTituloService $baixaService */
                    $baixaService = app(\iEducar\Financeiro\Services\BaixaTituloService::class);
                    $baixaService->baixar($titulo, [
                        'valor_pago' => (float) ($dados['value'] ?? $titulo->valor_saldo),
                        'forma_pagamento' => ($dados['billingType'] ?? 'BOLETO') === 'PIX' ? 'pix' : 'boleto',
                        'origem_baixa' => 'sincronizacao_gateway',
                        'data_pagamento' => $dados['paymentDate'] ?? date('Y-m-d'),
                        'observacoes' => "Baixa confirmada via sincronização manual com Asaas (ID {$titulo->cobrancaGateway->gateway_charge_id})"
                    ]);
                }

                return redirect()->back()->with('success', "Status sincronizado com sucesso: {$statusExterno}.");
            }

            return redirect()->back()->with('warning', 'Nenhum dado retornado pelo Asaas.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Erro na sincronização com Asaas: ' . $e->getMessage());
        }
    }

    /**
     * Prepara link de cobrança para disparo via WhatsApp pela secretaria
     */
    public function notificarWhatsApp($id)
    {
        $titulo = Titulo::with(['responsavel', 'cobrancaGateway'])->findOrFail($id);
        $telefone = preg_replace('/\D/', '', $titulo->responsavel->celular ?? $titulo->responsavel->telefone ?? '');

        if (empty($telefone)) {
            return redirect()->back()->with('error', 'O responsável financeiro não possui celular cadastrado.');
        }

        // Adiciona DDI 55 se não tiver
        if (strlen($telefone) === 10 || strlen($telefone) === 11) {
            $telefone = '55' . $telefone;
        }

        $linkBoleto = $titulo->cobrancaGateway->link_pagamento ?? $titulo->cobrancaGateway->link_boleto_pdf ?? url("/financeiro/titulos/{$titulo->id}");
        $vencimento = \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y');
        $valor = number_format($titulo->valor_saldo, 2, ',', '.');

        $mensagem = "Olá, {$titulo->responsavel->nome}! Informamos a mensalidade escolar referente a {$titulo->competencia} (Vencimento: {$vencimento}) no valor de R$ {$valor}. Acesse a 2ª via ou pague com Pix pelo link: {$linkBoleto}";

        $url = 'https://api.whatsapp.com/send?phone=' . $telefone . '&text=' . urlencode($mensagem);

        return redirect()->away($url);
    }
}

