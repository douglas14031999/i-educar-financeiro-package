<?php

namespace iEducar\Financeiro\Http\Controllers;

use iEducar\Financeiro\Services\RealtimeEventService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RealtimeStreamController extends Controller
{
    /**
     * Endpoint Server-Sent Events (SSE) para atualização em tempo real de pagamentos.
     */
    public function stream(Request $request): StreamedResponse
    {
        $tituloId = $request->query('titulo_id') ? (int) $request->query('titulo_id') : null;
        $sinceParam = $request->query('since') ? (int) $request->query('since') : 0;

        // Se o cliente não enviou 'since', inicializa a partir do momento atual menos 5 segundos
        // para não perder eventos disparados na transição de página
        $since = $sinceParam > 0 ? $sinceParam : (int) ((microtime(true) - 5) * 1000);

        // Crucial no PHP: fechar a sessão para não reter locks FPM de outras requisições
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Desativa limites de tempo do script para streaming controlado
        @set_time_limit(35);
        @ini_set('max_execution_time', '35');

        $response = new StreamedResponse(function () use ($tituloId, $since) {
            $lastSince = $since;
            $start = time();
            $maxDuration = 25; // 25 segundos por conexão para reciclagem saudável dos workers

            // Envia cabeçalho inicial de boas-vindas / ping
            echo "retry: 2000\n"; // Orientar reconexão em 2s
            echo ": connected\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            while ((time() - $start) < $maxDuration) {
                if (connection_aborted()) {
                    break;
                }

                $eventos = RealtimeEventService::obterNovosEventos($lastSince, $tituloId);

                if (! empty($eventos)) {
                    foreach ($eventos as $evento) {
                        $id = $evento['id'] ?? uniqid();
                        $tipo = $evento['tipo'] ?? 'message';
                        $lastSince = max($lastSince, (int) ($evento['timestamp_ms'] ?? 0));

                        echo "id: {$id}\n";
                        echo "event: {$tipo}\n";
                        echo "data: " . json_encode($evento, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                    }

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                } else {
                    // Ping periódico a cada 5s para manter proxy/nginx vivo
                    echo ": ping\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                // Pausa curta de 1.5s entre checagens
                usleep(1500000);
            }

            // Encerramento limpo para o EventSource reconectar
            echo ": end\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // Garante bypass de buffer no Nginx

        return $response;
    }
}
