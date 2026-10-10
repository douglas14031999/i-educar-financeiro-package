@extends('financeiro::layouts.app')

@section('title', 'Simulação de Acordo e Novação')
@section('breadcrumb', 'Renegociação > Simulação')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="renegociacao-simulacao-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Proposta de Acordo e Novação de Dívida',
            'subtitle' => 'Confira o demonstrativo financeiro e a projeção das novas parcelas antes de formalizar a transação',
            'breadcrumbs' => [
                ['label' => 'Renegociação de Dívidas', 'url' => route('financeiro.renegociacao.index')],
                ['label' => 'Simulação de Acordo']
            ],
            'actions' => '
                <a href="' . route('financeiro.renegociacao.index') . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar à Seleção
                </a>
            '
        ])

        <!-- Resumo Financeiro da Proposta -->
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Resumo Consolidado da Proposta
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    Novação de Dívida Extrajudicial
                </span>
            </div>

            <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Valor Original dos Títulos</div>
                    <div class="font-mono font-bold text-slate-800 mt-1 text-base">
                        R$ {{ number_format($simulacao['total_original'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5">{{ count($simulacao['titulos']) }} títulos selecionados</div>
                </div>

                <div class="p-3 bg-red-50/60 border border-red-200">
                    <div class="text-[11px] font-semibold text-red-800 uppercase tracking-wider">Desconto Negociado</div>
                    <div class="font-mono font-bold text-red-700 mt-1 text-base">
                        - R$ {{ number_format($simulacao['desconto_concedido'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-red-600 mt-0.5">Abatimento direto sobre a dívida</div>
                </div>

                <div class="p-3 bg-emerald-50/60 border border-emerald-200">
                    <div class="text-[11px] font-semibold text-emerald-800 uppercase tracking-wider">Valor Final do Acordo</div>
                    <div class="font-mono font-bold text-emerald-700 mt-1 text-lg">
                        R$ {{ number_format($simulacao['valor_acordo'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-emerald-600 mt-0.5">Novo saldo líquido acordado</div>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Condição de Pagamento</div>
                    <div class="font-mono font-bold text-brand-petroleum mt-1 text-base">
                        {{ $simulacao['qtd_parcelas'] }}x parcela(s)
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">Vencimento inicial: {{ $simulacao['parcelas'][0]['data_vencimento_formatada'] }}</div>
                </div>
            </div>
        </div>

        <!-- Grade das Novas Parcelas a Serem Emitidas -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="novas-parcelas">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Grade das Novas Parcelas a Serem Geradas ({{ count($simulacao['parcelas']) }})
                    </h2>
                </div>
                <div class="text-xs text-slate-300">
                    Ao confirmar, os títulos antigos serão baixados como RENEGOCIADOS e estas novas cobranças serão emitidas
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4 text-center" width="100">Parcela</th>
                            <th class="py-3 px-4" width="160">Data de Vencimento</th>
                            <th class="py-3 px-4 text-right" width="160">Valor da Parcela</th>
                            <th class="py-3 px-4">Observações Contábeis / Descrição</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($simulacao['parcelas'] as $p)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-800">
                                    {{ $p['numero_parcela'] }} / {{ $p['total_parcelas'] }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-semibold text-brand-petroleum">
                                    {{ $p['data_vencimento_formatada'] }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700">
                                    R$ {{ number_format($p['valor'], 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    Parcela resultante de renegociação e novação extrajudicial escolar
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Formulário de Confirmação Definitiva -->
            <form method="post" action="{{ route('financeiro.renegociacao.confirmar') }}" onsubmit="return confirm('ATENÇÃO: Ao confirmar, os títulos antigos serão liquidados como RENEGOCIADOS e as novas parcelas serão geradas imediatamente. Deseja prosseguir com a formalização?');" class="p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                @csrf
                @foreach($simulacao['titulos'] as $t)
                    <input type="hidden" name="titulos_ids[]" value="{{ $t->id }}">
                @endforeach
                <input type="hidden" name="desconto_valor" value="{{ $simulacao['desconto_concedido'] }}">
                <input type="hidden" name="qtd_parcelas" value="{{ $simulacao['qtd_parcelas'] }}">
                <input type="hidden" name="primeiro_vencimento" value="{{ $simulacao['parcelas'][0]['data_vencimento'] }}">

                <div class="text-xs text-slate-500">
                    O termo de confissão de dívida ficará disponível para download em PDF imediatamente após a confirmação.
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('financeiro.renegociacao.index') }}" class="px-4 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 btn-ieducar-action btn-ieducar-green">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Formalizar e Gerar Acordo &raquo;
                    </button>
                </div>
            </form>
        </section>

    </main>
</div>
@endsection
