<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Models\ReguaCobrancaRegra;
use iEducar\Financeiro\Models\ReguaEnvioLog;
use iEducar\Financeiro\Services\ReguaCobrancaService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReguaCobrancaController extends Controller
{
    /**
     * Listagem das regras da régua e histórico recente de envios
     */
    public function index()
    {
        $regras = ReguaCobrancaRegra::orderBy('dias_gatilho')->get();
        $ultimosEnvios = ReguaEnvioLog::with(['regra', 'titulo', 'responsavel'])
            ->orderBy('id', 'desc')
            ->limit(15)
            ->get();

        return view('financeiro::configuracoes.regua', compact('regras', 'ultimosEnvios'));
    }

    /**
     * Atualização dos templates e estado de uma regra
     */
    public function atualizar(Request $request, $id)
    {
        $regra = ReguaCobrancaRegra::findOrFail($id);

        $request->validate([
            'nome_regra' => 'required|string|max:150',
            'canal' => 'required|string|in:whatsapp,email,sms',
            'corpo_template' => 'required|string',
            'ativo' => 'nullable|boolean',
        ]);

        $regra->update([
            'nome_regra' => $request->input('nome_regra'),
            'canal' => $request->input('canal'),
            'corpo_template' => $request->input('corpo_template'),
            'ativo' => $request->boolean('ativo'),
        ]);

        return redirect()->back()->with('success', "Regra '{$regra->nome_regra}' atualizada com sucesso!");
    }

    /**
     * Disparo manual do processamento da régua de cobrança
     */
    public function executarManual(ReguaCobrancaService $service)
    {
        $resumo = $service->processar();

        return redirect()->back()->with('success', sprintf(
            'Processamento da régua executado! Notificações enviadas: %d | Duplicidades evitadas: %d.',
            $resumo['total_disparados'],
            $resumo['total_ignorados']
        ));
    }

    /**
     * Tela dedicada de auditoria de notificações enviadas aos responsáveis
     */
    public function historicoNotificacoes(Request $request)
    {
        $canal = $request->input('canal');
        $busca = $request->input('busca');

        $query = ReguaEnvioLog::with(['regra', 'titulo', 'responsavel'])
            ->orderBy('enviado_em', 'desc');

        if ($canal) {
            $query->where('canal', $canal);
        }

        if ($busca) {
            $query->where(function ($q) use ($busca) {
                $q->where('destinatario', 'ilike', "%{$busca}%")
                  ->orWhere('mensagem_enviada', 'ilike', "%{$busca}%")
                  ->orWhereHas('responsavel', function ($rq) use ($busca) {
                      $rq->where('nome', 'ilike', "%{$busca}%");
                  });
            });
        }

        $envios = $query->paginate(25);

        return view('financeiro::notificacoes.index', compact('envios', 'canal', 'busca'));
    }
}

