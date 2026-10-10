@extends('financeiro::layouts.app')

@section('title', 'Fluxo de Caixa Realizado - Exercício ' . $ano)
@section('breadcrumb', 'Relatórios > Fluxo de Caixa')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="fluxo-caixa-container">
        
        @php
            $anoOptions = '';
            for ($y = date('Y') + 1; $y >= date('Y') - 4; $y--) {
                $sel = ($ano == $y) ? 'selected' : '';
                $anoOptions .= "<option value=\"{$y}\" {$sel}>{$y}</option>";
            }
        @endphp

        @include('financeiro::partials.page-header', [
            'title' => 'Fluxo de Caixa Realizado (Exercício ' . $ano . ')',
            'subtitle' => 'Entradas líquidas efetivamente liquidadas vs despesas quitadas consolidadas mês a mês',
            'breadcrumbs' => [
                ['label' => 'Relatórios', 'url' => route('financeiro.relatorios.index')],
                ['label' => 'Fluxo de Caixa']
            ],
            'actions' => '
                <form method="get" action="' . route('financeiro.relatorios.fluxo-caixa') . '" class="flex items-center gap-2">
                    <label for="ano" class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Ano:</label>
                    <select name="ano" id="ano" onchange="this.form.submit();" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-800 text-xs font-semibold focus:outline-none focus:border-[#47728f]">
                        ' . $anoOptions . '
                    </select>
                </form>
                <a href="' . route('financeiro.relatorios.index') . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar aos Relatórios
                </a>
            '
        ])

        <!-- Cards com Indicadores Globais do Ano -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-slate-200 border-l-4 border-l-emerald-600 shadow-xs p-5">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Receitas (Entradas)</div>
                <div class="text-xl md:text-2xl font-bold font-mono text-emerald-700 mt-1">
                    R$ {{ number_format($totalReceitasAno, 2, ',', '.') }}
                </div>
                <div class="text-[11px] text-emerald-600 mt-0.5">Mensalidades, taxas e acordos recebidos</div>
            </div>

            <div class="bg-white border border-slate-200 border-l-4 border-l-red-600 shadow-xs p-5">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Despesas (Saídas)</div>
                <div class="text-xl md:text-2xl font-bold font-mono text-red-700 mt-1">
                    R$ {{ number_format($totalDespesasAno, 2, ',', '.') }}
                </div>
                <div class="text-[11px] text-red-600 mt-0.5">Contas e custos operacionais liquidados</div>
            </div>

            <div class="bg-white border border-slate-200 border-l-4 {{ $saldoOperacionalAno >= 0 ? 'border-l-brand-petroleum' : 'border-l-amber-600' }} shadow-xs p-5">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Resultado Operacional Líquido</div>
                <div class="text-xl md:text-2xl font-bold font-mono {{ $saldoOperacionalAno >= 0 ? 'text-brand-petroleum' : 'text-red-700' }} mt-1">
                    R$ {{ number_format($saldoOperacionalAno, 2, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">Superávit / Déficit financeiro anual</div>
            </div>
        </div>

        <!-- Tabela Mês a Mês do Fluxo de Caixa -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-container">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Demonstrativo Mensal Consolidado — Exercício {{ $ano }}
                    </h2>
                </div>
                <div class="text-xs text-slate-300">
                    Regime de Caixa (Valores Efetivamente Liquidados)
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4">Mês de Referência</th>
                            <th class="py-3 px-4 text-right" width="220">Receitas Realizadas (Entradas)</th>
                            <th class="py-3 px-4 text-right" width="220">Despesas Pagas (Saídas)</th>
                            <th class="py-3 px-4 text-right" width="220">Saldo Operacional do Mês</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @php
                            $mesesNomes = [
                                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                            ];
                        @endphp

                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $recMes = $receitasPorMes[$m] ?? 0.0;
                                $despMes = $despesasPorMes[$m] ?? 0.0;
                                $saldoMes = $recMes - $despMes;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    {{ $mesesNomes[$m] }} / {{ $ano }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-semibold text-emerald-700">
                                    R$ {{ number_format($recMes, 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-semibold text-red-700">
                                    R$ {{ number_format($despMes, 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold {{ $saldoMes >= 0 ? 'text-emerald-800' : 'text-red-700' }}">
                                    R$ {{ number_format($saldoMes, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 border-t-2 border-slate-300 font-bold text-slate-900">
                            <td class="py-4 px-4 text-xs uppercase tracking-wider font-bold">
                                TOTAL CONSOLIDADO DO EXERCÍCIO {{ $ano }}
                            </td>
                            <td class="py-4 px-4 text-right font-mono font-bold text-emerald-700 text-base">
                                R$ {{ number_format($totalReceitasAno, 2, ',', '.') }}
                            </td>
                            <td class="py-4 px-4 text-right font-mono font-bold text-red-700 text-base">
                                R$ {{ number_format($totalDespesasAno, 2, ',', '.') }}
                            </td>
                            <td class="py-4 px-4 text-right font-mono font-bold {{ $saldoOperacionalAno >= 0 ? 'text-emerald-800' : 'text-red-700' }} text-base">
                                R$ {{ number_format($saldoOperacionalAno, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

    </main>
</div>
@endsection
