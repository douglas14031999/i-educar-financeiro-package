@extends('financeiro::layouts.app')

@section('title', 'Planos de Cobrança')
@section('breadcrumb', 'Planos de Cobrança')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="planos-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Modelos de Planos de Pagamento e Anuidades
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Defina as regras de parcelamento, valores anuais e condições de cobrança por ano letivo
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('financeiro.planos.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm hover:shadow transition duration-150" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Novo Plano de Cobrança
                </a>
            </div>
        </header>

        <!-- Barra de Filtros -->
        <section class="bg-white shadow-xs border border-slate-200 p-4" data-purpose="filter-section">
            <form method="get" action="{{ route('financeiro.planos.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="w-full sm:w-36">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Ano Letivo:
                    </label>
                    <input type="number" name="ano_letivo" value="{{ $anoLetivo }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                </div>

                <div class="flex items-center gap-2 pt-1 sm:pt-0">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-700 hover:bg-slate-800 active:bg-slate-900 text-white text-sm font-medium shadow-sm transition duration-150">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Filtrar
                    </button>
                    @if(request('ano_letivo') && request('ano_letivo') != date('Y'))
                        <a href="{{ route('financeiro.planos.index') }}" class="px-3 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium transition shadow-2xs" style="text-decoration: none;">
                            Limpar
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- Tabela de Listagem -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-container">
            <!-- Cabeçalho com Gradiente Petróleo -->
            <div class="bg-gradient-to-r from-[#47728f] to-[#355972] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Planos Cadastrados ({{ $planos->total() }})
                    </h2>
                </div>
                <div class="text-xs text-slate-300 font-mono">
                    Total: {{ $planos->total() }} registros
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4">Nome do Plano</th>
                            <th class="py-3 px-4 text-center">Ano Letivo</th>
                            <th class="py-3 px-4 text-center">Parcelas</th>
                            <th class="py-3 px-4 text-right">Valor Anual</th>
                            <th class="py-3 px-4 text-right">Média Mensal</th>
                            <th class="py-3 px-4 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($planos as $plano)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4">
                                    <a href="{{ route('financeiro.planos.show', $plano->id) }}" class="font-semibold text-brand-petroleum hover:underline" style="color: #47728f; text-decoration: none;">
                                        {{ $plano->nome }}
                                    </a>
                                    @if($plano->descricao)
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            {{ $plano->descricao }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-medium text-slate-700">
                                    {{ $plano->ano_letivo }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-semibold text-slate-800">
                                    {{ $plano->qtd_parcelas }}x
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    R$ {{ number_format($plano->valor_total, 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-600">
                                    R$ {{ number_format($plano->qtd_parcelas > 0 ? $plano->valor_total / $plano->qtd_parcelas : 0, 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('financeiro.planos.show', $plano->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-brand-petroleum hover:text-white text-slate-700 text-xs font-medium border border-slate-200 transition" title="Ver Parcelas e Detalhes" style="text-decoration: none;">
                                        <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        Detalhes
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 px-4 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-3" width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                                        </svg>
                                        <span class="text-base font-medium text-slate-600">Nenhum plano de cobrança cadastrado para este ano</span>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm">
                                            Crie um plano para vincular às matrículas dos alunos e emitir as mensalidades.
                                        </p>
                                        <div class="mt-4">
                                            <a href="{{ route('financeiro.planos.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium shadow-xs transition" style="text-decoration: none;">
                                                <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                                Cadastrar Plano Agora
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            @if($planos->hasPages())
                <div class="bg-white px-5 py-3.5 border-t border-slate-200 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Mostrando <span class="font-medium text-slate-700">{{ $planos->firstItem() }}</span> até <span class="font-medium text-slate-700">{{ $planos->lastItem() }}</span> de <span class="font-medium text-slate-700">{{ $planos->total() }}</span> planos
                    </div>
                    <div>
                        {{ $planos->appends(['ano_letivo' => $anoLetivo])->links() }}
                    </div>
                </div>
            @endif
        </section>

    </main>
</div>
@endsection
