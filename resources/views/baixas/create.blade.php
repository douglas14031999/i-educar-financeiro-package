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
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Recebimento Presencial no Caixa Escolar
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Liquidação presencial e emissão de recibos para mensalidades e parcelas em aberto
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('financeiro.titulos.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-800 active:bg-slate-900 text-white text-sm font-medium shadow-sm transition duration-150" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4 text-slate-300" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar para Mensalidades
                </a>
            </div>
        </header>

        @if(!$titulo)
            <!-- Seletor de Título em Aberto -->
            <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-container">
                <!-- Cabeçalho com Gradiente Petróleo -->
                <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                            Selecione a Mensalidade ou Parcela a Baixar
                        </h2>
                    </div>
                    <span class="text-xs font-medium text-cyan-100">
                        {{ $titulosAbertos->count() }} parcelas aguardando acerto
                    </span>
                </div>
                
                <!-- Barra de Instrução -->
                <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
                    <span>Clique em <strong>"Selecionar"</strong> na parcela desejada para informar o valor recebido e a forma de pagamento</span>
                </div>

                <!-- Tabela Responsiva -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                        <thead>
                            <tr class="bg-slate-100/90 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4" scope="col">Parcela</th>
                                <th class="py-3 px-4" scope="col">Responsável Financeiro</th>
                                <th class="py-3 px-4" scope="col">Aluno / Descrição</th>
                                <th class="py-3 px-4" scope="col">Vencimento</th>
                                <th class="py-3 px-4 text-right" scope="col">Saldo Devedor</th>
                                <th class="py-3 px-4 text-center" scope="col">Status</th>
                                <th class="py-3 px-4 text-center" scope="col">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-700">
                            @forelse($titulosAbertos as $t)
                                @php
                                    $isAtrasado = ($t->status === 'vencido' || $t->isVencido());
                                    $bgClass = $isAtrasado ? 'hover:bg-rose-50/40 bg-rose-50/20' : 'hover:bg-slate-50/80';
                                @endphp
                                <tr class="{{ $bgClass }} transition-colors">
                                    <td class="py-3 px-4 whitespace-nowrap font-bold {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                                        {{ $t->numero_parcela == 0 ? 'Matr.' : sprintf('%02d/%02d', $t->numero_parcela, $t->total_parcelas) }}
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900">
                                        {{ $t->responsavel->nome ?? '-' }}
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
                                    <td class="py-3 px-4 text-right font-bold {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                                        R$ {{ number_format($t->valor_saldo, 2, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        @if($isAtrasado)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Em Atraso</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200/70">Pendente</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $t->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold shadow-xs hover:shadow transition duration-150" style="text-decoration: none;">
                                            <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                            Selecionar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-500">
                                        <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        Não há títulos com saldo em aberto no momento.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Rodapé -->
                <footer class="p-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                    <span>Mostrando até 50 parcelas com saldo pendente ordenadas por vencimento</span>
                </footer>
            </section>
        @else
            <!-- Formulário de Baixa com o Título Selecionado -->
            <div class="space-y-6">
                <!-- Card 1: Dados da Parcela Selecionada -->
                <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="details-card">
                    <div class="bg-[#47728f] px-5 py-3.5 flex items-center justify-between text-white">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                                Dados da Parcela Selecionada (#{{ $titulo->id }})
                            </h2>
                        </div>
                        <a href="{{ route('financeiro.baixas.create') }}" class="text-xs font-medium text-cyan-100 hover:text-white transition flex items-center gap-1" style="text-decoration: none;">
                            ← Trocar Parcela
                        </a>
                    </div>

                    <div class="p-5 md:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-3 bg-slate-50 border border-slate-200">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Aluno</span>
                            <span class="text-sm font-semibold text-slate-900">Aluno #{{ $titulo->aluno_id }}</span>
                        </div>

                        <div class="p-3 bg-slate-50 border border-slate-200">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Responsável Financeiro</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $titulo->responsavel->nome ?? '-' }}</span>
                            @if(!empty($titulo->responsavel->cpf_cnpj_formatado))
                                <span class="block text-xs text-slate-500">CPF: {{ $titulo->responsavel->cpf_cnpj_formatado }}</span>
                            @endif
                        </div>

                        <div class="p-3 bg-slate-50 border border-slate-200">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Vencimento Original</span>
                            <span class="text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</span>
                            @if($titulo->isVencido())
                                <span class="block text-xs font-semibold text-rose-600">Vencido há {{ $titulo->dias_atraso }} dias</span>
                            @endif
                        </div>

                        <div class="p-3 bg-slate-50 border border-slate-200">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Saldo Devedor</span>
                            <span class="text-lg font-bold text-brand-petroleum">R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    @if($titulo->isVencido())
                        <div class="mx-5 mb-5 p-3 bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-center justify-between flex-wrap gap-2">
                            <span>
                                <strong>Aviso de Atraso:</strong> Esta parcela está vencida. Sugestão com juros/multa calculados:
                                <strong>R$ {{ number_format($titulo->valor_atualizado, 2, ',', '.') }}</strong>
                            </span>
                        </div>
                    @endif
                </section>

                <!-- Card 2: Formulário de Recebimento -->
                <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="form-container">
                    <div class="bg-[#47728f] px-5 py-3.5 text-white">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                                Dados do Recebimento no Caixa
                            </h2>
                        </div>
                    </div>

                    <form id="formcadastro" method="post" action="{{ route('financeiro.baixas.store') }}" class="p-5 md:p-6 space-y-5">
                        @csrf
                        <input type="hidden" name="titulo_id" value="{{ $titulo->id }}">

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Valor Pago -->
                            <div>
                                <label for="valor_pago" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Valor Pago (R$) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" name="valor_pago" id="valor_pago" step="0.01" min="0.01" value="{{ old('valor_pago', $titulo->valor_saldo) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-900 text-base font-bold focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            </div>

                            <!-- Forma de Pagamento -->
                            <div>
                                <label for="forma_pagamento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Forma de Pagamento <span class="text-rose-500">*</span>
                                </label>
                                <select name="forma_pagamento" id="forma_pagamento" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                    <option value="dinheiro">Dinheiro (Espécie)</option>
                                    <option value="pix">Pix (Chave da Escola)</option>
                                    <option value="cartao_debito">Cartão de Débito</option>
                                    <option value="cartao_credito">Cartão de Crédito</option>
                                    <option value="transferencia">Transferência / TED</option>
                                </select>
                            </div>

                            <!-- Data de Pagamento -->
                            <div>
                                <label for="data_pagamento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Data do Recebimento <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" name="data_pagamento" id="data_pagamento" value="{{ old('data_pagamento', date('Y-m-d')) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            </div>

                            <!-- Desconto Concedido -->
                            <div>
                                <label for="valor_desconto_concedido" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Desconto Concedido (R$)
                                </label>
                                <input type="number" name="valor_desconto_concedido" id="valor_desconto_concedido" step="0.01" min="0" value="{{ old('valor_desconto_concedido', '0.00') }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            </div>

                            <!-- Juros -->
                            <div>
                                <label for="valor_juros" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Juros Cobrados (R$)
                                </label>
                                <input type="number" name="valor_juros" id="valor_juros" step="0.01" min="0" value="{{ old('valor_juros', $titulo->calcularJuros()) }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            </div>

                            <!-- Multa -->
                            <div>
                                <label for="valor_multa" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Multa Cobrada (R$)
                                </label>
                                <input type="number" name="valor_multa" id="valor_multa" step="0.01" min="0" value="{{ old('valor_multa', $titulo->calcularMulta()) }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="pt-5 border-t border-slate-200 flex flex-wrap items-center justify-end gap-3">
                            <a href="{{ route('financeiro.baixas.create') }}" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium transition shadow-2xs" style="text-decoration: none;">
                                Cancelar
                            </a>
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm transition">
                                <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Confirmar Recebimento e Emitir Recibo
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        @endif

    </main>
</div>
@endsection
