@extends('financeiro::layouts.app')

@section('title', 'Declaração de Quitação Anual (Lei 12.007/09)')
@section('breadcrumb', 'Relatórios > Quitação Anual')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
    <style>
        @media print {
            #corpo > table.tablelistagem, #menu, .noprint, header, footer, form, .financeiro-filter-card { display: none !important; }
            .financeiro-dashboard-container { background: white !important; padding: 0 !important; }
            #documento-quitacao { border: 2px solid #059669 !important; box-shadow: none !important; margin: 0 !important; }
        }
    </style>
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="quitacao-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Declaração de Quitação Anual de Débitos
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Emissão da certidão anual de quitação integral com eficácia jurídica conforme a <strong>Lei Federal nº 12.007/2009</strong>
                </p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                @if($responsavel && $quitadoIntegralmente)
                    <button type="button" onclick="window.print();" class="inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-green">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Imprimir Certidão Oficial
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

        <!-- Filtros de Seleção -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden financeiro-filter-card" data-purpose="filter-card">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Consultar Situação Cadastral de Quitação
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    Exercício Anual e Responsável Financeiro
                </span>
            </div>

            <div class="p-5">
                <form method="get" action="{{ route('financeiro.relatorios.quitacao-anual') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-3">
                        <label for="ano" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Ano de Exercício:
                        </label>
                        <input type="number" name="ano" id="ano" value="{{ $ano }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum" required>
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
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Verificar Elegibilidade
                        </button>
                    </div>
                </form>
            </div>
        </section>

        @if($responsavel)
            @if($quitadoIntegralmente)
                <!-- Certidão Positiva com Efeito de Negativa / Declaração de Quitação Oficial -->
                <article id="documento-quitacao" class="bg-white shadow-xs border-2 border-emerald-600 p-6 md:p-10 space-y-6" data-purpose="official-quitacao">
                    <div class="text-center border-b-2 border-emerald-600 pb-5">
                        <div class="inline-flex items-center justify-center p-3 bg-emerald-100 text-emerald-800 rounded-full mb-2">
                            <svg class="w-8 h-8" width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl md:text-2xl font-extrabold text-emerald-800 uppercase tracking-wider" style="margin: 0;">
                            Declaração Anual de Quitação de Débitos
                        </h2>
                        <span class="text-xs md:text-sm font-semibold text-slate-600 block mt-1">
                            EMITIDA EM ESTRITO CUMPRIMENTO À LEI FEDERAL Nº 12.007, DE 29 DE JULHO DE 2009
                        </span>
                    </div>

                    <div class="text-sm md:text-base leading-relaxed text-slate-800 space-y-4 text-justify">
                        <p>
                            A instituição de ensino mantenedora, operando em conformidade com o sistema educacional <strong>i-Educar</strong>, para os devidos fins de direito e em estrito cumprimento ao disposto na <strong>Lei Federal nº 12.007/2009</strong>, 
                            <strong>DECLARA E CERTIFICA</strong> que o(a) responsável financeiro(a) <strong>{{ $responsavel->nome }}</strong>, 
                            inscrito(a) no CPF sob o nº <strong>{{ $responsavel->cpf_cnpj_formatado }}</strong>, 
                            encontra-se em <strong>PLENA QUITAÇÃO INTEGRAL</strong> de todas as obrigações e mensalidades escolares relativas ao exercício letivo de <strong>{{ $ano }}</strong>.
                        </p>
                        <p class="text-slate-600 text-xs md:text-sm">
                            Esta declaração compreende a liquidação total de todos os títulos emitidos no referido ano-base, substituindo, para todos os efeitos de comprovação de regularidade, as quitações mensais dos débitos do período, nos termos do Artigo 2º da referida lei.
                        </p>
                    </div>

                    <!-- Tabela de Títulos Liquidados -->
                    <div class="border border-slate-200 overflow-hidden">
                        <div class="bg-slate-100 px-4 py-2 border-b border-slate-200">
                            <span class="text-xs font-bold text-slate-700 uppercase">
                                Relação Consolidada de Títulos do Exercício de {{ $ano }}
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                                <thead>
                                    <tr class="bg-slate-50 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                                        <th class="py-2.5 px-4" scope="col">Competência</th>
                                        <th class="py-2.5 px-4" scope="col">Vencimento Original</th>
                                        <th class="py-2.5 px-4 text-right" scope="col">Valor Nominal</th>
                                        <th class="py-2.5 px-4 text-center" scope="col">Situação Contábil</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 text-slate-700">
                                    @foreach($titulosAno as $t)
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-2.5 px-4 font-semibold text-slate-900">{{ $t->competencia }}</td>
                                            <td class="py-2.5 px-4">{{ \Carbon\Carbon::parse($t->data_vencimento)->format('d/m/Y') }}</td>
                                            <td class="py-2.5 px-4 text-right font-medium">R$ {{ number_format($t->valor_nominal, 2, ',', '.') }}</td>
                                            <td class="py-2.5 px-4 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Liquidado Integralmente
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Assinatura e Autenticação -->
                    <div class="pt-8 text-center space-y-4">
                        <div class="inline-block border-t border-slate-700 w-80 pt-2 text-xs text-slate-700">
                            <strong>Setor de Controladoria e Gestão Financeira Escolar</strong><br>
                            Emitido digitalmente em {{ date('d/m/Y \à\s H:i:s') }}
                        </div>

                        <div class="pt-2 text-[10px] text-slate-400 font-mono">
                            Código Verificador de Autenticidade: {{ hash('sha256', 'LEI12007' . $responsavel->id . $ano . config('app.key')) }}
                        </div>
                    </div>
                </article>
            @else
                <!-- Alerta de Pendência Financeira (Impeditivo Legal) -->
                <article class="bg-rose-50 border border-rose-200 p-6 space-y-3" data-purpose="impeditive-alert">
                    <div class="flex items-center gap-2.5 text-rose-800">
                        <svg class="w-6 h-6 text-rose-600 flex-shrink-0" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <h3 class="text-base font-bold" style="margin: 0;">
                            Impossível Emitir Quitação Anual - Débitos Pendentes
                        </h3>
                    </div>
                    <p class="text-xs md:text-sm text-rose-900 leading-relaxed">
                        O(A) responsável financeiro(a) <strong>{{ $responsavel->nome }}</strong> possui parcelas ou mensalidades em aberto, parciais ou em atraso referentes ao ano de <strong>{{ $ano }}</strong>.
                    </p>
                    <p class="text-xs text-rose-700">
                        De acordo com o Artigo 3º da <strong>Lei Federal nº 12.007/2009</strong>, a declaração anual somente poderá ser emitida quando todos os débitos do ano estiverem comprovadamente e integralmente quitados.
                    </p>
                    <div class="pt-2 flex items-center gap-3">
                        <a href="{{ route('financeiro.renegociacao.index', ['responsavel_id' => $responsavel->id]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-rose-700 hover:bg-rose-800 text-white text-xs font-semibold shadow-sm transition" style="text-decoration: none;">
                            Iniciar Renegociação / Acordo
                        </a>
                        <a href="{{ route('financeiro.titulos.index', ['busca' => $responsavel->nome]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white hover:bg-rose-100 text-rose-800 text-xs font-semibold border border-rose-300 transition" style="text-decoration: none;">
                            Consultar Títulos em Aberto
                        </a>
                    </div>
                </article>
            @endif
        @endif

    </main>
</div>
@endsection
