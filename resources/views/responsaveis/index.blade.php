@extends('financeiro::layouts.app')

@section('title', 'Responsáveis Financeiros')
@section('breadcrumb', 'Responsáveis Financeiros')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="responsaveis-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Cadastro de Responsáveis Financeiros
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Gestão dos responsáveis legais e financeiros vinculados aos alunos da instituição
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('financeiro.responsaveis.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm hover:shadow transition duration-150" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Novo Responsável
                </a>
            </div>
        </header>

        <!-- Barra de Busca e Filtro -->
        <section class="bg-white shadow-xs border border-slate-200 p-4" data-purpose="filter-section">
            <form method="get" action="{{ route('financeiro.responsaveis.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[280px]">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Buscar Responsável:
                    </label>
                    <div class="relative">
                        <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar por Nome, CPF/CNPJ ou E-mail..." class="w-full pl-9 pr-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1 sm:pt-0">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-700 hover:bg-slate-800 active:bg-slate-900 text-white text-sm font-medium shadow-sm transition duration-150">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Buscar
                    </button>
                    @if($busca)
                        <a href="{{ route('financeiro.responsaveis.index') }}" class="px-3 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium transition shadow-2xs" style="text-decoration: none;">
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
                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Responsáveis Cadastrados ({{ $responsaveis->total() }})
                    </h2>
                </div>
                <div class="text-xs text-slate-300 font-mono">
                    Total: {{ $responsaveis->total() }} registros
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4">Nome do Responsável</th>
                            <th class="py-3 px-4">CPF / CNPJ</th>
                            <th class="py-3 px-4">E-mail</th>
                            <th class="py-3 px-4">Celular</th>
                            <th class="py-3 px-4">Cidade / UF</th>
                            <th class="py-3 px-4 text-center">Alunos Vinculados</th>
                            <th class="py-3 px-4 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($responsaveis as $resp)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-brand-petroleum/10 text-brand-petroleum font-bold flex items-center justify-center text-xs shrink-0">
                                            {{ strtoupper(substr($resp->nome, 0, 1)) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('financeiro.responsaveis.show', $resp->id) }}" class="font-semibold text-brand-petroleum hover:underline" style="color: #47728f; text-decoration: none;">
                                                {{ $resp->nome }}
                                            </a>
                                            @if($resp->observacoes)
                                                <div class="text-[11px] text-slate-400 truncate max-w-xs" title="{{ $resp->observacoes }}">
                                                    {{ $resp->observacoes }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-600">
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 border border-slate-200 text-xs">
                                        {{ $resp->cpf_cnpj_formatado }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ $resp->email ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 font-mono">
                                    {{ $resp->telefone_celular ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ $resp->cidade ? $resp->cidade . ' / ' . $resp->estado : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @php
                                        $qtdAlunos = $resp->aluno_vinculos_count ?? 0;
                                    @endphp
                                    @if($qtdAlunos > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <svg class="w-3 h-3 text-emerald-600" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 14l9-5-9-5-9 5 9 5z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                            {{ $qtdAlunos }} {{ $qtdAlunos == 1 ? 'aluno' : 'alunos' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                            Nenhum aluno
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('financeiro.responsaveis.show', $resp->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-brand-petroleum hover:text-white text-slate-700 text-xs font-medium border border-slate-200 transition" title="Ver Detalhes e Alunos Vinculados" style="text-decoration: none;">
                                            <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                            Detalhes
                                        </a>
                                        <a href="{{ route('financeiro.responsaveis.edit', $resp->id) }}" class="inline-flex items-center p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-medium border border-amber-200 transition" title="Editar Responsável" style="text-decoration: none;">
                                            <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 px-4 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-3" width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                                        </svg>
                                        <span class="text-base font-medium text-slate-600">Nenhum responsável financeiro encontrado</span>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm">
                                            @if($busca)
                                                Não encontramos nenhum registro compatível com os termos pesquisados.
                                            @else
                                                Cadastre o primeiro responsável para começar a emitir contratos e planos de cobrança.
                                            @endif
                                        </p>
                                        <div class="mt-4">
                                            <a href="{{ route('financeiro.responsaveis.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium shadow-xs transition" style="text-decoration: none;">
                                                <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                                Cadastrar Responsável Agora
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
            @if($responsaveis->hasPages())
                <div class="bg-white px-5 py-3.5 border-t border-slate-200 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Mostrando <span class="font-medium text-slate-700">{{ $responsaveis->firstItem() }}</span> até <span class="font-medium text-slate-700">{{ $responsaveis->lastItem() }}</span> de <span class="font-medium text-slate-700">{{ $responsaveis->total() }}</span> responsáveis
                    </div>
                    <div>
                        {{ $responsaveis->appends(['busca' => $busca])->links() }}
                    </div>
                </div>
            @endif
        </section>

    </main>
</div>
@endsection
