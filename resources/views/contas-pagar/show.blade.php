@extends('financeiro::layouts.app')

@section('title', 'Despesa #' . $despesa->id)
@section('breadcrumb', 'Despesa #' . $despesa->id)
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="conta-pagar-show-container">
        
        @php
            $actionsHtml = '<div class="flex flex-wrap items-center gap-2">';

            if ($despesa->status === 'a_pagar') {
                $actionsHtml .= '
                    <button type="button" onclick="document.getElementById(\'modal-liquidar\').style.display=\'flex\';" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-green" title="Liquidar e debitar da conta bancária selecionada">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Liquidar Despesa
                    </button>
                    <a href="' . route('financeiro.contas-pagar.edit', $despesa->id) . '" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Editar
                    </a>';
            } elseif ($despesa->status === 'pago') {
                $actionsHtml .= '
                    <form method="POST" action="' . route('financeiro.contas-pagar.estornar', $despesa->id) . '" onsubmit="return confirm(\'Deseja realmente estornar a liquidação desta despesa? O saldo da conta bancária será recomposto.\');" style="display: inline;">
                        ' . csrf_field() . '
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-danger" title="Estorna o pagamento e recompõe o saldo da conta">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Estornar Liquidação
                        </button>
                    </form>';
            }

            $actionsHtml .= '
                <a href="' . route('financeiro.contas-pagar.index') . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar
                </a>
            </div>';
        @endphp

        @include('financeiro::partials.page-header', [
            'title' => 'Despesa #' . $despesa->id . ' — ' . $despesa->descricao,
            'subtitle' => 'Fornecedor: ' . ($despesa->fornecedor_nome ?? 'Não informado') . ' • Vencimento: ' . $despesa->data_vencimento->format('d/m/Y'),
            'breadcrumbs' => [
                ['label' => 'Contas a Pagar', 'url' => route('financeiro.contas-pagar.index')],
                ['label' => 'Despesa #' . $despesa->id]
            ],
            'actions' => $actionsHtml
        ])

        <!-- Status e Resumo Financeiro -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 border border-slate-200 shadow-xs">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Status Atual</span>
                @if($despesa->status === 'pago')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        Liquidada (Paga)
                    </span>
                @elseif($despesa->status === 'cancelado')
                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                        Cancelada
                    </span>
                @else
                    @if($despesa->data_vencimento->isPast())
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800 border border-red-200">
                            Vencida (A Pagar)
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                            A Pagar (Em Aberto)
                        </span>
                    @endif
                @endif
            </div>

            <div class="bg-white p-4 border border-slate-200 shadow-xs">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Valor Nominal</span>
                <span class="text-lg font-bold font-mono text-slate-900">
                    R$ {{ number_format($despesa->valor, 2, ',', '.') }}
                </span>
            </div>

            <div class="bg-white p-4 border border-slate-200 shadow-xs">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Valor Pago</span>
                <span class="text-lg font-bold font-mono {{ $despesa->valor_pago ? 'text-emerald-700' : 'text-slate-400' }}">
                    {{ $despesa->valor_pago ? 'R$ ' . number_format($despesa->valor_pago, 2, ',', '.') : '—' }}
                </span>
            </div>

            <div class="bg-white p-4 border border-slate-200 shadow-xs">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Conta Bancária / Caixa</span>
                <span class="text-sm font-semibold text-slate-800 block truncate">
                    {{ $despesa->contaBancaria?->nome ?? 'Não vinculada' }}
                </span>
            </div>
        </div>

        <!-- Detalhes do Lançamento -->
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                    Dados Cadastrais da Despesa
                </h2>
                <span class="text-xs text-slate-200">Lançamento #{{ $despesa->id }}</span>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Plano de Contas</span>
                    <span class="text-sm text-slate-800 font-medium">
                        {{ $despesa->planoConta ? ($despesa->planoConta->codigo . ' - ' . $despesa->planoConta->nome) : 'Não informado' }}
                    </span>
                </div>

                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Centro de Custo</span>
                    <span class="text-sm text-slate-800 font-medium">
                        {{ $despesa->centroCusto?->nome ?? 'Geral' }}
                    </span>
                </div>

                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Documento Fiscal</span>
                    <span class="text-sm text-slate-800 font-mono">
                        {{ $despesa->documento_fiscal ?? 'Nenhum' }}
                    </span>
                </div>

                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Data de Competência</span>
                    <span class="text-sm text-slate-800 font-mono">
                        {{ $despesa->data_competencia->format('d/m/Y') }}
                    </span>
                </div>

                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Data de Vencimento</span>
                    <span class="text-sm text-slate-800 font-mono">
                        {{ $despesa->data_vencimento->format('d/m/Y') }}
                    </span>
                </div>

                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Data de Liquidação</span>
                    <span class="text-sm text-slate-800 font-mono">
                        {{ $despesa->data_pagamento ? $despesa->data_pagamento->format('d/m/Y') : 'Pendente' }}
                    </span>
                </div>

                @if($despesa->observacoes)
                    <div class="md:col-span-3">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Observações</span>
                        <p class="text-sm text-slate-700 bg-slate-50 p-3 border border-slate-200">
                            {{ $despesa->observacoes }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Extrato e Movimentações Financeiras Bancárias -->
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <div class="bg-slate-100 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700" style="margin: 0;">
                        Histórico de Extrato Bancário e Movimentações
                    </h3>
                </div>
                <span class="text-xs text-slate-500 font-mono">{{ $movimentacoes->count() }} registro(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
                            <th class="py-2.5 px-4">Data</th>
                            <th class="py-2.5 px-4">Conta Bancária</th>
                            <th class="py-2.5 px-4">Tipo</th>
                            <th class="py-2.5 px-4 text-right">Valor</th>
                            <th class="py-2.5 px-4 text-right">Saldo Anterior</th>
                            <th class="py-2.5 px-4 text-right">Saldo Posterior</th>
                            <th class="py-2.5 px-4">Histórico / Descrição</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($movimentacoes as $mov)
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-2.5 px-4 font-mono text-slate-700">{{ $mov->data_movimentacao->format('d/m/Y') }}</td>
                                <td class="py-2.5 px-4 text-slate-800 font-medium">{{ $mov->contaBancaria?->nome ?? '—' }}</td>
                                <td class="py-2.5 px-4">
                                    @if($mov->tipo === 'saida')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-red-100 text-red-800">Saída</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">Entrada (Estorno)</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold {{ $mov->tipo === 'saida' ? 'text-red-700' : 'text-emerald-700' }}">
                                    {{ $mov->tipo === 'saida' ? '-' : '+' }} R$ {{ number_format($mov->valor, 2, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono text-slate-500">R$ {{ number_format($mov->saldo_anterior, 2, ',', '.') }}</td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-800">R$ {{ number_format($mov->saldo_posterior, 2, ',', '.') }}</td>
                                <td class="py-2.5 px-4 text-slate-600">{{ $mov->descricao }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-slate-400">
                                    Nenhuma movimentação bancária registrada para este lançamento até o momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Modal de Liquidação / Pagamento -->
    <div id="modal-liquidar" style="display: none;" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-none border border-slate-300 shadow-xl max-w-lg w-full overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3.5 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h3 class="text-sm font-semibold tracking-wide text-white" style="margin: 0;">
                        Liquidar Conta a Pagar
                    </h3>
                </div>
                <button type="button" onclick="document.getElementById('modal-liquidar').style.display='none';" class="text-white hover:text-slate-200 text-lg font-bold leading-none">&times;</button>
            </div>
            <form method="POST" action="{{ route('financeiro.contas-pagar.liquidar', $despesa->id) }}" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="data_pagamento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Data do Pagamento <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="data_pagamento" id="data_pagamento" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum">
                    </div>

                    <div>
                        <label for="conta_bancaria_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Conta a Debitar <span class="text-red-500">*</span>
                        </label>
                        <select name="conta_bancaria_id" id="conta_bancaria_id" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum">
                            @foreach($contasBancarias as $cb)
                                <option value="{{ $cb->id }}" {{ $despesa->conta_bancaria_id == $cb->id ? 'selected' : '' }}>
                                    {{ $cb->nome }} (Saldo: R$ {{ number_format($cb->saldo_atual, 2, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="valor_pago" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Valor Pago (R$) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="valor_pago" id="valor_pago" value="{{ $despesa->valor }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono font-bold focus:outline-none focus:border-brand-petroleum">
                    </div>

                    <div>
                        <label for="forma_pagamento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Forma de Pagamento
                        </label>
                        <select name="forma_pagamento" id="forma_pagamento" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum">
                            <option value="transferencia">Transferência Bancária</option>
                            <option value="pix">Pix</option>
                            <option value="boleto">Boleto / Compensação</option>
                            <option value="dinheiro">Dinheiro (Caixa Físico)</option>
                            <option value="ted">TED / DOC</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div>
                        <label for="valor_juros" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Juros / Encargos (R$)
                        </label>
                        <input type="number" step="0.01" min="0" name="valor_juros" id="valor_juros" value="0.00" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum">
                    </div>

                    <div>
                        <label for="valor_desconto" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Desconto Obtido (R$)
                        </label>
                        <input type="number" step="0.01" min="0" name="valor_desconto" id="valor_desconto" value="0.00" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-liquidar').style.display='none';" class="px-4 py-2 btn-ieducar-action btn-ieducar-outline">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 btn-ieducar-action btn-ieducar-green">
                        Confirmar Baixa Bancária
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
