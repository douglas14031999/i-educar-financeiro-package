{{-- Componente de Notificações em Tempo Real (SSE) para Webhooks e Pagamentos --}}
<div id="financeiro-toast-container" class="financeiro-toast-container" aria-live="polite" aria-atomic="true"></div>

<style>
.financeiro-toast-container {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 999999;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 420px;
    width: calc(100vw - 48px);
    pointer-events: none;
}
.financeiro-toast {
    pointer-events: auto;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #cddce6;
    border-left: 5px solid #059669;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    position: relative;
    animation: toastSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    transition: all 0.3s ease;
    font-family: "Open Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.financeiro-toast.fade-out {
    opacity: 0;
    transform: translateX(30px);
}
@keyframes toastSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.financeiro-toast-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px 6px 14px;
}
.financeiro-toast-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 700;
    color: #065f46;
}
.financeiro-toast-badge {
    background: #d1fae5;
    color: #065f46;
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.financeiro-toast-close {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    transition: color 0.2s;
}
.financeiro-toast-close:hover {
    color: #334155;
    background: #f1f5f9;
}
.financeiro-toast-body {
    padding: 4px 14px 12px 14px;
    font-size: 13px;
    color: #334155;
    line-height: 1.45;
}
.financeiro-toast-info {
    margin-top: 4px;
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    font-weight: 600;
}
.financeiro-toast-valor {
    color: #059669;
    font-size: 15px;
    font-weight: 800;
}
.financeiro-toast-actions {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid #f1f5f9;
}
.financeiro-toast-btn {
    font-size: 12px;
    padding: 5px 12px;
    border-radius: 4px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.financeiro-toast-btn-primary {
    background: #47728f;
    color: #ffffff;
    border: 1px solid #375970;
}
.financeiro-toast-btn-primary:hover {
    background: #375970;
    color: #ffffff;
}
.financeiro-toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: #059669;
    width: 100%;
    animation: toastProgress 6s linear forwards;
}
@keyframes toastProgress {
    from { width: 100%; }
    to { width: 0%; }
}
/* Efeito de flash em elementos da tabela atualizados */
.realtime-flash-success {
    animation: flashSuccess 2s ease;
}
@keyframes flashSuccess {
    0% { background-color: #d1fae5; }
    50% { background-color: #a7f3d0; }
    100% { background-color: transparent; }
}
</style>

<script>
(function() {
    'use strict';

    if (window.FinanceiroRealtimeInitialized) {
        return;
    }
    window.FinanceiroRealtimeInitialized = true;

    // 1. Sintetizador de Som Harmônico Suave (Web Audio API)
    function playPaymentChime() {
        try {
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            var ctx = new AudioCtx();
            if (ctx.state === 'suspended') {
                ctx.resume();
            }
            var now = ctx.currentTime;

            // Nota 1 (C5: 523.25Hz)
            var osc1 = ctx.createOscillator();
            var gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(523.25, now);
            gain1.gain.setValueAtTime(0.12, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.35);

            // Nota 2 (E5: 659.25Hz)
            var osc2 = ctx.createOscillator();
            var gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(659.25, now + 0.12);
            gain2.gain.setValueAtTime(0.18, now + 0.12);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.55);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.12);
            osc2.stop(now + 0.55);
        } catch (e) {
            // Navegadores com autoplay bloqueado continuam silenciosamente
        }
    }

    // 2. Componente de Toast Dinâmico
    function showPaymentToast(evento) {
        var container = document.getElementById('financeiro-toast-container');
        if (!container) return;

        var t = evento.titulo || {};
        var p = evento.pagamento || {};
        var tituloId = t.id || '';
        var respNome = t.responsavel_nome || 'Responsável';
        var desc = t.descricao || ('Título #' + tituloId);
        var valorFormatado = p.valor_pago_formatado || ('R$ ' + (p.valor_pago || 0).toFixed(2));
        var hora = p.data_pagamento || new Date().toLocaleTimeString('pt-BR');

        var toast = document.createElement('div');
        toast.className = 'financeiro-toast';
        toast.innerHTML = 
            '<div class="financeiro-toast-header">' +
                '<span class="financeiro-toast-title">' +
                    '<svg width="18" height="18" viewBox="0 0 20 20" fill="#059669"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>' +
                    'Pagamento Confirmado' +
                '</span>' +
                '<span class="financeiro-toast-badge">' + (evento.origem === 'webhook_asaas' ? 'Asaas' : 'Caixa') + '</span>' +
                '<button type="button" class="financeiro-toast-close" title="Fechar">&times;</button>' +
            '</div>' +
            '<div class="financeiro-toast-body">' +
                '<div><strong>' + escapeHtml(respNome) + '</strong> &bull; ' + escapeHtml(desc) + '</div>' +
                '<div class="financeiro-toast-info">' +
                    '<span>Horário: ' + escapeHtml(hora) + '</span>' +
                    '<span class="financeiro-toast-valor">' + escapeHtml(valorFormatado) + '</span>' +
                '</div>' +
                '<div class="financeiro-toast-actions">' +
                    (tituloId ? '<a href="/financeiro/titulos/' + tituloId + '" class="financeiro-toast-btn financeiro-toast-btn-primary">Ver Título</a>' : '') +
                    (p.id ? '<a href="/financeiro/titulos/recibo/' + p.id + '" target="_blank" class="financeiro-toast-btn" style="background:#059669;color:#fff;border:1px solid #047857;">Imprimir Recibo</a>' : '') +
                '</div>' +
            '</div>' +
            '<div class="financeiro-toast-progress"></div>';

        container.appendChild(toast);

        var closeBtn = toast.querySelector('.financeiro-toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                removeToast(toast);
            });
        }

        // Auto-remover após 6 segundos
        setTimeout(function() {
            removeToast(toast);
        }, 6000);
    }

    function removeToast(toast) {
        if (!toast || toast.dataset.removed) return;
        toast.dataset.removed = 'true';
        toast.classList.add('fade-out');
        setTimeout(function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // 3. Atualização Dinâmica do DOM sem F5
    function updateDomForPayment(evento) {
        var t = evento.titulo || {};
        var p = evento.pagamento || {};
        var tituloId = t.id;
        if (!tituloId) return;

        // Dispara evento JavaScript global para quem quiser ouvir
        window.dispatchEvent(new CustomEvent('financeiro:pagamento-confirmado', { detail: evento }));

        // A) Linhas de tabelas com data-titulo-id
        var rows = document.querySelectorAll('[data-titulo-id="' + tituloId + '"], tr[id="titulo-' + tituloId + '"]');
        rows.forEach(function(row) {
            row.classList.add('realtime-flash-success');

            // Atualiza coluna/badge de status
            var badge = row.querySelector('.badge, .status-badge, [class*="badge-"]');
            if (badge) {
                badge.className = 'badge badge-pago';
                badge.textContent = 'PAGO';
            }

            // Não zera o saldo da parcela, mantém o valor nominal e atualiza o status para PAGO
            var saldoEl = row.querySelector('.col-saldo, [data-field="saldo"]');
            if (saldoEl && (t.saldo_formatado || t.valor_nominal_formatado)) {
                saldoEl.textContent = t.saldo_formatado || t.valor_nominal_formatado;
            }

            // Adiciona/atualiza botão de recibo se houver pagamento ID
            if (p.id) {
                var acoesEl = row.querySelector('.col-acoes, td:last-child');
                if (acoesEl && !acoesEl.querySelector('.btn-recibo')) {
                    var reciboLink = document.createElement('a');
                    reciboLink.href = '/financeiro/titulos/recibo/' + p.id;
                    reciboLink.target = '_blank';
                    reciboLink.className = 'btn btn-sm btn-success btn-recibo';
                    reciboLink.style.marginLeft = '4px';
                    reciboLink.innerHTML = '<i class="fa fa-print"></i> Recibo';
                    acoesEl.appendChild(reciboLink);
                }
            }
        });

        // B) Tela de Detalhes do Título (/financeiro/titulos/{id})
        var pageTituloId = document.querySelector('[data-page-titulo-id]');
        if (pageTituloId && parseInt(pageTituloId.getAttribute('data-page-titulo-id'), 10) === tituloId) {
            var detailBadge = document.querySelector('.titulo-status-badge');
            if (detailBadge) {
                detailBadge.className = 'badge badge-pago titulo-status-badge';
                detailBadge.textContent = 'PAGO';
            }
            var detailSaldo = document.querySelector('.titulo-saldo-valor');
            if (detailSaldo) {
                detailSaldo.textContent = 'R$ 0,00';
            }
            // Exibe container de recibo instantâneo
            var reciboContainer = document.getElementById('titulo-recibo-instantaneo');
            if (reciboContainer && p.id) {
                reciboContainer.innerHTML = 
                    '<div class="alert alert-success" style="margin-top:15px;display:flex;justify-content:space-between;align-items:center;">' +
                        '<span><strong>Pagamento confirmado em tempo real!</strong> Valor: ' + escapeHtml(p.valor_pago_formatado || '') + '</span>' +
                        '<a href="/financeiro/titulos/recibo/' + p.id + '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-print"></i> Imprimir Recibo Oficial</a>' +
                    '</div>';
                reciboContainer.style.display = 'block';
            }
        }

        // C) Tela de Caixa de Balcão (/financeiro/baixas/novo)
        var selectedTituloInput = document.getElementById('titulo_id');
        if (selectedTituloInput && parseInt(selectedTituloInput.value, 10) === tituloId) {
            var caixaAlert = document.getElementById('caixa-webhook-alert');
            if (!caixaAlert) {
                caixaAlert = document.createElement('div');
                caixaAlert.id = 'caixa-webhook-alert';
                caixaAlert.className = 'alert alert-success';
                caixaAlert.style.marginTop = '15px';
                selectedTituloInput.parentNode.insertBefore(caixaAlert, selectedTituloInput.nextSibling);
            }
            caixaAlert.innerHTML = 
                '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                    '<span><i class="fa fa-check-circle"></i> <strong>Pagamento confirmado via Webhook em tempo real!</strong> O título já foi liquidado.</span>' +
                    (p.id ? '<a href="/financeiro/titulos/recibo/' + p.id + '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-print"></i> Abrir Recibo</a>' : '') +
                '</div>';
            
            // Desabilita botão de submissão dupla do caixa
            var btnSalvar = document.querySelector('button[type="submit"]');
            if (btnSalvar) {
                btnSalvar.disabled = true;
                btnSalvar.textContent = 'Título já Liquidado';
            }
        }

        // D) Dashboard (/financeiro)
        var kpiRecebido = document.getElementById('kpi-total-recebido');
        if (kpiRecebido && p.valor_pago) {
            var currentText = kpiRecebido.textContent.replace('R$', '').replace(/\./g, '').replace(',', '.').trim();
            var currentVal = parseFloat(currentText) || 0;
            var newVal = currentVal + parseFloat(p.valor_pago);
            kpiRecebido.textContent = 'R$ ' + newVal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            kpiRecebido.parentElement.classList.add('realtime-flash-success');
        }
    }

    // 4. Inicializador de Conexão Server-Sent Events (SSE)
    function startRealtimeConnection() {
        if (!window.EventSource) {
            return;
        }

        var url = '{{ route("financeiro.api.stream") }}';
        
        // Verifica se a página atual tem foco em um título específico
        var pageTituloId = document.querySelector('[data-page-titulo-id]');
        if (pageTituloId) {
            var id = pageTituloId.getAttribute('data-page-titulo-id');
            if (id) {
                url += (url.indexOf('?') === -1 ? '?' : '&') + 'titulo_id=' + encodeURIComponent(id);
            }
        }

        var source = null;
        try {
            source = new EventSource(url);
        } catch (e) {
            return;
        }

        source.addEventListener('PAGAMENTO_CONFIRMADO', function(e) {
            try {
                var evento = JSON.parse(e.data);
                playPaymentChime();
                showPaymentToast(evento);
                updateDomForPayment(evento);
            } catch (err) {
                console.error('[Financeiro Realtime] Erro ao processar payload:', err);
            }
        });

        source.onerror = function() {
            // EventSource reconecta automaticamente em caso de timeout/fechamento do stream
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startRealtimeConnection);
    } else {
        startRealtimeConnection();
    }
})();
</script>
