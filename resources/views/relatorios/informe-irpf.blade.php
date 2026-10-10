@extends('financeiro::layouts.app')

@section('title', 'Informe de Rendimentos e Pagamentos para IRPF')
@section('breadcrumb', 'Relatórios > Informe IRPF')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
    <style>
        @media print {
            #corpo > table.tablelistagem, #menu, .noprint, header, footer, form, .financeiro-filter-card { display: none !important; }
            .financeiro-dashboard-container { background: white !important; padding: 0 !important; }
            #documento-irpf { border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
    </style>
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="irpf-container">
        
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
                        Comprovante de Pagamentos para IRPF
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Declaração oficial de pagamentos de instrução para fins de dedução no Imposto de Renda Pessoa Física
                </p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                @if($responsavel)
                    <button type="button" onclick="window.print();" class="inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-green">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Imprimir Declaração
                    </button>
                @endif
                <a href="{{ route('financeiro.relatorios.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar aos Relatórios
                </a>
            </div>
        </header>

        <!-- Filtros de Emissão -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden financeiro-filter-card" data-purpose="filter-card">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Selecionar Parâmetros de Emissão
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    Ano-Calendário e Responsável Financeiro
                </span>
            </div>

            <div class="p-5">
                <form method="get" action="{{ route('financeiro.relatorios.informe-irpf') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-3">
                        <label for="ano_base" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Ano-Base (Exercício):
                        </label>
                        <input type="number" name="ano_base" id="ano_base" value="{{ $anoBase }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum" required>
                    </div>

                    <div class="md:col-span-6">
                        <label for="responsavel_id" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Responsável Financeiro:
                        </label>
                        <select name="responsavel_id" id="responsavel_id" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum" required>
                            <option value="">-- Selecione o Responsável Financeiro --</option>
                            @foreach($responsaveis as $resp)
                                <option value="{{ $resp->id }}" {{ ($responsavel && $responsavel->id == $resp->id) ? 'selected' : '' }}>
                                    {{ $resp->nome }} (CPF: {{ $resp->cpf_cnpj_formatado }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Gerar Comprovante
                        </button>
                    </div>
                </form>
            </div>
        </section>

        @if($responsavel)
            <!-- Documento Oficial para Impressão -->
            <article id="documento-irpf" class="bg-white shadow-xs border border-slate-200 p-6 md:p-8 space-y-6" data-purpose="official-document">
                <!-- Cabeçalho do Documento -->
                <div class="border-b-2 border-brand-petroleum pb-4 text-center">
                    <h2 class="text-lg md:text-xl font-extrabold text-brand-petroleum uppercase tracking-wide" style="margin: 0;">
                        Declaração de Pagamentos de Instrução e Educação
                    </h2>
                    <p class="text-xs md:text-sm text-slate-600 mt-1" style="margin: 4px 0 0 0;">
                        Comprovante de Rendimentos Pagos a Pessoas Jurídicas para Fins de Declaração de Ajuste Anual do IRPF
                    </p>
                </div>

                <!-- Dados Cadastrais -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs md:text-sm bg-slate-50 p-4 border border-slate-200">
                    <div>
                        <span class="text-slate-500 font-bold uppercase block text-[11px]">Instituição de Ensino / Fonte Pagadora:</span>
                        <strong class="text-slate-900 block mt-0.5">Escola / Mantenedora Integrada ao i-Educar</strong>
                        <span class="text-slate-600 text-xs">CNPJ conforme Cadastro Oficial Municipal / Estadual</span>
                    </div>
                    <div>
                        <span class="text-slate-500 font-bold uppercase block text-[11px]">Ano-Calendário de Referência:</span>
                        <strong class="text-slate-900 text-base block mt-0.5">{{ $anoBase }}</strong>
                        <span class="text-slate-600 text-xs">Data de Emissão: {{ date('d/m/Y H:i') }}</span>
                    </div>
                    <div class="md:col-span-2 pt-2 border-t border-slate-200">
                        <span class="text-slate-500 font-bold uppercase block text-[11px]">Beneficiário Titular / Responsável Financeiro:</span>
                        <strong class="text-slate-900 text-sm md:text-base">{{ $responsavel->nome }}</strong>
                        <span class="text-slate-700 ml-2 font-mono">CPF: {{ $responsavel->cpf_cnpj_formatado }}</span>
                    </div>
                </div>

                <!-- Detalhamento por Aluno -->
                <div class="space-y-6">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-b border-slate-200 pb-2" style="margin: 0;">
                        Discriminação dos Valores Pagos por Aluno / Dependente
                    </h3>

                    @forelse($pagamentosAgrupados as $alunoId => $dadosAluno)
                        <div class="border border-slate-200 overflow-hidden">
                            <div class="bg-slate-100 px-4 py-2.5 flex items-center justify-between border-b border-slate-200">
                                <span class="text-xs md:text-sm font-semibold text-slate-800">
                                    Aluno(a) / Dependente: <strong>Código #{{ $alunoId }}</strong>
                                </span>
                                <span class="text-xs font-bold text-emerald-700">
                                    Subtotal: R$ {{ number_format($dadosAluno['total'], 2, ',', '.') }}
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                                    <thead>
                                        <tr class="bg-slate-50 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                                            <th class="py-2.5 px-4" scope="col">Data Pagamento</th>
                                            <th class="py-2.5 px-4" scope="col">Competência / Descrição</th>
                                            <th class="py-2.5 px-4" scope="col">Forma de Pagamento</th>
                                            <th class="py-2.5 px-4 text-right" scope="col">Valor Pago (R$)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 text-slate-700">
                                        @foreach($dadosAluno['itens'] as $item)
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="py-2.5 px-4 whitespace-nowrap">
                                                    {{ \Carbon\Carbon::parse($item->data_pagamento)->format('d/m/Y') }}
                                                </td>
                                                <td class="py-2.5 px-4">
                                                    {{ $item->titulo->competencia ?? '-' }} - Mensalidade Escolar
                                                </td>
                                                <td class="py-2.5 px-4 whitespace-nowrap capitalize">
                                                    {{ str_replace('_', ' ', $item->forma_pagamento) }}
                                                </td>
                                                <td class="py-2.5 px-4 text-right font-medium text-slate-900 whitespace-nowrap">
                                                    R$ {{ number_format($item->valor_pago, 2, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-500 bg-slate-50 border border-slate-200">
                            Nenhum pagamento liquidado registrado para este responsável no ano-base {{ $anoBase }}.
                        </div>
                    @endforelse
                </div>

                <!-- Totalizador -->
                <div class="bg-emerald-50 border border-emerald-200 p-4 md:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider block">
                            Valor Total Comprovado Pago no Ano-Base {{ $anoBase }}:
                        </span>
                        <span class="text-xs text-emerald-700">
                            Soma de todas as mensalidades e parcelas escolares liquidadas
                        </span>
                    </div>
                    <span class="text-2xl font-extrabold text-emerald-700">
                        R$ {{ number_format($totalDeclarado, 2, ',', '.') }}
                    </span>
                </div>

                <!-- Rodapé de Autenticação -->
                <footer class="pt-4 border-t border-slate-200 text-center text-[11px] text-slate-500 space-y-1">
                    <p style="margin: 0;">
                        Declaração emitida eletronicamente pelo Sistema de Gestão Financeira Integrada do i-Educar em {{ date('d/m/Y H:i') }}.
                    </p>
                    <p class="font-mono text-[10px] text-slate-400" style="margin: 2px 0 0 0;">
                        Hash de Validação e Integridade: {{ hash('sha256', $responsavel->id . $anoBase . $totalDeclarado . config('app.key')) }}
                    </p>
                </footer>
            </article>
        @endif

    </main>
</div>
@endsection
