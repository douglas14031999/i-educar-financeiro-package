@extends('financeiro::layouts.app')

@section('title', 'Contrato ' . $contrato->numero_contrato)
@section('breadcrumb', 'Contrato ' . $contrato->numero_contrato)
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="contrato-show-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Contrato ' . $contrato->numero_contrato,
            'subtitle' => 'Matrícula #' . $contrato->matricula_id . ' • Aluno #' . $contrato->aluno_id . ' • Ano Letivo ' . $contrato->ano_letivo,
            'breadcrumbs' => [
                ['label' => 'Contratos', 'url' => route('financeiro.contratos.index')],
                ['label' => 'Contrato #' . $contrato->numero_contrato]
            ],
            'actions' => '
                <a href="' . route('financeiro.contratos.imprimir-carne', $contrato->id) . '" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Imprimir carnê completo com todas as parcelas deste contrato">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Imprimir Carnê do Aluno
                </a>
                ' . ($contrato->status === 'ativo' ? '
                    <button type="button" onclick="document.getElementById(\'modal-cancelar\').style.display=\'flex\';" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-danger">
                        <svg class="w-4 h-4 text-red-600" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Rescindir / Cancelar
                    </button>
                ' : '') . '
                <a href="' . route('financeiro.contratos.index') . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar
                </a>
            '
        ])

        <!-- Informações Consolidadas do Contrato -->
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Ficha Cadastral do Contrato
                    </h2>
                </div>
                <span class="text-xs text-slate-300 font-mono">
                    ID #{{ $contrato->id }}
                </span>
            </div>

            <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Responsável Principal</div>
                    <div class="font-semibold text-slate-900 mt-1 text-sm">
                        @if($contrato->responsavel)
                            <a href="{{ route('financeiro.responsaveis.show', $contrato->responsavel_financeiro_id) }}" class="text-brand-petroleum hover:underline" style="text-decoration: none;">
                                {{ $contrato->responsavel->nome }}
                            </a>
                        @else
                            Não informado
                        @endif
                    </div>
                    @if(optional($contrato->responsavel)->cpf_cnpj_formatado)
                        <div class="text-xs text-slate-400 font-mono mt-0.5">
                            CPF: {{ $contrato->responsavel->cpf_cnpj_formatado }}
                        </div>
                    @endif
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Plano Vinculado</div>
                    <div class="font-semibold text-slate-900 mt-1 text-sm">
                        {{ $contrato->plano->nome ?? '-' }}
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        Vencimento: todo dia {{ sprintf('%02d', $contrato->dia_vencimento) }}
                    </div>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Valor Bruto / Descontos</div>
                    <div class="text-sm text-slate-700 mt-1 font-mono">
                        Bruto: R$ {{ number_format($contrato->valor_total_bruto, 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-slate-500 font-mono mt-0.5">
                        Desc: R$ {{ number_format($contrato->valor_total_descontos, 2, ',', '.') }}
                    </div>
                </div>

                <div class="p-3 bg-emerald-50/60 border border-emerald-200">
                    <div class="text-[11px] font-semibold text-emerald-800 uppercase tracking-wider">Valor Líquido Contratado</div>
                    <div class="text-lg font-bold text-emerald-700 mt-1 font-mono">
                        R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}
                    </div>
                    <div class="text-[11px] text-emerald-600 mt-0.5">
                        {{ $contrato->titulos->count() }} parcelas geradas
                    </div>
                </div>
            </div>

            @if($contrato->motivo_cancelamento)
                <div class="px-5 pb-5">
                    <div class="p-3 bg-red-50 border border-red-200 text-red-800 text-xs">
                        <span class="font-semibold">Motivo do Cancelamento:</span> {{ $contrato->motivo_cancelamento }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Grade de Parcelas e Títulos do Contrato -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="parcelas-section">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Grade de Parcelas e Cobranças Geradas ({{ $contrato->titulos->count() }})
                    </h2>
                </div>
                <div class="text-xs text-slate-300">
                    Clique em "Baixar" para registrar liquidação imediata no balcão
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4 text-center">Parcela</th>
                            <th class="py-3 px-4">Vencimento</th>
                            <th class="py-3 px-4">Quem Paga (Responsável)</th>
                            <th class="py-3 px-4">Descrição / Tipo</th>
                            <th class="py-3 px-4 text-right">Valor Saldo</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($contrato->titulos as $titulo)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4 text-center font-bold font-mono text-slate-800">
                                    {{ $titulo->numero_parcela == 0 ? 'Matrícula' : sprintf('%02d/%02d', $titulo->numero_parcela, $titulo->total_parcelas) }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-700">
                                    {{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-900">
                                        {{ $titulo->responsavel->nome ?? '-' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ $titulo->observacoes ?? ucfirst($titulo->tipo) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($titulo->status === 'pago')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Pago
                                        </span>
                                    @elseif($titulo->status === 'parcial')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Parcial
                                        </span>
                                    @elseif($titulo->status === 'vencido')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            Vencido
                                        </span>
                                    @elseif($titulo->status === 'cancelado')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            Cancelado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            Aberto
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if($titulo->status === 'aberto' || $titulo->status === 'parcial' || $titulo->status === 'vencido')
                                            <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 btn-ieducar-action btn-ieducar-green" title="Baixa no Balcão" style="text-decoration: none;">
                                                <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                                Baixar
                                            </a>
                                        @elseif($titulo->status === 'pago')
                                            <a href="{{ route('financeiro.titulos.recibo-pdf', $titulo->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium border border-slate-200 transition" title="Imprimir Recibo" target="_blank" style="text-decoration: none;">
                                                <svg class="w-3.5 h-3.5 text-slate-500" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                                Recibo
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400 font-mono">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    Nenhuma parcela gerada para este contrato.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Modal de Cancelamento do Contrato -->
        <div id="modal-cancelar" style="display: none;" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white max-w-lg w-full border border-slate-200 shadow-xl overflow-hidden animate-fade-in">
                <div class="bg-gradient-to-r from-red-600 to-red-700 px-5 py-3.5 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <h3 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                            Confirmar Cancelamento do Contrato
                        </h3>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-cancelar').style.display='none';" class="text-red-200 hover:text-white">
                        <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </button>
                </div>
                <form method="post" action="{{ route('financeiro.contratos.cancelar', $contrato->id) }}" class="p-6 space-y-4">
                    @csrf
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Ao cancelar este contrato, <strong>apenas as parcelas futuras não vencidas</strong> serão canceladas. Parcelas já liquidadas ou débitos em atraso permanecerão no histórico.
                    </p>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Motivo do Cancelamento <span class="text-red-500">*</span>
                        </label>
                        <textarea name="motivo" required rows="3" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition" placeholder="Ex: Transferência de escola solicitada pela família..."></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" onclick="document.getElementById('modal-cancelar').style.display='none';" class="px-4 py-2 btn-ieducar-action btn-ieducar-outline">
                            Voltar
                        </button>
                        <button type="submit" class="px-4 py-2 btn-ieducar-action btn-ieducar-danger">
                            Confirmar Cancelamento
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>
@endsection
