@extends('financeiro::layouts.app')

@section('title', 'Central de Relatórios Financeiros')
@section('breadcrumb', 'Relatórios Financeiros')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="relatorios-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Central de Relatórios e Demonstrativos Fiscais',
            'subtitle' => 'Relatórios gerenciais de inadimplência, fluxo de caixa, DRE escolar e declarações oficiais para pais e alunos',
            'breadcrumbs' => [
                ['label' => 'Relatórios']
            ],
            'actions' => '
                <a href="' . route('financeiro.dashboard') . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar ao Dashboard
                </a>
            '
        ])

        <!-- Grade de Cards de Relatórios -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Card 1: Inadimplência & Aging -->
            <div class="bg-white border border-slate-200 border-t-4 border-t-red-600 shadow-xs p-6 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <div class="flex items-center gap-2.5 text-red-600 mb-2">
                        <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <h2 class="text-base font-bold text-slate-900" style="margin: 0;">
                            Painel de Inadimplência & Aging
                        </h2>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Classificação de atrasos por faixas etárias de débito (1-30, 31-60, 61-90 e >90 dias) e verificação legal de débitos para rematrícula com respaldo na <strong>Lei Federal nº 9.870/1999</strong>.
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100">
                    <a href="{{ route('financeiro.relatorios.inadimplencia') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold shadow-2xs transition" style="text-decoration: none;">
                        Consultar Inadimplência &raquo;
                    </a>
                </div>
            </div>

            <!-- Card 2: Fluxo de Caixa Realizado -->
            <div class="bg-white border border-slate-200 border-t-4 border-t-brand-petroleum shadow-xs p-6 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <div class="flex items-center gap-2.5 text-brand-petroleum mb-2">
                        <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <h2 class="text-base font-bold text-slate-900" style="margin: 0;">
                            Fluxo de Caixa Realizado
                        </h2>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Comparativo consolidado de receitas líquidas recebidas (mensalidades, matrículas e acordos) vs despesas operacionais pagas mês a mês durante todo o ano letivo.
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100">
                    <a href="{{ route('financeiro.relatorios.fluxo-caixa') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                        Acessar Fluxo de Caixa &raquo;
                    </a>
                </div>
            </div>

            <!-- Card 3: DRE Escolar -->
            <div class="bg-white border border-slate-200 border-t-4 border-t-emerald-600 shadow-xs p-6 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <div class="flex items-center gap-2.5 text-emerald-600 mb-2">
                        <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <h2 class="text-base font-bold text-slate-900" style="margin: 0;">
                            DRE Escolar (Resultado do Exercício)
                        </h2>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Demonstração contábil do resultado escolar: faturamento bruto contratado, deduções/bolsas de estudo, receita operacional líquida, custos por centro de custo e superávit do período.
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100">
                    <a href="{{ route('financeiro.relatorios.dre') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                        Visualizar DRE Escolar &raquo;
                    </a>
                </div>
            </div>

            <!-- Card 4: Informe de Rendimentos IRPF -->
            <div class="bg-white border border-slate-200 border-t-4 border-t-amber-500 shadow-xs p-6 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <div class="flex items-center gap-2.5 text-amber-600 mb-2">
                        <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <h2 class="text-base font-bold text-slate-900" style="margin: 0;">
                            Informe de Pagamentos IRPF
                        </h2>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Declaração anual com dados fiscais completos (CNPJ da escola, CPF do responsável e dependente/aluno) e valores totais pagos para declaração de despesas de instrução no Imposto de Renda.
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100">
                    <a href="{{ route('financeiro.relatorios.informe-irpf') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-2xs transition" style="text-decoration: none;">
                        Emitir Informe IRPF &raquo;
                    </a>
                </div>
            </div>

            <!-- Card 5: Quitação Anual Lei 12.007 -->
            <div class="bg-white border border-slate-200 border-t-4 border-t-teal-600 shadow-xs p-6 flex flex-col justify-between hover:shadow-md transition">
                <div>
                    <div class="flex items-center gap-2.5 text-teal-600 mb-2">
                        <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <h2 class="text-base font-bold text-slate-900" style="margin: 0;">
                            Declaração de Quitação Anual (Lei 12.007/09)
                        </h2>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Declaração obrigatória por lei atestando a quitação integral de débitos do exercício anterior, com autenticação eletrônica para emissão aos responsáveis em dia.
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100">
                    <a href="{{ route('financeiro.relatorios.quitacao-anual') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold shadow-2xs transition" style="text-decoration: none;">
                        Emitir Quitação Anual &raquo;
                    </a>
                </div>
            </div>

        </div>

    </main>
</div>
@endsection
