@extends('financeiro::layouts.app')

@section('title', 'Régua de Cobrança Escolar')
@section('breadcrumb', 'Configurações > Régua de Cobrança')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="regua-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Régua de Cobrança Automática
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Automação preventiva e reativa de lembretes e avisos de vencimento por WhatsApp, E-mail e SMS
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <form method="post" action="{{ route('financeiro.configuracoes.regua.executar-manual') }}" onsubmit="return confirm('Deseja executar a varredura da régua de cobrança agora? Os títulos elegíveis serão notificados.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Processar Régua Agora
                    </button>
                </form>
                <a href="{{ route('financeiro.dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </header>

        <!-- Variáveis Dinâmicas Disponíveis -->
        <article class="bg-cyan-50/70 border border-cyan-200/90 p-4 space-y-2" data-purpose="tags-card">
            <div class="flex items-center gap-2 text-brand-petroleum">
                <svg class="w-4 h-4 text-cyan-700" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
                <span class="text-xs md:text-sm font-bold">Variáveis disponíveis para templates de mensagens:</span>
            </div>
            <div class="flex flex-wrap gap-1.5 text-xs">
                <code class="px-2 py-1 bg-white border border-cyan-200 text-slate-800 font-mono rounded cursor-pointer" onclick="navigator.clipboard.writeText('{responsavel}'); alert('Tag copiada!');" title="Clique para copiar">{responsavel}</code>
                <code class="px-2 py-1 bg-white border border-cyan-200 text-slate-800 font-mono rounded cursor-pointer" onclick="navigator.clipboard.writeText('{aluno}'); alert('Tag copiada!');" title="Clique para copiar">{aluno}</code>
                <code class="px-2 py-1 bg-white border border-cyan-200 text-slate-800 font-mono rounded cursor-pointer" onclick="navigator.clipboard.writeText('{competencia}'); alert('Tag copiada!');" title="Clique para copiar">{competencia}</code>
                <code class="px-2 py-1 bg-white border border-cyan-200 text-slate-800 font-mono rounded cursor-pointer" onclick="navigator.clipboard.writeText('{vencimento}'); alert('Tag copiada!');" title="Clique para copiar">{vencimento}</code>
                <code class="px-2 py-1 bg-white border border-cyan-200 text-slate-800 font-mono rounded cursor-pointer" onclick="navigator.clipboard.writeText('{valor}'); alert('Tag copiada!');" title="Clique para copiar">{valor}</code>
                <code class="px-2 py-1 bg-white border border-cyan-200 text-slate-800 font-mono rounded cursor-pointer" onclick="navigator.clipboard.writeText('{link_pagamento}'); alert('Tag copiada!');" title="Clique para copiar">{link_pagamento}</code>
                <code class="px-2 py-1 bg-white border border-cyan-200 text-slate-800 font-mono rounded cursor-pointer" onclick="navigator.clipboard.writeText('{linha_digitavel}'); alert('Tag copiada!');" title="Clique para copiar">{linha_digitavel}</code>
            </div>
        </article>

        <!-- Regras Configuráveis -->
        <section class="space-y-4" data-purpose="regras-list">
            @foreach($regras as $regra)
                @php
                    $isPre = ($regra->dias_gatilho < 0);
                    $isD0 = ($regra->dias_gatilho == 0);
                    $isPos = ($regra->dias_gatilho > 0);
                    
                    if ($isPre) {
                        $badgeBg = 'bg-cyan-100 text-cyan-800 border-cyan-300';
                        $gatilhoTexto = 'Pré-Vencimento (' . abs($regra->dias_gatilho) . ' dias antes)';
                    } elseif ($isD0) {
                        $badgeBg = 'bg-amber-100 text-amber-800 border-amber-300';
                        $gatilhoTexto = 'No Dia do Vencimento (D0)';
                    } else {
                        $badgeBg = 'bg-rose-100 text-rose-800 border-rose-300';
                        $gatilhoTexto = 'Pós-Vencimento (' . $regra->dias_gatilho . ' dias de atraso)';
                    }
                @endphp
                <article class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="regra-item">
                    <form method="post" action="{{ route('financeiro.configuracoes.regua.atualizar', $regra->id) }}">
                        @csrf
                        <header class="bg-[#47728f] px-5 py-3 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold border {{ $badgeBg }}">
                                    {{ $gatilhoTexto }}
                                </span>
                                <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                                    {{ $regra->nome_regra }}
                                </h2>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center gap-2 text-xs font-medium cursor-pointer text-white">
                                    <input type="checkbox" name="ativo" value="1" {{ $regra->ativo ? 'checked' : '' }} class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300 rounded">
                                    <span>Regra Ativa na Rotina</span>
                                </label>
                            </div>
                        </header>

                        <div class="p-5 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-8">
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                        Nome Identificador da Regra:
                                    </label>
                                    <input type="text" name="nome_regra" value="{{ $regra->nome_regra }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum" required>
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                        Canal de Comunicação:
                                    </label>
                                    <select name="canal" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum">
                                        <option value="whatsapp" {{ $regra->canal === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                        <option value="email" {{ $regra->canal === 'email' ? 'selected' : '' }}>E-mail</option>
                                        <option value="sms" {{ $regra->canal === 'sms' ? 'selected' : '' }}>SMS</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                    Template da Mensagem Notificada:
                                </label>
                                <textarea name="corpo_template" rows="3" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 font-mono text-xs focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum" required>{{ $regra->corpo_template }}</textarea>
                            </div>

                            <div class="flex justify-end pt-2 border-t border-slate-100">
                                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue">
                                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                    </svg>
                                    Atualizar Regra
                                </button>
                            </div>
                        </div>
                    </form>
                </article>
            @endforeach
        </section>

        <!-- Histórico Recente de Disparos -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="history-table">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Últimos Disparos Registrados pela Régua
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    Histórico Operacional Auditado
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                    <thead>
                        <tr class="bg-slate-100 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4" scope="col">Data / Hora</th>
                            <th class="py-3 px-4" scope="col">Regra Disparada</th>
                            <th class="py-3 px-4" scope="col">Responsável / Destinatário</th>
                            <th class="py-3 px-4 text-center" scope="col">Canal</th>
                            <th class="py-3 px-4 text-center" scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($ultimosEnvios as $envio)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap font-medium text-slate-900">
                                    {{ \Carbon\Carbon::parse($envio->enviado_em)->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-800">
                                    {{ $envio->regra->nome_regra ?? 'Geral' }}
                                </td>
                                <td class="py-3 px-4">
                                    <strong class="text-slate-900">{{ $envio->responsavel->nome ?? 'Responsável' }}</strong>
                                    <span class="block text-xs text-slate-500 font-mono">{{ $envio->destinatario }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 uppercase">
                                        {{ $envio->canal }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($envio->sucesso)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Enviado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $envio->erro }}">
                                            Falha
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 italic">
                                    Nenhum disparo registrado até o momento.
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
