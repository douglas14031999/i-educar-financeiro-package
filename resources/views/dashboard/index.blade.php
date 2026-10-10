@extends('financeiro::layouts.app')

@section('title', 'Painel Geral Financeiro')
@section('breadcrumb', 'Painel Geral')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="dashboard-container">
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Painel Geral de Gestão Financeira
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Indicadores operacionais, fluxo de recebimentos e controle de cobrança escolar
                </p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
                <a href="{{ route('financeiro.baixas.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm hover:shadow transition duration-150" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Nova Baixa de Balcão
                </a>
                <a href="{{ route('financeiro.titulos.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-800 active:bg-slate-900 text-white text-sm font-medium shadow-sm transition duration-150" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4 text-slate-300" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Ver Todas as Mensalidades
                </a>
            </div>
        </header>

        <!-- Indicadores Financeiros -->
        <section aria-label="Indicadores Financeiros" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" data-purpose="kpi-grid">
            <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
                <div class="absolute top-0 inset-x-0 h-1 bg-emerald-500"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Receita Recebida no Mês</span>
                    <span class="p-1 bg-emerald-50 text-emerald-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-slate-900">R$ {{ number_format($kpis['recebido_mes'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Liquidações via Asaas, Pix e Caixa</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
                <div class="absolute top-0 inset-x-0 h-1 bg-cyan-600"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">A Receber no Mês Atual</span>
                    <span class="p-1 bg-cyan-50 text-cyan-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-slate-900">R$ {{ number_format($kpis['a_receber_mes'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Parcelas vincendas neste período</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
                <div class="absolute top-0 inset-x-0 h-1 bg-rose-500"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Total em Atraso</span>
                    <span class="p-1 bg-rose-50 text-rose-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-slate-900">R$ {{ number_format($kpis['total_inadimplente'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;"><strong class="font-medium text-slate-700">{{ $kpis['titulos_vencidos_count'] ?? 0 }}</strong> parcelas aguardando acerto</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
                <div class="absolute top-0 inset-x-0 h-1 bg-amber-500"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Taxa de Inadimplência</span>
                    <span class="p-1 bg-amber-50 text-amber-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($kpis['taxa_inadimplencia'] ?? 0, 1, ',', '.') }}%</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Em relação à receita faturada</p>
                </div>
            </article>
        </section>

        <!-- Tabela -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-container">
            <div class="bg-gradient-to-r from-[#214b5f] to-[#1a3d4e] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">Próximos Vencimentos e Cobranças Recentes</h2>
                </div>
                <a class="text-xs font-medium text-cyan-100 hover:text-white transition flex items-center gap-1" href="{{ route('financeiro.titulos.index') }}" style="text-decoration: none;">
                    Histórico completo <span aria-hidden="true">»</span>
                </a>
            </div>
            
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex p-0.5 bg-slate-200/80 text-xs font-medium text-slate-600">
                    <button class="px-3 py-1 bg-white text-slate-800 shadow-xs" type="button">Recentes</button>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('financeiro.planos.create') }}" class="text-xs font-medium inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-2xs" style="text-decoration: none;">
                        <svg class="w-3.5 h-3.5 text-emerald-600" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        Novo Plano de Pagamento
                    </a>
                    <a href="{{ route('financeiro.responsaveis.create') }}" class="text-xs font-medium inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-2xs" style="text-decoration: none;">
                        <svg class="w-3.5 h-3.5 text-slate-600" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        Cadastrar Responsável
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                    <thead>
                        <tr class="bg-slate-100/90 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4" scope="col">Vencimento</th>
                            <th class="py-3 px-4" scope="col">Aluno</th>
                            <th class="py-3 px-4" scope="col">Responsável Financeiro</th>
                            <th class="py-3 px-4" scope="col">Turma / Série</th>
                            <th class="py-3 px-4 text-center" scope="col">Parcela</th>
                            <th class="py-3 px-4 text-right" scope="col">Valor</th>
                            <th class="py-3 px-4 text-center" scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($titulosRecentes ?? [] as $titulo)
                            @php
                                $isAtrasado = ($titulo->status === 'vencido' || $titulo->isVencido());
                                $isPago = $titulo->status === 'pago';
                                $bgClass = $isAtrasado ? 'hover:bg-rose-50/40 bg-rose-50/20' : 'hover:bg-slate-50/80';
                            @endphp
                            <tr class="{{ $bgClass }} transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap font-medium {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                                    {{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}
                                    @if($isAtrasado)
                                        <span class="block text-[10px] text-rose-500 font-semibold">{{ \Carbon\Carbon::parse($titulo->data_vencimento)->diffInDays() }} dias atrasado</span>
                                    @elseif($isPago)
                                        <span class="block text-[10px] text-slate-400">Liquidado</span>
                                    @else
                                        <span class="block text-[10px] text-slate-400">Em {{ \Carbon\Carbon::parse($titulo->data_vencimento)->diffInDays() }} dias</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ $titulo->aluno->nome ?? 'Aluno ' . $titulo->aluno_id }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $titulo->responsavel->nome ?? 'Não informado' }}</td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700">
                                        {{ $titulo->turma_nome ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap font-medium">{{ $titulo->numero_parcela }}/{{ $titulo->total_parcelas }}</td>
                                <td class="py-3 px-4 text-right font-bold {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">R$ {{ number_format($titulo->valor_saldo ?? $titulo->valor_nominal, 2, ',', '.') }}</td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($isPago)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Liquidado</span>
                                    @elseif($isAtrasado)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Em Atraso</span>
                                    @elseif($titulo->status === 'cancelado')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">Cancelado</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200/70">Pendente</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                    Não há cobranças recentes geradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <footer class="p-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                <span>Mostrando as cobranças mais recentes com movimentação operacional</span>
            </footer>
        </section>

        <!-- Paineis Inferiores -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6" data-purpose="info-panels">
            <article class="bg-white border border-slate-200 shadow-xs p-5 flex flex-col justify-between" data-purpose="automation-card">
                <div>
                    <div class="flex items-center gap-2.5 text-brand-petroleum mb-3 pb-2 border-b border-slate-100">
                        <span class="p-1.5 bg-blue-50 text-blue-700">
                            <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        </span>
                        <h3 class="font-bold text-slate-900 text-sm md:text-base" style="margin: 0;">Régua de Cobrança Automática</h3>
                    </div>
                    <p class="text-xs md:text-sm text-slate-600 leading-relaxed mb-4" style="margin-top: 0;">
                        Disparo diário de lembretes e notificações de vencimento com links diretos de Boleto e Pix para os responsáveis por E-mail e WhatsApp.
                    </p>
                    <div class="flex items-center gap-2 text-xs text-emerald-700 font-medium bg-emerald-50/80 px-3 py-2 mb-4 border border-emerald-100">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Integração ativa: Lembretes automáticos enviados às 08h00.
                    </div>
                </div>
                <div>
                    <a class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-petroleum hover:text-brand-petroleum-dark group transition" href="{{ route('financeiro.configuracoes.gateway') }}" style="text-decoration: none;">
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        Configurações do Gateway Asaas e Chaves de Integração »
                    </a>
                </div>
            </article>

            <article class="bg-white border border-slate-200 shadow-xs p-5 flex flex-col justify-between" data-purpose="reports-card">
                <div>
                    <div class="flex items-center gap-2.5 text-brand-petroleum mb-3 pb-2 border-b border-slate-100">
                        <span class="p-1.5 bg-amber-50 text-amber-700">
                            <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        </span>
                        <h3 class="font-bold text-slate-900 text-sm md:text-base" style="margin: 0;">Relatórios Gerenciais</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3" style="margin-top: 0;">
                        Emissão de relatórios e certidões oficiais conforme legislação educacional:
                    </p>
                    <ul class="space-y-2 text-xs md:text-sm" style="list-style: none; padding-left: 0;">
                        <li>
                            <a class="flex items-center gap-2 text-brand-petroleum hover:text-cyan-700 transition font-medium group" href="{{ route('financeiro.relatorios.inadimplencia') }}" style="text-decoration: none;">
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                Relatório de Inadimplência
                            </a>
                        </li>
                        <li>
                            <a class="flex items-center gap-2 text-brand-petroleum hover:text-cyan-700 transition font-medium group" href="{{ route('financeiro.relatorios.informe-irpf') }}" style="text-decoration: none;">
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                Demonstrativo de Pagamentos para IRPF
                            </a>
                        </li>
                        <li>
                            <a class="flex items-center gap-2 text-brand-petroleum hover:text-cyan-700 transition font-medium group" href="{{ route('financeiro.relatorios.quitacao-anual') }}" style="text-decoration: none;">
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                Declaração de Quitação Anual de Débitos
                            </a>
                        </li>
                    </ul>
                </div>
            </article>
        </section>
    </main>
</div>
@endsection
