@extends('financeiro::layouts.app')

@section('title', 'Processamentos e Pendências Operacionais')
@section('breadcrumb', 'Processamentos')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="processamentos-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Processamentos e Pendências',
            'subtitle' => 'Monitoramento centralizado de lotes assíncronos, conciliações do Asaas e resolução de pendências de webhooks',
            'breadcrumbs' => [
                ['label' => 'Financeiro', 'url' => route('financeiro.dashboard')],
                ['label' => 'Processamentos']
            ],
            'actions' => '
                <a href="' . route('financeiro.dashboard') . '" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar ao Painel
                </a>
            '
        ])

        <!-- Indicadores de Status -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 border border-slate-200 border-l-4 border-l-[#47728f] shadow-xs">
                <span class="text-xs font-semibold uppercase text-slate-500 tracking-wider">Lotes em Execução</span>
                <div class="text-2xl font-bold text-slate-800 mt-1">{{ $counts['lotes_ativos'] }}</div>
                <span class="text-[11px] text-slate-400">Processamento em segundo plano</span>
            </div>

            <div class="bg-white p-4 border border-slate-200 border-l-4 border-l-rose-600 shadow-xs">
                <span class="text-xs font-semibold uppercase text-slate-500 tracking-wider">Lotes com Falhas</span>
                <div class="text-2xl font-bold text-rose-600 mt-1">{{ $counts['lotes_falhos'] }}</div>
                <span class="text-[11px] text-slate-400">Requerem reinicialização</span>
            </div>

            <div class="bg-white p-4 border border-slate-200 border-l-4 border-l-amber-500 shadow-xs">
                <span class="text-xs font-semibold uppercase text-slate-500 tracking-wider">Webhooks Requerem Ação</span>
                <div class="text-2xl font-bold text-amber-600 mt-1">{{ $counts['webhooks_requer_acao'] }}</div>
                <span class="text-[11px] text-slate-400">Pagamento com valor divergente</span>
            </div>

            <div class="bg-white p-4 border border-slate-200 border-l-4 border-l-emerald-600 shadow-xs">
                <span class="text-xs font-semibold uppercase text-slate-500 tracking-wider">Webhooks com Erro</span>
                <div class="text-2xl font-bold text-slate-800 mt-1">{{ $counts['webhooks_erros'] }}</div>
                <span class="text-[11px] text-slate-400">Pendentes de reprocessamento</span>
            </div>
        </div>

        <!-- Seção 1: Webhooks Pendentes e Divergências -->
        <section class="bg-white border border-slate-200 shadow-xs overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide m-0 text-white">Pendências e Divergências de Webhooks (Asaas)</h2>
                </div>
                <span class="text-xs text-slate-300">{{ $webhooksPendentes->total() }} registros</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm m-0">
                    <thead>
                        <tr class="bg-slate-50 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-2.5 px-4">ID / Evento</th>
                            <th class="py-2.5 px-4">Tipo</th>
                            <th class="py-2.5 px-4">Motivo / Erro</th>
                            <th class="py-2.5 px-4">Recebido Em</th>
                            <th class="py-2.5 px-4 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($webhooksPendentes as $wh)
                            <tr class="hover:bg-slate-50">
                                <td class="py-2.5 px-4 font-mono font-bold text-slate-900">
                                    #{{ $wh->id }} <span class="text-[10px] text-slate-500 font-normal">({{ $wh->event_id }})</span>
                                </td>
                                <td class="py-2.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ $wh->event_type }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-rose-700 font-medium">
                                    {{ $wh->motivo_acao ?: ($wh->erro ?: 'Requer validação manual do operador') }}
                                </td>
                                <td class="py-2.5 px-4 text-slate-500">
                                    {{ \Carbon\Carbon::parse($wh->created_at)->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <form method="post" action="{{ route('financeiro.processamentos.webhooks.reprocessar', $wh->id) }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded shadow-xs transition">
                                                Reprocessar
                                            </button>
                                        </form>

                                        <form method="post" action="{{ route('financeiro.processamentos.webhooks.ignorar', $wh->id) }}" onsubmit="return confirm('Deseja realmente ignorar esta pendência?');">
                                            @csrf
                                            <input type="hidden" name="motivo" value="Ignorado manualmente pelo operador via painel de pendências">
                                            <button type="submit" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded transition">
                                                Ignorar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-500 italic">
                                    Nenhuma pendência ou divergência de webhook registrada no momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($webhooksPendentes->hasPages())
                <div class="p-3 bg-slate-50 border-t border-slate-200">
                    {{ $webhooksPendentes->links() }}
                </div>
            @endif
        </section>

        <!-- Seção 2: Lotes de Processamento Assíncrono -->
        <section class="bg-white border border-slate-200 shadow-xs overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide m-0 text-white">Lotes de Emissão e Processamentos em Massa</h2>
                </div>
                <span class="text-xs text-slate-300">{{ $lotes->total() }} lotes registrados</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm m-0">
                    <thead>
                        <tr class="bg-slate-50 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-2.5 px-4">Lote ID</th>
                            <th class="py-2.5 px-4">Tipo</th>
                            <th class="py-2.5 px-4">Status</th>
                            <th class="py-2.5 px-4 text-center">Progresso</th>
                            <th class="py-2.5 px-4">Iniciado Em</th>
                            <th class="py-2.5 px-4 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($lotes as $lote)
                            <tr class="hover:bg-slate-50">
                                <td class="py-2.5 px-4 font-mono font-bold text-slate-900">#{{ $lote->id }}</td>
                                <td class="py-2.5 px-4">{{ ucfirst(str_replace('_', ' ', $lote->tipo ?? 'Emissão em Lote')) }}</td>
                                <td class="py-2.5 px-4">
                                    @if($lote->status === 'concluido')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Concluído
                                        </span>
                                    @elseif($lote->status === 'processando')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            Processando...
                                        </span>
                                    @elseif($lote->status === 'falha')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            Falha
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300">
                                            {{ ucfirst($lote->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-center font-mono">
                                    {{ $lote->processados ?? 0 }} / {{ $lote->total_registros ?? 0 }}
                                    @if(($lote->falhas ?? 0) > 0)
                                        <span class="text-rose-600 font-bold ml-1">({{ $lote->falhas }} erros)</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-slate-500">
                                    {{ \Carbon\Carbon::parse($lote->created_at)->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    @if(in_array($lote->status, ['falha', 'concluido_com_erros']))
                                        <form method="post" action="{{ route('financeiro.processamentos.lotes.reprocessar', $lote->id) }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded shadow-xs transition">
                                                Reiniciar Lote
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-500 italic">
                                    Nenhum lote registrado recentemente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lotes->hasPages())
                <div class="p-3 bg-slate-50 border-t border-slate-200">
                    {{ $lotes->links() }}
                </div>
            @endif
        </section>

    </main>
</div>
@endsection
