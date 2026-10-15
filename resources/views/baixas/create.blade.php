@extends('financeiro::layouts.app')

@section('title', 'Baixa de Balcão (Caixa / Secretaria)')
@section('breadcrumb', 'Baixa de Balcão')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="baixa-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Recebimento Presencial no Caixa Escolar',
            'subtitle' => 'Liquidação ágil com busca instantânea, cálculo automático de encargos e emissão imediata de recibo',
            'breadcrumbs' => [
                ['label' => 'Títulos', 'url' => route('financeiro.titulos.index')],
                ['label' => 'Baixa no Caixa']
            ],
            'actions' => '
                <a href="' . route('financeiro.titulos.index') . '" class="inline-flex items-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4 text-slate-300" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar para Mensalidades
                </a>
            '
        ])

        @if(!$titulo)
            <!-- Seletor de Título em Aberto com Busca Ágil Instantânea -->
            <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-container">
                <!-- Cabeçalho com Gradiente Petróleo -->
                <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                            Localizar Mensalidade ou Parcela no Caixa
                        </h2>
                    </div>
                    <span id="contador-parcelas-topo" class="text-xs font-medium text-cyan-100">
                        {{ $titulosAbertos->count() }} parcelas listadas
                    </span>
                </div>
                
                <!-- Barra de Busca Rápida Instantânea (FEAT-02) -->
                <div class="p-4 bg-slate-50 border-b border-slate-200">
                    <div class="relative max-w-2xl">
                        <input type="text" id="campo-busca-caixa" autofocus autocomplete="off"
                            class="w-full pl-10 pr-10 py-2.5 bg-white border border-slate-300 rounded text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition shadow-2xs"
                            placeholder="Digite o nome do aluno, responsável, CPF, nº contrato ou ID da parcela...">
                        <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <div id="indicador-carregando" class="hidden absolute right-3 top-3">
                            <svg class="animate-spin h-5 w-5 text-brand-petroleum" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-[11px] text-slate-500">
                        <span>Dica: Pressione <strong>Enter</strong> ou clique em <strong>"Receber"</strong> para ir direto ao pagamento</span>
                        <span id="feedback-busca" class="text-slate-600 font-medium"></span>
                    </div>
                </div>

                <!-- Tabela de Títulos -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                        <thead>
                            <tr class="bg-slate-100/90 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4" scope="col">Parcela</th>
                                <th class="py-3 px-4" scope="col">Responsável Financeiro</th>
                                <th class="py-3 px-4" scope="col">Aluno / Descrição</th>
                                <th class="py-3 px-4" scope="col">Vencimento</th>
                                <th class="py-3 px-4 text-right" scope="col">Saldo Principal</th>
                                <th class="py-3 px-4 text-right" scope="col">Total a Receber</th>
                                <th class="py-3 px-4 text-center" scope="col">Status</th>
                                <th class="py-3 px-4 text-center" scope="col">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="tabela-corpo-titulos" class="divide-y divide-slate-200 text-slate-700">
                            @forelse($titulosAbertos as $t)
                                @php
                                    $isAtrasado = ($t->status === 'vencido' || $t->isVencido());
                                    $bgClass = $isAtrasado ? 'hover:bg-rose-50/40 bg-rose-50/20' : 'hover:bg-slate-50/80';
                                    $calc = \iEducar\Financeiro\Support\CalculoEncargos::calcularTotais($t);
                                    $desc = app(\iEducar\Financeiro\Services\DescontoService::class)->calcularDescontoPontualidade($t, \Carbon\Carbon::today());
                                    $totRec = round(max(0, $calc['total_devido'] - $desc), 2);
                                @endphp
                                <tr class="{{ $bgClass }} transition-colors">
                                    <td class="py-3 px-4 whitespace-nowrap font-bold {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                                        {{ $t->numero_parcela == 0 ? 'Matr.' : sprintf('%02d/%02d', $t->numero_parcela, $t->total_parcelas) }}
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900">
                                        {{ $t->responsavel->nome ?? '-' }}
                                        @if(!empty($t->responsavel->cpf_cnpj_formatado))
                                            <span class="block text-[11px] text-slate-500 font-normal">CPF: {{ $t->responsavel->cpf_cnpj_formatado }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-600">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 mr-1.5">
                                            Aluno #{{ $t->aluno_id }}
                                        </span>
                                        {{ $t->observacoes }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap font-medium {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                                        {{ \Carbon\Carbon::parse($t->data_vencimento)->format('d/m/Y') }}
                                        @if($isAtrasado)
                                            <span class="block text-[10px] text-rose-500 font-semibold">{{ \Carbon\Carbon::parse($t->data_vencimento)->diffInDays() }} dias atrasado</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right font-medium text-slate-600">
                                        R$ {{ number_format($t->valor_saldo, 2, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold {{ $isAtrasado ? 'text-rose-700' : 'text-brand-petroleum' }}">
                                        R$ {{ number_format($totRec, 2, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        @if($isAtrasado)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Em Atraso</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200/70">Pendente</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $t->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;">
                                            <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                            Receber
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-slate-500">
                                        Não há títulos com saldo em aberto no momento.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Rodapé Informativo -->
                <footer class="p-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                    <span>Lista inicial ordenada por data de vencimento. Utilize a busca acima para filtrar rapidamente qualquer título.</span>
                </footer>
            </section>
        @else
            <!-- Formulário de Baixa com o Título Selecionado -->
            <div class="space-y-6">
                <!-- Card 1: Detalhes da Cobrança e Responsável -->
                <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="details-card">
                    <div class="bg-[#47728f] px-5 py-3.5 flex items-center justify-between text-white">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                                Parcela Selecionada: #{{ $titulo->id }} ({{ $titulo->numero_parcela == 0 ? 'Matrícula' : sprintf('%02d/%02d', $titulo->numero_parcela, $titulo->total_parcelas) }} - {{ $titulo->competencia }})
                            </h2>
                        </div>
                        <a href="{{ route('financeiro.baixas.create') }}" class="text-xs font-medium text-cyan-100 hover:text-white transition flex items-center gap-1" style="text-decoration: none;">
                            ← Trocar Parcela / Nova Busca
                        </a>
                    </div>

                    <div class="p-5 md:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Aluno</span>
                            <span class="text-sm font-semibold text-slate-900">Aluno #{{ $titulo->aluno_id }}</span>
                            @if($titulo->contrato)
                                <span class="block text-xs text-slate-500">Contrato: {{ $titulo->contrato->numero_contrato }}</span>
                            @endif
                        </div>

                        <div class="p-3 bg-slate-50 border border-slate-200 rounded">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Responsável Financeiro</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $titulo->responsavel->nome ?? '-' }}</span>
                            @if(!empty($titulo->responsavel->cpf_cnpj_formatado))
                                <span class="block text-xs text-slate-500">CPF: {{ $titulo->responsavel->cpf_cnpj_formatado }}</span>
                            @endif
                        </div>

                        <div class="p-3 bg-slate-50 border border-slate-200 rounded">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Vencimento Original</span>
                            <span class="text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</span>
                            @if($titulo->isVencido())
                                <span class="block text-xs font-semibold text-rose-600">Vencido há {{ $calculo['dias_atraso'] }} dias</span>
                            @else
                                <span class="block text-xs font-medium text-emerald-600">Em dia</span>
                            @endif
                        </div>

                        <div class="p-3 bg-slate-50 border border-slate-200 rounded">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Créditos do Responsável</span>
                            <span class="text-sm font-bold {{ $creditoDisponivel > 0 ? 'text-emerald-700' : 'text-slate-600' }}">
                                R$ {{ number_format($creditoDisponivel, 2, ',', '.') }}
                            </span>
                            @if($creditoDisponivel > 0)
                                <span class="block text-[10px] text-emerald-600">Disponível para abater</span>
                            @endif
                        </div>
                    </div>

                    <!-- Resumo Financeiro Dinâmico -->
                    <div class="mx-5 mb-5 p-4 bg-slate-100/90 border border-slate-200 rounded flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-6 flex-wrap text-xs">
                            <div>
                                <span class="text-slate-500 block">Saldo Principal:</span>
                                <strong class="text-slate-800 text-sm">R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block">(+) Multa por Atraso:</span>
                                <strong id="resumo-multa" class="text-rose-700 text-sm">R$ {{ number_format($calculo['multa'] ?? 0, 2, ',', '.') }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block">(+) Juros por Atraso:</span>
                                <strong id="resumo-juros" class="text-rose-700 text-sm">R$ {{ number_format($calculo['juros'] ?? 0, 2, ',', '.') }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block">(-) Desconto Pontualidade:</span>
                                <strong id="resumo-desconto" class="text-emerald-700 text-sm">R$ {{ number_format($descontoPontualidade ?? 0, 2, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-xs uppercase tracking-wider text-slate-600 font-bold block">Total a Receber:</span>
                            <span id="resumo-total-receber" class="text-2xl font-black text-brand-petroleum">
                                R$ {{ number_format($totalAReceber, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Card 2: Formulário de Recebimento no Caixa -->
                <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="form-container">
                    <div class="bg-[#47728f] px-5 py-3.5 text-white">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                                Efetivação do Recebimento e Forma de Pagamento
                            </h2>
                        </div>
                    </div>

                    <form id="form-baixa-caixa" method="post" action="{{ route('financeiro.baixas.store') }}" class="p-5 md:p-6 space-y-6">
                        @csrf
                        <input type="hidden" name="titulo_id" id="titulo_id_val" value="{{ $titulo->id }}">
                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                            <!-- Valor Pago (pré-preenchido com o total a receber, não só o saldo!) -->
                            <div>
                                <label for="valor_pago" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Valor a Quitar / Receber (R$) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" name="valor_pago" id="valor_pago" step="0.01" min="0.01"
                                    value="{{ old('valor_pago', number_format($totalAReceber, 2, '.', '')) }}" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-900 text-lg font-black focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                <span class="text-[11px] text-slate-500 mt-1 block">Pré-preenchido com o valor total devido calculado</span>
                            </div>

                            <!-- Forma de Pagamento -->
                            <div>
                                <label for="forma_pagamento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Forma de Pagamento <span class="text-rose-500">*</span>
                                </label>
                                <select name="forma_pagamento" id="forma_pagamento" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                    <option value="dinheiro" {{ old('forma_pagamento') === 'dinheiro' ? 'selected' : '' }}>Dinheiro (Espécie)</option>
                                    <option value="pix" {{ old('forma_pagamento') === 'pix' ? 'selected' : '' }}>Pix (Chave / QR Code)</option>
                                    <option value="cartao_debito" {{ old('forma_pagamento') === 'cartao_debito' ? 'selected' : '' }}>Cartão de Débito</option>
                                    <option value="cartao_credito" {{ old('forma_pagamento') === 'cartao_credito' ? 'selected' : '' }}>Cartão de Crédito</option>
                                    <option value="transferencia" {{ old('forma_pagamento') === 'transferencia' ? 'selected' : '' }}>Transferência / TED</option>
                                    <option value="cheque" {{ old('forma_pagamento') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                                </select>
                            </div>

                            <!-- Data de Pagamento -->
                            <div>
                                <label for="data_pagamento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Data do Recebimento <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" name="data_pagamento" id="data_pagamento"
                                    max="{{ date('Y-m-d') }}"
                                    value="{{ old('data_pagamento', date('Y-m-d')) }}" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                <span class="text-[11px] text-slate-500 mt-1 block">Recalcula juros/multa automaticamente ao mudar a data</span>
                            </div>

                            <!-- Desconto Concedido -->
                            <div>
                                <label for="valor_desconto_concedido" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Desconto Concedido (R$)
                                </label>
                                <input type="number" name="valor_desconto_concedido" id="valor_desconto_concedido" step="0.01" min="0"
                                    value="{{ old('valor_desconto_concedido', number_format($descontoPontualidade ?? 0, 2, '.', '')) }}"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            </div>

                            <!-- Juros Cobrados -->
                            <div>
                                <label for="valor_juros" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Juros Cobrados (R$)
                                </label>
                                <input type="number" name="valor_juros" id="valor_juros" step="0.01" min="0"
                                    value="{{ old('valor_juros', number_format($calculo['juros'] ?? 0, 2, '.', '')) }}"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            </div>

                            <!-- Multa Cobrada -->
                            <div>
                                <label for="valor_multa" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Multa Cobrada (R$)
                                </label>
                                <input type="number" name="valor_multa" id="valor_multa" step="0.01" min="0"
                                    value="{{ old('valor_multa', number_format($calculo['multa'] ?? 0, 2, '.', '')) }}"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            </div>
                        </div>

                        <!-- Painel Dinâmico de Troco / Dinheiro em Espécie (FEAT-02) -->
                        <div id="painel-dinheiro" class="p-4 bg-emerald-50/70 border border-emerald-200 rounded space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-900 m-0">
                                💵 Recebimento em Espécie e Troco
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                                <div>
                                    <label for="valor_entregue" class="block text-xs font-semibold text-emerald-800 mb-1">
                                        Valor Entregue pelo Cliente (R$)
                                    </label>
                                    <input type="number" id="valor_entregue" step="0.01" min="0" placeholder="0.00"
                                        class="w-full px-3 py-2 bg-white border border-emerald-300 text-emerald-950 font-bold text-base focus:outline-none focus:border-emerald-600">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-emerald-800 mb-1">
                                        Troco a Devolver
                                    </label>
                                    <div id="campo-troco-calculado" class="text-lg font-black text-emerald-800 bg-white px-3 py-2 border border-emerald-300 rounded">
                                        R$ 0,00
                                    </div>
                                </div>
                                <div class="pt-4">
                                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-emerald-900">
                                        <input type="checkbox" name="devolver_troco" id="devolver_troco" value="1" checked class="rounded border-emerald-400 text-emerald-600 focus:ring-emerald-500">
                                        <span>Devolver troco ao pagador (se desmarcado, vira crédito)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Observações -->
                        <div>
                            <label for="observacoes" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Observações do Recebimento (Opcional)
                            </label>
                            <input type="text" name="observacoes" id="observacoes" maxlength="500" value="{{ old('observacoes') }}"
                                placeholder="Ex: Pago no balcão da secretaria pelo responsável..."
                                class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <!-- Botões de Ação com Proteção contra Duplo Submit -->
                        <div class="pt-5 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                            <a href="{{ route('financeiro.baixas.create') }}" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium transition shadow-2xs" style="text-decoration: none;">
                                Cancelar
                            </a>
                            <button type="submit" id="btn-confirmar-baixa" class="inline-flex items-center gap-2 px-6 py-2.5 btn-ieducar-action btn-ieducar-green font-bold text-sm cursor-pointer shadow-xs">
                                <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                <span>Confirmar Recebimento e Emitir Recibo (F2)</span>
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        @endif

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Busca Instantânea de Títulos no Caixa (FEAT-02)
    const campoBusca = document.getElementById('campo-busca-caixa');
    const tabelaCorpo = document.getElementById('tabela-corpo-titulos');
    const indicadorCarregando = document.getElementById('indicador-carregando');
    const contadorTopo = document.getElementById('contador-parcelas-topo');
    const feedbackBusca = document.getElementById('feedback-busca');
    let debounceTimer = null;

    if (campoBusca) {
        campoBusca.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const termo = this.value.trim();

            debounceTimer = setTimeout(() => {
                if (indicadorCarregando) indicadorCarregando.classList.remove('hidden');

                fetch("{{ route('financeiro.api.titulos.buscar') }}?q=" + encodeURIComponent(termo))
                    .then(res => res.json())
                    .then(data => {
                        if (indicadorCarregando) indicadorCarregando.classList.add('hidden');
                        renderizarTitulos(data.data || []);
                    })
                    .catch(err => {
                        if (indicadorCarregando) indicadorCarregando.classList.add('hidden');
                        console.error('Erro na busca:', err);
                    });
            }, 250);
        });

        function renderizarTitulos(titulos) {
            if (!tabelaCorpo) return;
            if (contadorTopo) contadorTopo.textContent = titulos.length + ' parcelas localizadas';
            if (feedbackBusca) feedbackBusca.textContent = titulos.length > 0 ? titulos.length + ' parcelas encontradas' : 'Nenhuma parcela localizada';

            if (titulos.length === 0) {
                tabelaCorpo.innerHTML = `
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-500">
                            Nenhum título encontrado com o termo pesquisado. Verifique os dados digitados.
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            titulos.forEach(t => {
                const bgClass = t.is_vencido ? 'hover:bg-rose-50/40 bg-rose-50/20' : 'hover:bg-slate-50/80';
                const statusBadge = t.is_vencido 
                    ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Em Atraso</span>'
                    : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200/70">Pendente</span>';

                html += `
                    <tr class="${bgClass} transition-colors">
                        <td class="py-3 px-4 whitespace-nowrap font-bold ${t.is_vencido ? 'text-rose-700' : 'text-slate-900'}">
                            ${t.numero_parcela}
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            ${t.responsavel}
                            ${t.cpf ? `<span class="block text-[11px] text-slate-500 font-normal">CPF: ${t.cpf}</span>` : ''}
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 mr-1.5">
                                Aluno #${t.aluno_id}
                            </span>
                            ${t.observacoes}
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap font-medium ${t.is_vencido ? 'text-rose-700' : 'text-slate-900'}">
                            ${t.data_vencimento}
                            ${t.is_vencido ? `<span class="block text-[10px] text-rose-500 font-semibold">${t.dias_atraso} dias atrasado</span>` : ''}
                        </td>
                        <td class="py-3 px-4 text-right font-medium text-slate-600">
                            R$ ${t.valor_saldo.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                        </td>
                        <td class="py-3 px-4 text-right font-bold ${t.is_vencido ? 'text-rose-700' : 'text-brand-petroleum'}">
                            R$ ${t.total_receber.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            ${statusBadge}
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            <a href="${t.url_selecionar}" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;">
                                <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Receber
                            </a>
                        </td>
                    </tr>
                `;
            });
            tabelaCorpo.innerHTML = html;
        }
    }

    // 2. Comportamentos do Formulário de Baixa Selecionada
    const formaPagamentoSelect = document.getElementById('forma_pagamento');
    const painelDinheiro = document.getElementById('painel-dinheiro');
    const inputValorPago = document.getElementById('valor_pago');
    const inputValorEntregue = document.getElementById('valor_entregue');
    const campoTroco = document.getElementById('campo-troco-calculado');
    const inputDataPagamento = document.getElementById('data_pagamento');
    const inputTituloId = document.getElementById('titulo_id_val');
    const formBaixa = document.getElementById('form-baixa-caixa');
    const btnSubmit = document.getElementById('btn-confirmar-baixa');

    // Alterna visibilidade do painel de troco se a forma for dinheiro
    if (formaPagamentoSelect && painelDinheiro) {
        function verificarForma() {
            if (formaPagamentoSelect.value === 'dinheiro') {
                painelDinheiro.classList.remove('hidden');
            } else {
                painelDinheiro.classList.add('hidden');
            }
        }
        formaPagamentoSelect.addEventListener('change', verificarForma);
        verificarForma();
    }

    // Cálculo dinâmico de troco
    function calcularTroco() {
        if (!inputValorPago || !inputValorEntregue || !campoTroco) return;
        const pago = parseFloat(inputValorPago.value || 0);
        const entregue = parseFloat(inputValorEntregue.value || 0);
        const troco = Math.max(0, entregue - pago);
        campoTroco.textContent = 'R$ ' + troco.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    if (inputValorEntregue) {
        inputValorEntregue.addEventListener('input', calcularTroco);
    }
    if (inputValorPago) {
        inputValorPago.addEventListener('input', calcularTroco);
    }

    // Recálculo ao mudar a data de pagamento
    if (inputDataPagamento && inputTituloId) {
        inputDataPagamento.addEventListener('change', function () {
            const data = this.value;
            const tituloId = inputTituloId.value;
            if (!data || !tituloId) return;

            fetch("{{ url('/financeiro/api/titulos') }}/" + tituloId + "/calculo?data=" + encodeURIComponent(data))
                .then(res => res.json())
                .then(d => {
                    const inputJuros = document.getElementById('valor_juros');
                    const inputMulta = document.getElementById('valor_multa');
                    const inputDesc = document.getElementById('valor_desconto_concedido');
                    const resJuros = document.getElementById('resumo-juros');
                    const resMulta = document.getElementById('resumo-multa');
                    const resDesc = document.getElementById('resumo-desconto');
                    const resTotal = document.getElementById('resumo-total-receber');

                    if (inputJuros) inputJuros.value = d.juros.toFixed(2);
                    if (inputMulta) inputMulta.value = d.multa.toFixed(2);
                    if (inputDesc) inputDesc.value = d.desconto_pontualidade.toFixed(2);
                    if (inputValorPago) inputValorPago.value = d.total_receber.toFixed(2);

                    if (resJuros) resJuros.textContent = 'R$ ' + d.juros.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    if (resMulta) resMulta.textContent = 'R$ ' + d.multa.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    if (resDesc) resDesc.textContent = 'R$ ' + d.desconto_pontualidade.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    if (resTotal) resTotal.textContent = 'R$ ' + d.total_receber.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                    calcularTroco();
                })
                .catch(err => console.error('Erro ao recalcular encargos:', err));
        });
    }

    // Proteção contra duplo submit
    if (formBaixa && btnSubmit) {
        formBaixa.addEventListener('submit', function () {
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-75', 'cursor-not-allowed');
            const spanText = btnSubmit.querySelector('span');
            if (spanText) spanText.textContent = 'Registrando baixa no caixa...';
        });
    }

    // Atalho de teclado F2 para submeter
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2' && formBaixa && btnSubmit && !btnSubmit.disabled) {
            e.preventDefault();
            formBaixa.submit();
        }
    });
});
</script>
@endsection
