@extends('financeiro::layouts.app')

@section('title', 'Demonstração do Resultado do Exercício - DRE')
@section('breadcrumb', 'Relatórios > DRE Escolar')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
    <style>
        @media print {
            #corpo > table.tablelistagem, #menu, .noprint, header, footer { display: none !important; }
            .financeiro-dashboard-container { background: white !important; }
        }
    </style>
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="dre-container">
        
        @php
            $anoOptions = '';
            for ($y = date('Y') + 1; $y >= date('Y') - 4; $y--) {
                $sel = ($ano == $y) ? 'selected' : '';
                $anoOptions .= "<option value=\"{$y}\" {$sel}>{$y}</option>";
            }
        @endphp

        @include('financeiro::partials.page-header', [
            'title' => 'Demonstração do Resultado do Exercício (DRE)',
            'subtitle' => 'Exercício contábil de ' . $ano . ': faturamento bruto, deduções, custos operacionais e resultado líquido',
            'breadcrumbs' => [
                ['label' => 'Relatórios', 'url' => route('financeiro.relatorios.index')],
                ['label' => 'DRE']
            ],
            'actions' => '
                <form method="get" action="' . route('financeiro.relatorios.dre') . '" class="flex items-center gap-2">
                    <label for="ano" class="text-xs font-semibold text-slate-600 uppercase">Exercício:</label>
                    <select name="ano" id="ano" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f]" onchange="this.form.submit();">
                        ' . $anoOptions . '
                    </select>
                </form>
                <button type="button" onclick="window.print();" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline">
                    <svg class="w-4 h-4 text-slate-600" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Imprimir DRE
                </button>
                <a href="' . route('financeiro.relatorios.index') . '" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar aos Relatórios
                </a>
            '
        ])

        <!-- Cards de Resumo Gerencial -->

        {{-- P-04 / A14 — Aviso obrigatório: regime de caixa explícito --}}
        <div class="flex items-start gap-3 bg-amber-50 border border-amber-300 px-4 py-3 text-amber-900 text-sm" role="alert" data-purpose="regime-caixa-notice">
            <svg class="w-5 h-5 flex-shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="font-semibold text-amber-800 mb-0.5">⚠️ Demonstrativo em Regime de Caixa</p>
                <p class="text-amber-700 leading-relaxed">
                    Este relatório reconhece <strong>receitas pelo efetivo recebimento</strong> (data do pagamento) e
                    <strong>despesas pela data do pagamento</strong> — não pela competência do serviço prestado.
                    <span class="block mt-1 text-xs text-amber-600">
                        Não incluídos: títulos em aberto / vencidos não recebidos · Títulos renegociados (contados apenas uma vez no acordo) ·
                        Receita de matrículas ainda pendentes · Despesas provisionadas não pagas.
                        Para análise por competência econômica, utilize o Fluxo de Caixa Previsto.
                    </span>
                </p>
            </div>
        </div>

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" data-purpose="kpi-cards">

            <article class="bg-white shadow-xs border border-slate-200 p-4 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 inset-x-0 h-1 bg-emerald-500"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Receita Bruta Faturada</span>
                    <span class="p-1 bg-emerald-50 text-emerald-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <span class="text-xl font-bold text-slate-900">R$ {{ number_format($receitaBruta, 2, ',', '.') }}</span>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Mensalidades e matrículas {{ $ano }}</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200 p-4 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 inset-x-0 h-1 bg-amber-500"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Deduções e Bolsas</span>
                    <span class="p-1 bg-amber-50 text-amber-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M20 12H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <span class="text-xl font-bold text-slate-900">R$ {{ number_format($deducoes, 2, ',', '.') }}</span>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Descontos irmãos e bolsas</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200 p-4 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 inset-x-0 h-1 bg-rose-500"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Despesas Operacionais</span>
                    <span class="p-1 bg-rose-50 text-rose-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 14l-7 7m0 0l-7-7m7 7V3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <span class="text-xl font-bold text-slate-900">R$ {{ number_format($totalDespesasOperacionais, 2, ',', '.') }}</span>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Custos e contas liquidadas</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200 p-4 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 inset-x-0 h-1 {{ $resultadoLiquido >= 0 ? 'bg-emerald-600' : 'bg-rose-600' }}"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Resultado Líquido</span>
                    <span class="p-1 {{ $resultadoLiquido >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($resultadoLiquido >= 0)
                                <path d="M5 10l7-7m0 0l7 7m-7-7v18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            @else
                                <path d="M19 14l-7 7m0 0l-7-7m7 7V3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            @endif
                        </svg>
                    </span>
                </div>
                <div>
                    <span class="text-xl font-bold {{ $resultadoLiquido >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        R$ {{ number_format($resultadoLiquido, 2, ',', '.') }}
                    </span>
                    <p class="text-xs font-semibold {{ $resultadoLiquido >= 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-1" style="margin: 4px 0 0 0;">
                        {{ $resultadoLiquido >= 0 ? 'Superávit Operacional' : 'Déficit Operacional' }}
                    </p>
                </div>
            </article>
        </section>

        <!-- Tabela Demonstrativa da DRE -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="dre-table">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Estrutura Contábil Oficial da DRE Escolar
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    Regime de Competência / Caixa Integrado
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                    <thead>
                        <tr class="bg-slate-100 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-6" scope="col">Rubrica / Conta Contábil</th>
                            <th class="py-3 px-6 text-right" scope="col">Valor (R$)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        <!-- 1. Receita Bruta -->
                        <tr class="bg-slate-50/70 font-semibold text-slate-900">
                            <td class="py-3.5 px-6">
                                (+) 1. RECEITA BRUTA OPERACIONAL (Mensalidades e Matrículas)
                            </td>
                            <td class="py-3.5 px-6 text-right font-bold text-emerald-700">
                                R$ {{ number_format($receitaBruta, 2, ',', '.') }}
                            </td>
                        </tr>

                        <!-- 2. Deduções -->
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-6 pl-10 text-slate-600">
                                (-) 2. DEDUÇÕES DA RECEITA BRUTA (Bolsas, Descontos Irmãos, Pontualidade)
                            </td>
                            <td class="py-3 px-6 text-right font-medium text-amber-700">
                                (R$ {{ number_format($deducoes, 2, ',', '.') }})
                            </td>
                        </tr>

                        <!-- 3. Receita Líquida -->
                        <tr class="bg-cyan-50/50 font-bold text-brand-petroleum border-y border-cyan-200">
                            <td class="py-3.5 px-6">
                                (=) 3. RECEITA OPERACIONAL LÍQUIDA
                            </td>
                            <td class="py-3.5 px-6 text-right font-bold text-brand-petroleum text-base">
                                R$ {{ number_format($receitaLiquida, 2, ',', '.') }}
                            </td>
                        </tr>

                        <!-- 4. Despesas Operacionais Cabeçalho -->
                        <tr class="bg-slate-100/90 font-bold text-slate-700">
                            <td class="py-2.5 px-6" colspan="2">
                                (-) 4. DESPESAS OPERACIONAIS POR CATEGORIA
                            </td>
                        </tr>

                        @forelse($despesasPorCategoria as $desp)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-2.5 px-6 pl-10 text-slate-600 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    {{ $desp->planoConta->descricao ?? 'Outras Despesas Operacionais' }}
                                </td>
                                <td class="py-2.5 px-6 text-right font-medium text-rose-700">
                                    (R$ {{ number_format($desp->total, 2, ',', '.') }})
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-3 px-6 pl-10 text-slate-400 italic">
                                    Nenhuma despesa operacional liquidada registrada no exercício de {{ $ano }}.
                                </td>
                            </tr>
                        @endforelse

                        <!-- Subtotal Despesas -->
                        <tr class="bg-rose-50/40 font-semibold text-rose-900">
                            <td class="py-3 px-6 pl-10">
                                TOTAL DAS DESPESAS OPERACIONAIS:
                            </td>
                            <td class="py-3 px-6 text-right font-bold text-rose-700">
                                (R$ {{ number_format($totalDespesasOperacionais, 2, ',', '.') }})
                            </td>
                        </tr>

                        <!-- 5. Resultado Líquido Final -->
                        <tr class="{{ $resultadoLiquido >= 0 ? 'bg-emerald-50 text-emerald-900 border-t-2 border-emerald-500' : 'bg-rose-50 text-rose-900 border-t-2 border-rose-500' }}">
                            <td class="py-4 px-6 text-base font-bold">
                                (=) 5. RESULTADO LÍQUIDO DO EXERCÍCIO ({{ $resultadoLiquido >= 0 ? 'SUPERÁVIT' : 'DÉFICIT' }})
                            </td>
                            <td class="py-4 px-6 text-right text-lg font-extrabold {{ $resultadoLiquido >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                R$ {{ number_format($resultadoLiquido, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <footer class="p-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                <span>Relatório gerado em {{ date('d/m/Y H:i') }} pelo Módulo Financeiro i-Educar</span>
                <span>Auditado e em conformidade com as Normas Brasileiras de Contabilidade (NBC TG)</span>
            </footer>
        </section>

    </main>
</div>
@endsection
