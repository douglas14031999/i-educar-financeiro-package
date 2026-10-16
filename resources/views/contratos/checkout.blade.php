@extends('financeiro::layouts.app')

@section('title', 'Checkout e Fechamento do Contrato #' . $contrato->numero_contrato)
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="contrato-checkout-container">

        {{-- Cabeçalho Padrão i-Educar --}}
        @include('financeiro::partials.page-header', [
            'title' => 'Checkout e Fechamento Financeiro da Matrícula',
            'subtitle' => 'Contrato ' . $contrato->numero_contrato . ' — Aluno #' . $contrato->aluno_id . ' (' . $contrato->ano_letivo . ')',
            'breadcrumbs' => [
                ['label' => 'Contratos', 'url' => route('financeiro.contratos.index')],
                ['label' => $contrato->numero_contrato, 'url' => route('financeiro.contratos.show', $contrato->id)],
                ['label' => 'Checkout de Pagamento']
            ],
            'actions' => '
                <a href="' . route('financeiro.contratos.show', $contrato->id) . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Ver Detalhes do Contrato
                </a>
            '
        ])

        {{-- Painel Principal do Checkout --}}
        <div class="max-w-4xl mx-auto space-y-6">

            {{-- Resumo da Matrícula / Contrato --}}
            <div class="bg-white border border-slate-200 p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="block text-xs font-bold uppercase tracking-wider text-slate-500">Contrato Formalizado</span>
                    <h2 class="text-lg font-bold text-slate-900 m-0">{{ $contrato->numero_contrato }}</h2>
                    <span class="text-xs text-slate-600">
                        Responsável: <strong>{{ $contrato->responsavel->nome ?? 'Não identificado' }}</strong>
                        @if($contrato->responsavel && $contrato->responsavel->cpf_cnpj)
                            (CPF: {{ $contrato->responsavel->cpf_cnpj }})
                        @endif
                    </span>
                </div>
                <div class="text-left sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-100">
                    <span class="block text-xs font-bold uppercase tracking-wider text-slate-500">Valor Total Líquido</span>
                    <span class="text-2xl font-extrabold text-slate-900">R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}</span>
                    <span class="block text-xs text-slate-500">Ano Letivo {{ $contrato->ano_letivo }}</span>
                </div>
            </div>

            {{-- ========================================================================= --}}
            {{-- CENÁRIO 1: QUITADO (DINHEIRO OU CARTÃO PRESENCIAL POS)                    --}}
            {{-- ========================================================================= --}}
            @if($contrato->status === 'quitado')
                <div class="bg-white border-2 border-emerald-500 p-6 md:p-8 shadow-xs text-center space-y-4">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"></path>
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-xl font-extrabold text-slate-900 m-0">Contrato Quitado com Sucesso!</h3>
                        <p class="text-sm text-slate-600 mt-1">
                            @if($contrato->forma_pagamento === 'cartao_presencial')
                                Baixa confirmada via <strong>Cartão Presencial (POS)</strong>
                                @if($contrato->cartao_bandeira) - {{ $contrato->cartao_bandeira }} @endif
                                @if($contrato->cartao_nsu) (NSU: {{ $contrato->cartao_nsu }}) @endif
                            @else
                                Baixa confirmada no <strong>Caixa Escolar (Dinheiro à Vista)</strong>.
                            @endif
                        </p>
                    </div>

                    <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
                        @if($primeiroTitulo)
                            <a href="{{ route('financeiro.titulos.recibo-pdf', $primeiroTitulo->id) }}" target="_blank" class="inline-flex items-center gap-2 px-6 py-2.5 btn-ieducar-action btn-ieducar-green text-sm font-semibold shadow-xs" style="text-decoration:none;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Imprimir Recibo Oficial
                            </a>
                        @endif
                        <a href="{{ route('financeiro.contratos.show', $contrato->id) }}" class="inline-flex items-center gap-2 px-5 py-2.5 btn-ieducar-action btn-ieducar-outline text-sm" style="text-decoration:none;">
                            Ver Contrato e Títulos
                        </a>
                    </div>
                </div>

            {{-- ========================================================================= --}}
            {{-- CENÁRIO 2: PIX INSTANTÂNEO (QR CODE DINÂMICO 24H)                         --}}
            {{-- ========================================================================= --}}
            @elseif($contrato->forma_pagamento === 'pix')
                <div class="bg-white border border-slate-200 p-6 md:p-8 shadow-xs space-y-6">
                    <div class="text-center space-y-1">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-sky-50 text-sky-800 border border-sky-200 text-xs font-bold uppercase tracking-wider">
                            ⚡ Pagamento Instantâneo via PIX
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 m-0">Aguardando Pagamento do Responsável</h3>
                        <p class="text-xs text-slate-500">Validade deste QR Code: 24 horas. Esta tela atualizará automaticamente após a confirmação.</p>
                    </div>

                    <div class="flex flex-col md:flex-row items-center justify-center gap-8 py-2">
                        {{-- QR Code Image --}}
                        <div class="p-3 bg-white border border-slate-300 shadow-2xs text-center">
                            @if($cobrancaAtiva && $cobrancaAtiva->qr_code_base64)
                                <img src="data:image/png;base64,{{ $cobrancaAtiva->qr_code_base64 }}" alt="QR Code Pix" class="w-48 h-48 mx-auto">
                            @elseif($cobrancaAtiva && $cobrancaAtiva->pix_qr_code_base64)
                                <img src="data:image/png;base64,{{ $cobrancaAtiva->pix_qr_code_base64 }}" alt="QR Code Pix" class="w-48 h-48 mx-auto">
                            @else
                                <div class="w-48 h-48 bg-slate-100 flex flex-col items-center justify-center text-slate-400 text-xs p-4">
                                    <span>QR Code gerado no Asaas</span>
                                    <span class="text-[10px] mt-1 text-slate-500">Consulte no botão abaixo</span>
                                </div>
                            @endif
                            <span class="block text-[11px] text-slate-500 mt-2 font-medium">Aponte a câmera do aplicativo do seu banco</span>
                        </div>

                        {{-- Instruções e Chave Copia e Cola --}}
                        <div class="space-y-4 max-w-sm w-full">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Pix Copia e Cola:</label>
                                <div class="relative">
                                    <input type="text" id="pix_copia_cola" readonly value="{{ $cobrancaAtiva->pix_copia_cola ?? 'Pix em processamento no gateway...' }}" class="w-full text-xs font-mono p-2.5 bg-slate-50 border border-slate-300 select-all pr-20 text-slate-700">
                                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('pix_copia_cola').value); alert('Chave Pix copiada com sucesso!');" class="absolute right-1.5 top-1.5 px-2.5 py-1 bg-[#47728f] text-white text-[11px] font-semibold hover:bg-[#385b73]">
                                        Copiar
                                    </button>
                                </div>
                            </div>

                            {{-- Ações WhatsApp --}}
                            @php
                                $telefoneResp = preg_replace('/\D/', '', $contrato->responsavel->telefone_celular ?? '');
                                $msgWhatsapp = urlencode("Olá " . ($contrato->responsavel->nome ?? '') . "! Segue o Pix para pagamento da matrícula do aluno no Contrato " . $contrato->numero_contrato . " no valor de R$ " . number_format($contrato->valor_total_liquido, 2, ',', '.') . ":\n\n" . ($cobrancaAtiva->pix_copia_cola ?? ''));
                            @endphp
                            @if($telefoneResp)
                                <a href="https://wa.me/55{{ $telefoneResp }}?text={{ $msgWhatsapp }}" target="_blank" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-none shadow-xs" style="text-decoration:none;">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                    Enviar Chave Pix via WhatsApp
                                </a>
                            @endif

                            {{-- Status Pulsante --}}
                            <div class="flex items-center gap-2 text-xs text-slate-500 pt-2" id="status_realtime_pix">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-sky-500"></span>
                                </span>
                                <span>Monitorando confirmação bancária em tempo real...</span>
                            </div>
                        </div>
                    </div>
                </div>

            {{-- ========================================================================= --}}
            {{-- CENÁRIO 3: CARTÃO ONLINE (LINK DE PAGAMENTO ASAAS ATÉ 12X)                 --}}
            {{-- ========================================================================= --}}
            @elseif($contrato->forma_pagamento === 'cartao_credito')
                <div class="bg-white border border-slate-200 p-6 md:p-8 shadow-xs space-y-6">
                    <div class="text-center space-y-1">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold uppercase tracking-wider">
                            🌐 Link de Pagamento no Cartão de Crédito
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 m-0">Link Asaas Disponível em até 12x</h3>
                        <p class="text-xs text-slate-500">Os juros são repassados ao responsável no checkout. Validade: 5 dias úteis.</p>
                    </div>

                    @if($contrato->payment_link_url)
                        <div class="p-4 bg-slate-50 border border-slate-200 space-y-3 max-w-lg mx-auto">
                            <label class="block text-xs font-bold uppercase text-slate-700">Link Direto do Checkout:</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="link_cartao_url" readonly value="{{ $contrato->payment_link_url }}" class="w-full text-xs p-2 bg-white border border-slate-300 text-slate-800 font-mono select-all">
                                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('link_cartao_url').value); alert('Link copiado!');" class="px-3 py-2 bg-[#47728f] text-white text-xs font-semibold shrink-0">
                                    Copiar
                                </button>
                            </div>

                            <div class="pt-2 flex flex-col sm:flex-row gap-2">
                                <a href="{{ $contrato->payment_link_url }}" target="_blank" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-[#47728f] hover:bg-[#355972] text-white font-semibold text-xs shadow-xs" style="text-decoration:none;">
                                    Abrir Checkout Asaas ↗
                                </a>

                                @php
                                    $telefoneResp = preg_replace('/\D/', '', $contrato->responsavel->telefone_celular ?? '');
                                    $msgLinkWhatsapp = urlencode("Olá " . ($contrato->responsavel->nome ?? '') . "! Segue o link para pagamento no cartão de crédito em até 12x da matrícula no Contrato " . $contrato->numero_contrato . ":\n\n" . $contrato->payment_link_url);
                                @endphp
                                @if($telefoneResp)
                                    <a href="https://wa.me/55{{ $telefoneResp }}?text={{ $msgLinkWhatsapp }}" target="_blank" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs" style="text-decoration:none;">
                                        Enviar WhatsApp
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-xs text-amber-700 bg-amber-50 border border-amber-200 p-4">
                            O link de pagamento ainda está sendo processado pelo Asaas. Atualize a página em instantes.
                        </div>
                    @endif
                </div>

            {{-- ========================================================================= --}}
            {{-- CENÁRIO 4: BOLETO 1X OU CARNÊ ESCOLAR                                     --}}
            {{-- ========================================================================= --}}
            @else
                <div class="bg-white border border-slate-200 p-6 md:p-8 shadow-xs text-center space-y-4">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-800 border border-blue-200 text-xs font-bold uppercase tracking-wider">
                        @if($contrato->forma_pagamento === 'carne') 📚 Carnê Escolar Asaas @else 📄 Boleto Bancário @endif
                    </span>

                    <h3 class="text-lg font-bold text-slate-900 m-0">
                        @if($contrato->forma_pagamento === 'carne') Carnê Oficial Gerado com Sucesso @else Boleto Emitido no Gateway @endif
                    </h3>
                    <p class="text-xs text-slate-500 max-w-md mx-auto">
                        O contrato está ativo e as cobranças bancárias foram registradas no gateway Asaas.
                    </p>

                    <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
                        @if($contrato->forma_pagamento === 'carne')
                            <a href="{{ route('financeiro.contratos.imprimir-carne', $contrato->id) }}" target="_blank" class="inline-flex items-center gap-2 px-6 py-2.5 btn-ieducar-action btn-ieducar-green text-sm font-semibold shadow-xs" style="text-decoration:none;">
                                🖨️ Imprimir Carnê Oficial Asaas (PDF)
                            </a>
                        @elseif($primeiroTitulo && $primeiroTitulo->cobrancaGateway && $primeiroTitulo->cobrancaGateway->link_boleto_pdf)
                            <a href="{{ $primeiroTitulo->cobrancaGateway->link_boleto_pdf }}" target="_blank" class="inline-flex items-center gap-2 px-6 py-2.5 btn-ieducar-action btn-ieducar-green text-sm font-semibold shadow-xs" style="text-decoration:none;">
                                📄 Baixar Boleto Bancário (PDF)
                            </a>
                        @endif
                        <a href="{{ route('financeiro.contratos.show', $contrato->id) }}" class="inline-flex items-center gap-2 px-5 py-2.5 btn-ieducar-action btn-ieducar-outline text-sm" style="text-decoration:none;">
                            Ver Contrato Completo
                        </a>
                    </div>
                </div>
            @endif

        </div>

    </main>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($contrato->forma_pagamento === 'pix' && $contrato->status !== 'quitado')
        // Polling de verificação de status do contrato / título a cada 4 segundos
        const intervaloPolling = setInterval(function () {
            fetch("{{ route('financeiro.api.stream', ['titulo_id' => $primeiroTitulo->id ?? 0]) }}")
                .then(r => r.json())
                .then(dados => {
                    if (dados && (dados.status === 'pago' || dados.status === 'liquidado' || dados.contrato_status === 'quitado')) {
                        clearInterval(intervaloPolling);
                        window.location.reload();
                    }
                })
                .catch(() => {});
        }, 4000);
    @endif
});
</script>
@endpush
@endsection
