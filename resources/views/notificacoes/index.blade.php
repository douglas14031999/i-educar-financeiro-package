@extends('financeiro::layouts.app')

@section('title', 'Histórico de Notificações e Mensagens')
@section('breadcrumb', 'Notificações')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="notificacoes-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Histórico de Notificações e Mensagens
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Rastreabilidade auditada de todos os disparos de cobrança e lembretes por WhatsApp, E-mail e SMS
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('financeiro.configuracoes.regua') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium border border-slate-300 shadow-2xs transition" style="text-decoration: none;">
                    <svg class="w-4 h-4 text-slate-500" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Ajustar Parâmetros da Régua
                </a>
            </div>
        </header>

        <!-- Barra de Filtros -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="filter-card">
            <div class="bg-gradient-to-r from-[#214b5f] to-[#1a3d4e] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Filtros de Auditoria e Rastreabilidade
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    {{ $envios->total() }} mensagens localizadas
                </span>
            </div>

            <div class="p-5">
                <form method="get" action="{{ route('financeiro.notificacoes.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-3">
                        <label for="canal" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Canal de Envio:
                        </label>
                        <select name="canal" id="canal" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum">
                            <option value="">Todos os Canais</option>
                            <option value="whatsapp" {{ $canal === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                            <option value="email" {{ $canal === 'email' ? 'selected' : '' }}>E-mail</option>
                            <option value="sms" {{ $canal === 'sms' ? 'selected' : '' }}>SMS</option>
                        </select>
                    </div>

                    <div class="md:col-span-6">
                        <label for="busca" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Buscar Destinatário, Responsável ou Palavra-Chave:
                        </label>
                        <input type="text" name="busca" id="busca" value="{{ $busca }}" placeholder="Ex: nome do responsável, telefone ou parte do texto..." class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum">
                    </div>

                    <div class="md:col-span-3 flex items-center gap-2">
                        <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-brand-petroleum hover:bg-[#153442] text-white text-sm font-semibold shadow-sm transition">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Filtrar
                        </button>
                        @if($busca || $canal)
                            <a href="{{ route('financeiro.notificacoes.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium transition" style="text-decoration: none;">
                                Limpar
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </section>

        <!-- Tabela de Mensagens -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                    <thead>
                        <tr class="bg-slate-100 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4" scope="col">Data / Hora</th>
                            <th class="py-3 px-4 text-center" scope="col">Canal</th>
                            <th class="py-3 px-4" scope="col">Responsável / Destinatário</th>
                            <th class="py-3 px-4" scope="col">Mensagem Disparada</th>
                            <th class="py-3 px-4 text-center" scope="col">Título</th>
                            <th class="py-3 px-4 text-center" scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($envios as $envio)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap font-medium text-slate-900">
                                    {{ \Carbon\Carbon::parse($envio->enviado_em)->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 uppercase">
                                        {{ $envio->canal }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <strong class="text-slate-900 block">{{ $envio->responsavel->nome ?? 'Responsável' }}</strong>
                                    <span class="text-xs text-slate-500 font-mono">{{ $envio->destinatario }}</span>
                                </td>
                                <td class="py-3 px-4 max-w-md">
                                    <p class="text-xs text-slate-700 leading-relaxed line-clamp-2" title="{{ $envio->mensagem_enviada }}" style="margin: 0;">
                                        {{ $envio->mensagem_enviada }}
                                    </p>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($envio->titulo_id)
                                        <a href="{{ route('financeiro.titulos.show', $envio->titulo_id) }}" class="inline-flex items-center px-2 py-0.5 text-xs font-semibold text-brand-petroleum hover:underline bg-cyan-50 border border-cyan-200">
                                            #{{ $envio->titulo_id }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($envio->sucesso)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Sucesso
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
                                <td colspan="6" class="py-10 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                    Nenhuma notificação registrada no período consultado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($envios->hasPages())
                <footer class="p-4 bg-slate-50 border-t border-slate-200">
                    {{ $envios->appends(request()->query())->links() }}
                </footer>
            @endif
        </section>

    </main>
</div>
@endsection
