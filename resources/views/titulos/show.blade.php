@extends('financeiro::layouts.app')

@section('title', 'Detalhes da Mensalidade #' . $titulo->id)
@section('breadcrumb', 'Título #' . $titulo->id)
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="titulo-details-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                            Título #{{ $titulo->id }} &bull; Parcela {{ $titulo->numero_parcela == 0 ? 'Matrícula' : $titulo->numero_parcela . '/' . $titulo->total_parcelas }}
                        </h1>
                    </div>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Competência <strong>{{ $titulo->competencia }}</strong> &bull; Vencimento em <strong>{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</strong>
                </p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                @if(in_array($titulo->status, ['aberto', 'parcial', 'vencido']))
                    <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm transition" style="text-decoration: none;">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Baixar no Caixa
                    </a>
                @endif
                @if($titulo->status === 'pago')
                    <a href="{{ route('financeiro.titulos.recibo-pdf', $titulo->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-sm font-medium shadow-sm transition" style="text-decoration: none;">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Imprimir Recibo
                    </a>
                @endif
                <a href="{{ route('financeiro.titulos.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium border border-slate-300 shadow-2xs transition" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </header>

        <!-- Cards de Resumo Financeiro -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" data-purpose="summary-cards">
            <article class="bg-white shadow-xs border border-slate-200 p-4 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 inset-x-0 h-1 bg-brand-petroleum"></div>
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1">Valor Nominal</span>
                <div>
                    <span class="text-2xl font-bold text-slate-900">R$ {{ number_format($titulo->valor_nominal, 2, ',', '.') }}</span>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Valor base da parcela</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200 p-4 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 inset-x-0 h-1 bg-emerald-500"></div>
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1">Total Já Pago</span>
                <div>
                    <span class="text-2xl font-bold text-emerald-700">R$ {{ number_format($titulo->valor_pago, 2, ',', '.') }}</span>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Amortizado no caixa/gateway</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200 p-4 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 inset-x-0 h-1 {{ $titulo->valor_saldo > 0 ? ($titulo->isVencido() ? 'bg-rose-500' : 'bg-cyan-600') : 'bg-emerald-600' }}"></div>
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1">Saldo Devedor Atual</span>
                <div>
                    <span class="text-2xl font-bold {{ $titulo->valor_saldo > 0 ? ($titulo->isVencido() ? 'text-rose-700' : 'text-cyan-700') : 'text-slate-900' }}">
                        R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}
                    </span>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">
                        {{ $titulo->valor_saldo <= 0 ? 'Título quitado integralmente' : 'Aguardando liquidação' }}
                    </p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200 p-4 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 inset-x-0 h-1 {{ $titulo->status === 'pago' ? 'bg-emerald-500' : ($titulo->isVencido() ? 'bg-rose-500' : 'bg-amber-500') }}"></div>
                <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1">Situação Contábil</span>
                <div>
                    <div class="flex items-center gap-2">
                        @if($titulo->status === 'pago')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Liquidado
                            </span>
                        @elseif($titulo->isVencido())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                Vencido ({{ $titulo->dias_atraso }}d)
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                {{ ucfirst($titulo->status) }}
                            </span>
                        @endif
                    </div>
                    @if($titulo->isVencido())
                        <p class="text-xs text-rose-600 mt-1 font-semibold" style="margin: 4px 0 0 0;">
                            Corrigido hoje: R$ {{ number_format($titulo->valor_atualizado, 2, ',', '.') }}
                        </p>
                    @else
                        <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Regularidade cadastral</p>
                    @endif
                </div>
            </article>
        </section>

        <!-- Especificações e Vínculos -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="details-card">
            <div class="bg-gradient-to-r from-[#214b5f] to-[#1a3d4e] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Dados do Responsável Financeiro e Aluno
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    Matrícula e Vínculo Escolar
                </span>
            </div>

            <div class="p-5 md:p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-xs md:text-sm">
                <div class="space-y-3">
                    <div>
                        <span class="text-slate-500 font-bold uppercase block text-[11px]">Responsável Financeiro:</span>
                        <strong class="text-slate-900 text-sm md:text-base">{{ $titulo->responsavel->nome ?? '-' }}</strong>
                        <span class="text-slate-600 block mt-0.5 font-mono">CPF/CNPJ: {{ $titulo->responsavel->cpf_cnpj_formatado ?? '-' }}</span>
                        <span class="text-slate-500 block text-xs">E-mail: {{ $titulo->responsavel->email ?? '-' }} &bull; Tel: {{ $titulo->responsavel->telefone_celular ?? '-' }}</span>
                    </div>

                    @if($titulo->observacoes)
                        <div class="pt-2 border-t border-slate-100">
                            <span class="text-slate-500 font-bold uppercase block text-[11px]">Observações:</span>
                            <p class="text-slate-700 italic mt-0.5" style="margin: 0;">{{ $titulo->observacoes }}</p>
                        </div>
                    @endif
                </div>

                <div class="space-y-3">
                    <div>
                        <span class="text-slate-500 font-bold uppercase block text-[11px]">Aluno(a) Matriculado(a):</span>
                        <strong class="text-slate-900 text-sm md:text-base">{{ $titulo->aluno_nome }}</strong>
                        <span class="text-slate-600 block mt-0.5">Turma Ativa: <strong>{{ $titulo->turma_nome }}</strong></span>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-slate-500 font-bold uppercase block text-[11px]">Contrato Financeiro:</span>
                        <span class="text-slate-800">
                            @if($titulo->contrato_id)
                                <a href="{{ route('financeiro.contratos.show', $titulo->contrato_id) }}" class="text-brand-petroleum font-semibold hover:underline">
                                    Contrato #{{ $titulo->contrato_id }} &raquo;
                                </a>
                            @else
                                Título gerado avulso / sem contrato vinculado
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Gateway Asaas: Boleto Híbrido e Pix -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="gateway-card">
            <div class="bg-gradient-to-r from-[#214b5f] to-[#1a3d4e] px-5 py-3 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Cobrança Digital Integrada (Boleto Bancário Híbrido & Pix Dinâmico)
                    </h2>
                </div>
                @if($titulo->cobrancaGateway)
                    <div class="flex items-center gap-2">
                        <form method="post" action="{{ route('financeiro.titulos.sincronizar-asaas', $titulo->id) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 hover:bg-white/20 text-white text-xs font-medium rounded transition">
                                <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                Sincronizar Gateway
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="p-5 md:p-6">
                @if($titulo->cobrancaGateway)
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-100">
                            <div>
                                <span class="text-xs text-slate-500 font-semibold uppercase block">Identificador no Asaas:</span>
                                <code class="text-xs md:text-sm font-bold text-slate-800">{{ $titulo->cobrancaGateway->gateway_charge_id }}</code>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($titulo->cobrancaGateway->link_boleto_pdf)
                                    <a href="{{ $titulo->cobrancaGateway->link_boleto_pdf }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-brand-petroleum hover:bg-[#153442] text-white text-xs font-semibold shadow-xs transition" style="text-decoration: none;">
                                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                        Visualizar Boleto PDF
                                    </a>
                                @endif
                                @if($titulo->cobrancaGateway->link_pagamento)
                                    <a href="{{ $titulo->cobrancaGateway->link_pagamento }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold border border-slate-300 transition" style="text-decoration: none;">
                                        Fatura Online / Cartão &raquo;
                                    </a>
                                @endif
                                <a href="{{ route('financeiro.titulos.notificar-whatsapp', $titulo->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition" style="text-decoration: none;">
                                    Enviar WhatsApp
                                </a>
                            </div>
                        </div>

                        <!-- Linha Digitável e Pix Copia e Cola -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                            @if($titulo->cobrancaGateway->pix_qr_code_base64)
                                <div class="md:col-span-3 text-center">
                                    <img src="data:image/png;base64,{{ $titulo->cobrancaGateway->pix_qr_code_base64 }}" width="140" height="140" class="mx-auto border border-slate-300 p-1 bg-white shadow-xs" alt="QR Code Pix">
                                    <span class="block text-[11px] text-slate-500 font-semibold mt-1">QR Code Pix Instantâneo</span>
                                </div>
                            @endif

                            <div class="{{ $titulo->cobrancaGateway->pix_qr_code_base64 ? 'md:col-span-9' : 'md:col-span-12' }} space-y-4">
                                @if($titulo->cobrancaGateway->linha_digitavel)
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Linha Digitável do Boleto:</label>
                                        <div class="flex gap-2">
                                            <input type="text" readonly value="{{ $titulo->cobrancaGateway->linha_digitavel }}" id="input-linha-dig" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 font-mono text-xs text-slate-800 focus:outline-none">
                                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('input-linha-dig').value); alert('Linha digitável copiada!');" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-semibold whitespace-nowrap">
                                                Copiar
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                @if($titulo->cobrancaGateway->pix_copia_cola)
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Código Pix Copia e Cola:</label>
                                        <div class="flex gap-2">
                                            <input type="text" readonly value="{{ $titulo->cobrancaGateway->pix_copia_cola }}" id="input-pix" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 font-mono text-xs text-slate-800 focus:outline-none">
                                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('input-pix').value); alert('Código Pix copiado!');" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold whitespace-nowrap">
                                                Copiar Pix
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-8 text-center space-y-3 bg-slate-50 border border-slate-200">
                        <svg class="w-10 h-10 mx-auto text-slate-400" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        <p class="text-xs md:text-sm text-slate-600" style="margin: 0;">
                            Este título ainda não foi registrado no Gateway de Pagamentos Asaas.
                        </p>
                        @if(!$titulo->isLiquidado())
                            <form method="post" action="{{ route('financeiro.titulos.emitir-asaas', $titulo->id) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs md:text-sm font-semibold shadow-sm transition">
                                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                    Emitir Cobrança no Asaas Agora (Boleto & Pix)
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        <!-- Histórico de Recebimentos / Baixas -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="history-payments">
            <div class="bg-gradient-to-r from-[#214b5f] to-[#1a3d4e] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Histórico de Pagamentos e Baixas de Caixa ({{ $titulo->pagamentos->count() }})
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    Livro Caixa Auditado
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                    <thead>
                        <tr class="bg-slate-100 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4" scope="col">Data Pagamento</th>
                            <th class="py-3 px-4" scope="col">Forma</th>
                            <th class="py-3 px-4" scope="col">Origem</th>
                            <th class="py-3 px-4 text-right" scope="col">Valor Pago</th>
                            <th class="py-3 px-4" scope="col">Hash / Comprovante</th>
                            <th class="py-3 px-4 text-center" scope="col">Status</th>
                            <th class="py-3 px-4 text-center" scope="col">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($titulo->pagamentos as $pag)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap font-medium text-slate-900">
                                    {{ \Carbon\Carbon::parse($pag->data_pagamento)->format('d/m/Y') }}
                                </td>
                                <td class="py-3 px-4 capitalize">
                                    {{ str_replace('_', ' ', $pag->forma_pagamento) }}
                                </td>
                                <td class="py-3 px-4 capitalize">
                                    {{ $pag->origem_baixa }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-700 whitespace-nowrap">
                                    R$ {{ number_format($pag->valor_pago, 2, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-500">
                                    {{ substr($pag->comprovante_hash, 0, 16) }}...
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($pag->estornado)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            Estornado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Liquidado
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if(!$pag->estornado)
                                        <form method="post" action="{{ route('financeiro.baixas.estornar', $pag->id) }}" onsubmit="return confirm('Deseja realmente estornar este recebimento? O saldo do título será recalculado.');">
                                            @csrf
                                            <input type="hidden" name="motivo_estorno" value="Estorno administrativo presencial">
                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-rose-700 hover:text-white hover:bg-rose-600 border border-rose-300 rounded transition">
                                                Estornar
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400 italic">{{ $pag->motivo_estorno }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 italic">
                                    Nenhum pagamento liquidado registrado para este título.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</div>
@endsection
