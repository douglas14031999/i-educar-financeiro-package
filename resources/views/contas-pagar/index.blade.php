@extends('financeiro::layouts.app')

@section('title', 'Contas a Pagar e Despesas')
@section('breadcrumb', 'Contas a Pagar')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="contas-pagar-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Gestão de Contas a Pagar e Despesas',
            'subtitle' => 'Total em aberto para liquidação: R$ ' . number_format($totalAberto, 2, ',', '.'),
            'breadcrumbs' => [
                ['label' => 'Contas a Pagar']
            ],
            'actions' => '
                <a href="' . route('financeiro.contas-pagar.create') . '" class="inline-flex items-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Nova Conta a Pagar
                </a>
            '
        ])

        <!-- Barra de Busca e Filtros -->
        <section class="bg-white shadow-xs border border-slate-200 p-4" data-purpose="filter-section">
            <form method="get" action="{{ route('financeiro.contas-pagar.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[280px]">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Buscar Descrição ou Fornecedor:
                    </label>
                    <div class="relative">
                        <input type="text" name="busca" value="{{ $busca }}" placeholder="Ex: Energia, Material Escolar, Limpeza..." class="w-full pl-9 pr-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="w-full sm:w-44">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Status da Despesa:
                    </label>
                    <select name="status" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        <option value="a_pagar" {{ $status === 'a_pagar' ? 'selected' : '' }}>A Pagar (Em aberto)</option>
                        <option value="pago" {{ $status === 'pago' ? 'selected' : '' }}>Pagas (Liquidadas)</option>
                        <option value="todos" {{ $status === 'todos' ? 'selected' : '' }}>Todas as Despesas</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-1 sm:pt-0">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Filtrar
                    </button>
                    @if($busca || $status !== 'a_pagar')
                        <a href="{{ route('financeiro.contas-pagar.index') }}" class="px-3 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                            Limpar
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- Tabela de Listagem -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-container">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Despesas e Obrigações Financeiras ({{ $despesas->total() }})
                    </h2>
                </div>
                <div class="text-xs text-slate-300 font-mono">
                    Total: {{ $despesas->total() }} registros
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4" width="70">ID</th>
                            <th class="py-3 px-4" width="110">Vencimento</th>
                            <th class="py-3 px-4">Descrição / Fornecedor</th>
                            <th class="py-3 px-4">Classificação Contábil</th>
                            <th class="py-3 px-4 text-right">Valor</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($despesas as $d)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4 font-mono font-medium text-slate-500">
                                    #{{ $d->id }}
                                </td>
                                <td class="py-3.5 px-4 font-mono {{ $d->status === 'a_pagar' && \Carbon\Carbon::parse($d->data_vencimento)->isPast() ? 'text-red-600 font-bold' : 'text-slate-700' }}">
                                    {{ \Carbon\Carbon::parse($d->data_vencimento)->format('d/m/Y') }}
                                    @if($d->status === 'a_pagar' && \Carbon\Carbon::parse($d->data_vencimento)->isPast())
                                        <span class="text-[10px] uppercase tracking-wider text-red-500 block">Vencida</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-900">
                                        {{ $d->descricao }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                        @if($d->fornecedor_nome)
                                            <span>Fornecedor: <strong>{{ $d->fornecedor_nome }}</strong></span>
                                        @endif
                                        @if($d->documento_fiscal)
                                            <span class="font-mono bg-slate-100 px-1 border border-slate-200 text-[10px]">NF: {{ $d->documento_fiscal }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    <div class="font-medium text-brand-petroleum">
                                        {{ $d->planoConta->descricao ?? '-' }}
                                    </div>
                                    @if($d->centroCusto)
                                        <div class="text-[11px] text-slate-400">
                                            Centro de Custo: {{ $d->centroCusto->nome }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    R$ {{ number_format($d->valor, 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($d->status === 'pago')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Liquidada
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            A Pagar
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if($d->status === 'a_pagar')
                                            <button type="button" onclick="document.getElementById('modal-liquidar-{{ $d->id }}').style.display='flex';" class="inline-flex items-center gap-1 px-2.5 py-1.5 btn-ieducar-action btn-ieducar-green" title="Registrar Liquidação de Pagamento">
                                                <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                                Liquidar
                                            </button>
                                            <a href="{{ route('financeiro.contas-pagar.edit', $d->id) }}" class="inline-flex items-center p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium border border-slate-200 transition" title="Editar Despesa">
                                                <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                            </a>
                                        @else
                                            <span class="text-xs text-emerald-700 font-medium inline-flex items-center gap-1" title="Pago em {{ \Carbon\Carbon::parse($d->data_pagamento)->format('d/m/Y') }}">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                                Paga em {{ \Carbon\Carbon::parse($d->data_pagamento)->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal de Liquidação -->
                            @if($d->status === 'a_pagar')
                                <div id="modal-liquidar-{{ $d->id }}" style="display: none;" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
                                    <div class="bg-white max-w-md w-full border border-slate-200 shadow-xl overflow-hidden animate-fade-in text-left">
                                        <div class="bg-[#47728f] px-5 py-3.5 text-white flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                                <h3 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                                                    Liquidar Despesa #{{ $d->id }}
                                                </h3>
                                            </div>
                                            <button type="button" onclick="document.getElementById('modal-liquidar-{{ $d->id }}').style.display='none';" class="text-slate-300 hover:text-white">
                                                <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <form method="post" action="{{ route('financeiro.contas-pagar.liquidar', $d->id) }}" class="p-6 space-y-4">
                                            @csrf
                                            <div class="p-3 bg-slate-50 border border-slate-200">
                                                <div class="font-semibold text-slate-800 text-sm">{{ $d->descricao }}</div>
                                                <div class="text-xs text-slate-500 mt-1 font-mono">Valor: <strong class="text-red-700">R$ {{ number_format($d->valor, 2, ',', '.') }}</strong></div>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                                    Data de Pagamento Efetivo <span class="text-red-500">*</span>
                                                </label>
                                                <input type="date" name="data_pagamento" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                                    Conta Bancária de Saída <span class="text-red-500">*</span>
                                                </label>
                                                <select name="conta_bancaria_id" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                                    <option value="1">Caixa Geral da Escola</option>
                                                    <option value="2">Banco do Brasil - Conta Operacional</option>
                                                </select>
                                            </div>

                                            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                                                <button type="button" onclick="document.getElementById('modal-liquidar-{{ $d->id }}').style.display='none';" class="px-4 py-2 btn-ieducar-action btn-ieducar-outline">
                                                    Cancelar
                                                </button>
                                                <button type="submit" class="px-4 py-2 btn-ieducar-action btn-ieducar-green">
                                                    Confirmar Liquidação
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 px-4 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-3" width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                                        </svg>
                                        <span class="text-base font-medium text-slate-600">Nenhuma conta a pagar encontrada</span>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm">
                                            Utilize os filtros acima ou cadastre uma nova despesa ou obrigação a pagar.
                                        </p>
                                        <div class="mt-4">
                                            <a href="{{ route('financeiro.contas-pagar.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;">
                                                <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                                Registrar Nova Despesa
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
            @if($despesas->hasPages())
                <div class="bg-white px-5 py-3.5 border-t border-slate-200 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Mostrando <span class="font-medium text-slate-700">{{ $despesas->firstItem() }}</span> até <span class="font-medium text-slate-700">{{ $despesas->lastItem() }}</span> de <span class="font-medium text-slate-700">{{ $despesas->total() }}</span> despesas
                    </div>
                    <div>
                        {{ $despesas->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        </section>

    </main>
</div>
@endsection
