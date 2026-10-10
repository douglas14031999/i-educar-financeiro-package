@extends('financeiro::layouts.app')

@section('title', 'Contratos Financeiros')
@section('breadcrumb', 'Contratos Financeiros')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="contratos-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Gestão de Contratos Financeiros
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Administração de vínculos contratuais, planos de cobrança e anuidades por matrícula
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('financeiro.contratos.create') }}" class="inline-flex items-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Novo Contrato por Matrícula
                </a>
            </div>
        </header>

        <!-- Barra de Filtros e Busca -->
        <section class="bg-white shadow-xs border border-slate-200 p-4" data-purpose="filter-section">
            <form method="get" action="{{ route('financeiro.contratos.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="w-full sm:w-36">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Ano Letivo:
                    </label>
                    <input type="number" name="ano_letivo" value="{{ $anoLetivo }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                </div>

                <div class="w-full sm:w-48">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Status:
                    </label>
                    <select name="status" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        <option value="">Todos os Status</option>
                        <option value="ativo" {{ $status === 'ativo' ? 'selected' : '' }}>Ativos</option>
                        <option value="cancelado" {{ $status === 'cancelado' ? 'selected' : '' }}>Cancelados</option>
                        <option value="quitado" {{ $status === 'quitado' ? 'selected' : '' }}>Quitados</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-1 sm:pt-0">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Filtrar
                    </button>
                    @if(request()->anyFilled(['ano_letivo', 'status']))
                        <a href="{{ route('financeiro.contratos.index') }}" class="px-3 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                            Limpar
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- Tabela de Listagem -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-container">
            <!-- Cabeçalho com Gradiente Petróleo -->
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Contratos Financeiros Registrados ({{ $contratos->total() }})
                    </h2>
                </div>
                <div class="text-xs text-slate-300 font-mono">
                    Total: {{ $contratos->total() }} registros
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4">Número Contrato</th>
                            <th class="py-3 px-4">Matrícula</th>
                            <th class="py-3 px-4">Aluno ID</th>
                            <th class="py-3 px-4">Responsável Financeiro</th>
                            <th class="py-3 px-4">Plano Contratado</th>
                            <th class="py-3 px-4 text-right">Valor Total</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($contratos as $contrato)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4 font-mono font-semibold text-brand-petroleum">
                                    <a href="{{ route('financeiro.contratos.show', $contrato->id) }}" class="hover:underline flex items-center gap-1.5" style="color: #47728f; text-decoration: none;">
                                        <svg class="w-3.5 h-3.5 text-slate-400" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        {{ $contrato->numero_contrato }}
                                    </a>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    #{{ $contrato->matricula_id }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    Aluno #{{ $contrato->aluno_id }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-900">
                                        {{ $contrato->responsavel->nome ?? 'Não informado' }}
                                    </div>
                                    @if(optional($contrato->responsavel)->cpf_cnpj_formatado)
                                        <div class="text-[11px] text-slate-400 font-mono">
                                            CPF: {{ $contrato->responsavel->cpf_cnpj_formatado }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ $contrato->plano->nome ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-semibold text-slate-900 font-mono">
                                    R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($contrato->status === 'ativo')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Ativo
                                        </span>
                                    @elseif($contrato->status === 'cancelado')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            Cancelado
                                        </span>
                                    @elseif($contrato->status === 'quitado')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-100 text-teal-800 border border-teal-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                                            Quitado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ ucfirst($contrato->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('financeiro.contratos.show', $contrato->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-brand-petroleum hover:text-white text-slate-700 text-xs font-medium border border-slate-200 transition" title="Ver Detalhes do Contrato" style="text-decoration: none;">
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
                                <td colspan="8" class="py-12 px-4 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-3" width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                                        </svg>
                                        <span class="text-base font-medium text-slate-600">Nenhum contrato financeiro encontrado</span>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm">
                                            Utilize os filtros acima para refinar sua busca ou cadastre um novo contrato vinculado a uma matrícula.
                                        </p>
                                        <div class="mt-4">
                                            <a href="{{ route('financeiro.contratos.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;">
                                                <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                                Cadastrar Novo Contrato
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
            @if($contratos->hasPages())
                <div class="bg-white px-5 py-3.5 border-t border-slate-200 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Mostrando <span class="font-medium text-slate-700">{{ $contratos->firstItem() }}</span> até <span class="font-medium text-slate-700">{{ $contratos->lastItem() }}</span> de <span class="font-medium text-slate-700">{{ $contratos->total() }}</span> contratos
                    </div>
                    <div>
                        {{ $contratos->appends(['ano_letivo' => $anoLetivo, 'status' => $status])->links() }}
                    </div>
                </div>
            @endif
        </section>

    </main>
</div>
@endsection
