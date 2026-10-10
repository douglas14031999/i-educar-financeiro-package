@extends('financeiro::layouts.app')

@section('title', 'Detalhes do Plano - ' . $plano->nome)
@section('breadcrumb', 'Detalhes do Plano')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="planos-show-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                            {{ $plano->nome }}
                        </h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Ano {{ $plano->ano_letivo }}
                        </span>
                    </div>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Escola #{{ $plano->escola_id }} &bull; {{ $plano->qtd_parcelas }} parcelas mensais &bull; Total R$ {{ number_format($plano->valor_total, 2, ',', '.') }}
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <form method="post" action="{{ route('financeiro.planos.destroy', $plano->id) }}" onsubmit="return confirm('Deseja realmente excluir este plano de cobrança?');" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-medium border border-red-200 transition">
                        <svg class="w-4 h-4 text-red-600" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Excluir Plano
                    </button>
                </form>
                <a href="{{ route('financeiro.planos.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium border border-slate-300 shadow-2xs transition" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </header>

        <!-- Especificações do Plano -->
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Especificações da Cobrança
                    </h2>
                </div>
                <span class="text-xs text-slate-300 font-mono">
                    ID #{{ $plano->id }}
                </span>
            </div>

            <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Ano Letivo</div>
                    <div class="font-mono font-bold text-slate-900 mt-1 text-base">
                        {{ $plano->ano_letivo }}
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">Escola ID #{{ $plano->escola_id }}</div>
                </div>

                <div class="p-3 bg-emerald-50/60 border border-emerald-200">
                    <div class="text-[11px] font-semibold text-emerald-800 uppercase tracking-wider">Valor Total da Anuidade</div>
                    <div class="font-mono font-bold text-emerald-700 mt-1 text-lg">
                        R$ {{ number_format($plano->valor_total, 2, ',', '.') }}
                    </div>
                    <div class="text-[11px] text-emerald-600 mt-0.5">Valor global anual</div>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Parcelamento</div>
                    <div class="font-mono font-bold text-slate-900 mt-1 text-base">
                        {{ $plano->qtd_parcelas }}x parcelas
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        Média de R$ {{ number_format($plano->qtd_parcelas > 0 ? $plano->valor_total / $plano->qtd_parcelas : 0, 2, ',', '.') }} / mês
                    </div>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Vencimento Padrão</div>
                    <div class="font-mono font-bold text-slate-900 mt-1 text-base">
                        Dia {{ sprintf('%02d', $plano->dia_vencimento_padrao ?? 10) }}
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">Sugestão de cobrança</div>
                </div>
            </div>

            @if($plano->descricao)
                <div class="px-5 pb-5">
                    <div class="p-3 bg-blue-50/50 border border-blue-200 text-slate-700 text-xs">
                        <span class="font-semibold text-brand-petroleum">Descrição:</span> {{ $plano->descricao }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Grade de Parcelas e Itens Gerados -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="itens-section">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Grade de Parcelas Geradas ({{ $plano->itens->count() }})
                    </h2>
                </div>
                <div class="text-xs text-slate-300">
                    Itens e competências que serão gerados ao vincular este plano a uma matrícula
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4 text-center">Parcela</th>
                            <th class="py-3 px-4">Descrição do Item</th>
                            <th class="py-3 px-4 text-center">Tipo</th>
                            <th class="py-3 px-4 text-center">Competência</th>
                            <th class="py-3 px-4 text-center">Vencimento Padrão</th>
                            <th class="py-3 px-4 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($plano->itens as $item)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-800">
                                    {{ $item->numero_parcela == 0 ? 'Matrícula' : sprintf('%02d', $item->numero_parcela) }}
                                </td>
                                <td class="py-3.5 px-4 font-medium text-slate-900">
                                    {{ $item->descricao }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($item->tipo_item === 'matricula')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                            Matrícula
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                            Mensalidade
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono text-slate-600">
                                    Mês {{ sprintf('%02d', $item->mes_competencia) }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono text-slate-600">
                                    Dia {{ sprintf('%02d', $item->dia_vencimento_padrao) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    R$ {{ number_format($item->valor, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    Nenhuma parcela configurada para este plano de cobrança.
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
