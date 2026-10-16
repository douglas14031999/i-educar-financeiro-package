@extends('financeiro::layouts.app')

@section('title', 'Matrículas sem Contrato Financeiro')
@section('breadcrumb', 'Contratos > Matrículas sem Contrato')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="matriculas-pendentes-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Matrículas sem Contrato Financeiro',
            'subtitle' => 'Alunos matriculados no i-Educar que ainda não possuem plano financeiro ou cobranças vinculadas no ano letivo',
            'breadcrumbs' => [
                ['label' => 'Contratos', 'url' => route('financeiro.contratos.index')],
                ['label' => 'Matrículas sem Contrato']
            ],
            'actions' => '
                <a href="' . route('financeiro.contratos.index') . '" class="inline-flex items-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4 text-slate-300" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar para Contratos
                </a>
            '
        ])

        <!-- Filtro por Ano Letivo -->
        <section class="bg-white shadow-xs border border-slate-200 p-4" data-purpose="filter-section">
            <form method="get" action="{{ route('financeiro.contratos.matriculas-sem-contrato') }}" class="flex flex-wrap items-end gap-3">
                <div class="w-full sm:w-44">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Ano Letivo:
                    </label>
                    <input type="number" name="ano_letivo" value="{{ $anoLetivo }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                </div>

                <div class="flex items-center gap-2 pt-1 sm:pt-0">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Filtrar Ano
                    </button>
                </div>
            </form>
        </section>

        <!-- Tabela de Pendências -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-container">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Matrículas com Pendência de Contrato ({{ $pendencias->count() }})
                    </h2>
                </div>
                <span class="text-xs font-medium text-cyan-100">
                    Ano Letivo: {{ $anoLetivo }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                    <thead>
                        <tr class="bg-slate-100/90 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4" scope="col">Matrícula ID</th>
                            <th class="py-3 px-4" scope="col">Aluno</th>
                            <th class="py-3 px-4" scope="col">Ano Letivo</th>
                            <th class="py-3 px-4 text-center" scope="col">Situação</th>
                            <th class="py-3 px-4 text-center" scope="col">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($pendencias as $p)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-900 whitespace-nowrap">
                                    #{{ $p->matricula_id }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ \iEducar\Financeiro\Support\AlunoLookup::nomeComCpf($p->aluno_id) }}
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ $p->ano_letivo }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                        Sem Contrato
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <a href="{{ route('financeiro.contratos.create', ['aluno_id' => $p->aluno_id, 'matricula_id' => $p->matricula_id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;">
                                        <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        Criar Contrato
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">
                                    Nenhuma matrícula pendente de contrato encontrada para o ano letivo selecionado.
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
