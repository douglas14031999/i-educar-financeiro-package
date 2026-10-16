@extends('financeiro::layouts.app')

@section('title', 'Mensalidades e Títulos')
@section('breadcrumb', 'Mensalidades e Títulos')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="titulos-container">
        
        @php
            $breadcrumbs = [['label' => 'Títulos', 'url' => route('financeiro.titulos.index')]];
            if ($responsavelFiltro) {
                $breadcrumbs[] = ['label' => 'Responsável: ' . $responsavelFiltro->nome];
            } elseif ($contratoFiltro) {
                $breadcrumbs[] = ['label' => 'Contrato #' . $contratoFiltro->numero_contrato];
            }
            $actionsHtml = '<div class="flex flex-wrap items-center gap-2">';

            // Botão Baixa de Balcão (sempre presente)
            $actionsHtml .= '
                <a href="' . route('financeiro.baixas.create') . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Baixar pagamento em dinheiro, cartão ou cheque na secretaria">
                    <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Baixa de Balcão (Caixa)
                </a>';

            if ($contratoFiltro) {
                $forma = $contratoFiltro->forma_pagamento ?? '';
                $primeiroTituloContrato = $contratoFiltro->titulos->first();
                $cobrancaContrato = $primeiroTituloContrato?->cobrancaGateway;

                // Opções conforme a forma de pagamento do contrato filtrado
                if ($forma === 'carne') {
                    $actionsHtml .= '
                        <a href="' . route('financeiro.contratos.imprimir-carne', $contratoFiltro->id) . '" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Imprimir carnê oficial com todas as lâminas escolares">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Imprimir Carnê
                        </a>
                        <a href="' . route('financeiro.contratos.checkout', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;" title="Ver resumo do carnê e cobranças">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Ver Checkout / Pagamento
                        </a>';
                } elseif ($forma === 'pix') {
                    $actionsHtml .= '
                        <a href="' . route('financeiro.contratos.checkout', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Ver QR Code Pix e Chave Copia e Cola">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Ver Checkout / Pix
                        </a>';
                } elseif ($forma === 'boleto') {
                    if ($cobrancaContrato?->link_boleto_pdf) {
                        $actionsHtml .= '
                            <a href="' . $cobrancaContrato->link_boleto_pdf . '" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Baixar Boleto Bancário (PDF)">
                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Baixar Boleto (PDF)
                            </a>';
                    }
                    $actionsHtml .= '
                        <a href="' . route('financeiro.contratos.checkout', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;" title="Ver Boleto Bancário e Linha Digitável">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Ver Checkout / Boleto
                        </a>';
                } elseif ($forma === 'cartao_credito') {
                    $actionsHtml .= '
                        <a href="' . route('financeiro.contratos.checkout', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Acessar link para pagamento com Cartão">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Ver Checkout / Cartão
                        </a>';
                } elseif (in_array($forma, ['cartao_presencial', 'dinheiro'])) {
                    $actionsHtml .= '
                        <a href="' . route('financeiro.contratos.checkout', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Ver detalhes do pagamento e comprovante">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Comprovante / Detalhes
                        </a>';
                }

                // Botão para voltar / ver o contrato
                $actionsHtml .= '
                    <a href="' . route('financeiro.contratos.show', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;" title="Ver detalhes do contrato #' . $contratoFiltro->numero_contrato . '">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Ver Contrato
                    </a>';
            } else {
                // Visualização geral sem filtro de contrato: opção de novo contrato
                $actionsHtml .= '
                    <a href="' . route('financeiro.contratos.create', array_filter(['responsavel_id' => $responsavelId])) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;" title="Matricular e gerar parcelas">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Novo Contrato
                    </a>';
            }

            $actionsHtml .= '</div>';
        @endphp

        @include('financeiro::partials.page-header', [
            'title' => 'Títulos e Mensalidades Escolares',
            'subtitle' => $responsavelFiltro 
                ? 'Exibindo títulos do responsável ' . $responsavelFiltro->nome . ' (CPF: ' . $responsavelFiltro->cpf_cnpj_formatado . ')'
                : ($contratoFiltro 
                    ? 'Exibindo títulos do Contrato #' . $contratoFiltro->numero_contrato . ' (Ano Letivo: ' . $contratoFiltro->ano_letivo . ')'
                    : 'Gerenciamento operacional de cobranças, vencimentos, liquidações e taxas escolares'),
            'breadcrumbs' => $breadcrumbs,
            'actions' => $actionsHtml
        ])

        <!-- Barra de Filtros e Busca -->
        <section class="bg-white shadow-xs border border-slate-200 p-4" data-purpose="filter-section">
            <form method="get" action="{{ route('financeiro.titulos.index') }}" class="flex flex-wrap items-end gap-3">
                @if($responsavelId)
                    <input type="hidden" name="responsavel_id" value="{{ $responsavelId }}">
                @endif
                @if($contratoId)
                    <input type="hidden" name="contrato_id" value="{{ $contratoId }}">
                @endif
                @if($alunoId)
                    <input type="hidden" name="aluno_id" value="{{ $alunoId }}">
                @endif

                <div class="flex-1 min-w-[260px]">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Buscar:
                    </label>
                    <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar por Responsável, CPF ou Observação..." class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                </div>

                <div class="w-full sm:w-44">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Status:
                    </label>
                    <select name="status" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        <option value="">Todos os Status</option>
                        <option value="aberto" {{ $status === 'aberto' ? 'selected' : '' }}>Abertos</option>
                        <option value="parcial" {{ $status === 'parcial' ? 'selected' : '' }}>Parciais</option>
                        <option value="pago" {{ $status === 'pago' ? 'selected' : '' }}>Pagos / Liquidados</option>
                        <option value="vencido" {{ $status === 'vencido' ? 'selected' : '' }}>Vencidos (Em Atraso)</option>
                        <option value="cancelado" {{ $status === 'cancelado' ? 'selected' : '' }}>Cancelados</option>
                    </select>
                </div>

                <div class="w-full sm:w-36">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Competência:
                    </label>
                    <input type="text" name="competencia" value="{{ $competencia }}" placeholder="AAAA-MM" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                </div>

                <div class="w-full sm:w-28">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Ano Letivo:
                    </label>
                    <input type="number" name="ano" value="{{ $ano }}" placeholder="{{ date('Y') }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                </div>

                <div class="flex items-center gap-2 pt-1 sm:pt-0">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Filtrar
                    </button>
                    @if($busca || $status || $competencia || $ano || $responsavelId || $contratoId || $alunoId)
                        <a href="{{ route('financeiro.titulos.index') }}" class="px-3 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                            Limpar Filtros
                        </a>
                    @endif
                </div>
            </form>

            @if($responsavelFiltro || $contratoFiltro || $status || $competencia || $ano || $alunoId || $busca)
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">Filtros ativos:</span>
                    @if($responsavelFiltro)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-[#47728f] border border-blue-200 text-xs font-medium">
                            Responsável: {{ $responsavelFiltro->nome }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('responsavel_id')) }}" class="text-[#47728f] hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($contratoFiltro)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-[#47728f] border border-blue-200 text-xs font-medium">
                            Contrato #{{ $contratoFiltro->numero_contrato }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('contrato_id')) }}" class="text-[#47728f] hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($alunoId)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-[#47728f] border border-blue-200 text-xs font-medium">
                            Aluno #{{ $alunoId }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('aluno_id')) }}" class="text-[#47728f] hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($ano)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium">
                            Ano: {{ $ano }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('ano')) }}" class="text-slate-500 hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($status)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium">
                            Status: {{ ucfirst($status) }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('status')) }}" class="text-slate-500 hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($competencia)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium">
                            Competência: {{ $competencia }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('competencia')) }}" class="text-slate-500 hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($busca)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium">
                            Busca: "{{ $busca }}"
                            <a href="{{ route('financeiro.titulos.index', request()->except('busca')) }}" class="text-slate-500 hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                </div>
            @endif
        </section>

        <!-- Formulário Oculto de Ações em Lote -->
        <form id="form-lote-acoes" method="post" action="{{ route('financeiro.titulos.emitir-lote-asaas') }}">
            @csrf
            <input type="hidden" name="titulo_ids" id="lote-titulo-ids" value="">
        </form>

        <!-- Tabela de Listagem -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden relative" data-purpose="table-container">
            <!-- Cabeçalho com Gradiente Petróleo -->
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Mensalidades e Cobranças Registradas ({{ $titulos->total() }})
                    </h2>
                </div>
                <span class="text-xs font-medium text-cyan-100">
                    Total de registros: {{ $titulos->total() }}
                </span>
            </div>

            <!-- Tabela Responsiva -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                    <thead>
                        <tr class="bg-slate-100/90 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-3 text-center" style="width: 40px;" scope="col">
                                <input type="checkbox" id="check-todos-titulos" class="cursor-pointer rounded border-slate-300 text-brand-petroleum focus:ring-brand-petroleum" title="Selecionar todos os títulos da página">
                            </th>
                            <th class="py-3 px-4" scope="col">Vencimento</th>
                            <th class="py-3 px-4 text-center" scope="col">Parcela</th>
                            <th class="py-3 px-4" scope="col">Responsável Financeiro</th>
                            <th class="py-3 px-4" scope="col">Descrição / Contrato</th>
                            <th class="py-3 px-4 text-right" scope="col">Saldo Atual</th>
                            <th class="py-3 px-4 text-center" scope="col">Status</th>
                            <th class="py-3 px-4 text-center" scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($titulos as $titulo)
                            @php
                                $hoje = \Carbon\Carbon::today();
                                $dataVenc = \Carbon\Carbon::parse($titulo->data_vencimento)->startOfDay();
                                $diasDiff = (int) $hoje->diffInDays($dataVenc, false);

                                $isPago = $titulo->isLiquidado() || $titulo->status === 'pago';
                                $isRenegociado = $titulo->status === 'renegociado';
                                $isCancelado = $titulo->status === 'cancelado';
                                $isAtrasado = !$isPago && !$isRenegociado && !$isCancelado && ($titulo->status === 'vencido' || $titulo->isVencido() || ($diasDiff < 0 && (float)$titulo->valor_saldo > 0.005));
                                $bgClass = $isAtrasado ? 'hover:bg-rose-50/40 bg-rose-50/20' : ($isPago ? 'hover:bg-slate-50/80 bg-emerald-50/10' : 'hover:bg-slate-50/80');
                                $valorSaldoNum = (float) ($titulo->valor_saldo > 0 ? $titulo->valor_saldo : $titulo->valor_nominal);
                            @endphp
                            <tr class="{{ $bgClass }} transition-colors" data-titulo-id="{{ $titulo->id }}">
                                <td class="py-3 px-3 text-center">
                                    <input type="checkbox" class="check-titulo-item cursor-pointer rounded border-slate-300 text-brand-petroleum focus:ring-brand-petroleum" value="{{ $titulo->id }}" data-valor="{{ $valorSaldoNum }}" data-pago="{{ $isPago ? '1' : '0' }}">
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap font-medium {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                                    {{ $dataVenc->format('d/m/Y') }}
                                    @if($isPago)
                                        <span class="block text-[10px] text-emerald-600 font-semibold">Liquidado</span>
                                    @elseif($isRenegociado)
                                        <span class="block text-[10px] text-cyan-600 font-medium">Acordo Formalizado</span>
                                    @elseif($isAtrasado)
                                        <span class="block text-[10px] text-rose-500 font-semibold">{{ abs($diasDiff) == 1 ? '1 dia atrasado' : abs($diasDiff) . ' dias atrasado' }}</span>
                                    @elseif($diasDiff === 0)
                                        <span class="block text-[10px] text-amber-600 font-bold">Vence Hoje</span>
                                    @elseif($diasDiff === 1)
                                        <span class="block text-[10px] text-amber-600 font-medium">Vence amanhã</span>
                                    @else
                                        <span class="block text-[10px] text-slate-400">Em {{ $diasDiff }} dias</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap font-bold {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                                    {{ $titulo->numero_parcela == 0 ? 'Matr.' : sprintf('%02d/%02d', $titulo->numero_parcela, $titulo->total_parcelas) }}
                                </td>
                                <td class="py-3 px-4">
                                    <a href="{{ route('financeiro.responsaveis.show', $titulo->responsavel_financeiro_id) }}" class="font-semibold text-slate-900 hover:text-brand-petroleum hover:underline" style="text-decoration: none;">
                                        {{ $titulo->responsavel->nome ?? '-' }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 mr-1.5">
                                        Aluno #{{ $titulo->aluno_id }}
                                    </span>
                                    {{ $titulo->observacoes ?? ucfirst($titulo->tipo) }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold col-saldo {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                                    R$ {{ number_format($isPago ? $titulo->valor_nominal : $titulo->valor_saldo, 2, ',', '.') }}
                                    @if($isPago)
                                        <span class="block text-[10px] font-normal text-emerald-700">Quitado</span>
                                    @elseif($titulo->status === 'parcial')
                                        <span class="block text-[10px] font-normal text-cyan-700">Nominal: R$ {{ number_format($titulo->valor_nominal, 2, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap col-status">
                                    @if($isPago)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Liquidado</span>
                                    @elseif($titulo->status === 'parcial')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-50 text-cyan-800 border border-cyan-200">Parcial</span>
                                    @elseif($isAtrasado)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Em Atraso</span>
                                    @elseif($isRenegociado)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-300">Renegociado</span>
                                    @elseif($isCancelado)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">Cancelado</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200/70">Pendente</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('financeiro.titulos.show', $titulo->id) }}" class="inline-flex items-center justify-center p-1.5 text-slate-500 hover:text-brand-petroleum hover:bg-slate-100 rounded transition" title="Ver Detalhes do Título">
                                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                        </a>

                                        @if($titulo->status === 'aberto' || $titulo->status === 'parcial' || $titulo->status === 'vencido')
                                            @if($titulo->contrato && $titulo->contrato->forma_pagamento === 'carne')
                                                <a href="{{ route('financeiro.contratos.imprimir-carne', $titulo->contrato_id) }}" target="_blank" class="inline-flex items-center justify-center p-1.5 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded transition" title="Imprimir Carnê Oficial Asaas">
                                                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                    </svg>
                                                </a>
                                            @elseif($titulo->contrato && $titulo->contrato->forma_pagamento === 'pix')
                                                <a href="{{ route('financeiro.contratos.checkout', $titulo->contrato_id) }}" class="inline-flex items-center justify-center p-1.5 text-sky-600 hover:text-sky-700 hover:bg-sky-50 rounded transition" title="Ver Cobrança / QR Code Pix">
                                                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                    </svg>
                                                </a>
                                            @elseif($titulo->contrato && $titulo->contrato->forma_pagamento === 'boleto')
                                                @if($titulo->cobrancaGateway && $titulo->cobrancaGateway->link_boleto_pdf)
                                                    <a href="{{ $titulo->cobrancaGateway->link_boleto_pdf }}" target="_blank" class="inline-flex items-center justify-center p-1.5 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded transition" title="Baixar Boleto Bancário (PDF)">
                                                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                        </svg>
                                                    </a>
                                                @else
                                                    <a href="{{ route('financeiro.contratos.checkout', $titulo->contrato_id) }}" class="inline-flex items-center justify-center p-1.5 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded transition" title="Ver Cobrança / Boleto Bancário">
                                                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                        </svg>
                                                    </a>
                                                @endif
                                            @elseif($titulo->contrato_id)
                                                <a href="{{ route('financeiro.contratos.checkout', $titulo->contrato_id) }}" class="inline-flex items-center justify-center p-1.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded transition" title="Ver Checkout do Contrato">
                                                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                    </svg>
                                                </a>
                                            @endif
                                            <a href="{{ route('financeiro.titulos.notificar-whatsapp', $titulo->id) }}" target="_blank" class="inline-flex items-center justify-center p-1.5 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded transition" title="Enviar Cobrança / Link por WhatsApp">
                                                <svg class="w-4 h-4" width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.824z"/>
                                                </svg>
                                            </a>
                                            <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}" class="inline-flex items-center justify-center p-1.5 text-amber-600 hover:text-amber-700 hover:bg-amber-50 rounded transition" title="Baixa Presencial no Caixa">
                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                            </a>
                                        @elseif($titulo->status === 'pago')
                                            <a href="{{ route('financeiro.titulos.recibo-pdf', $titulo->id) }}" class="inline-flex items-center justify-center p-1.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded transition" title="Imprimir Recibo de Pagamento" target="_blank">
                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                    </svg>
                                    Nenhuma mensalidade encontrada para os filtros selecionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Rodapé com Paginação -->
            <footer class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-600 gap-3">
                <span>
                    Mostrando <strong>{{ $titulos->firstItem() ?? 0 }}</strong> a <strong>{{ $titulos->lastItem() ?? 0 }}</strong> de <strong>{{ $titulos->total() }}</strong> cobranças
                </span>
                <div class="pagination-wrapper">
                    {{ $titulos->appends(['busca' => $busca, 'status' => $status, 'competencia' => $competencia])->links() }}
                </div>
            </footer>
        </section>

        <!-- Barra Flutuante de Ações em Lote (Sticky Bottom) -->
        <div id="barra-acoes-lote" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 bg-[#1e293b] text-white px-5 py-3 rounded-lg shadow-2xl border border-slate-700 flex items-center gap-4 transition-all duration-300 transform translate-y-24 opacity-0 pointer-events-none" style="min-width: 320px; max-width: 90vw;">
            <div class="flex items-center gap-2">
                <span id="contador-selecionados" class="bg-cyan-500 text-slate-900 font-extrabold text-xs px-2 py-0.5 rounded-full">0</span>
                <span class="text-xs font-semibold text-slate-200">parcelas</span>
                <span id="total-selecionados" class="text-xs font-bold text-emerald-400 border-l border-slate-600 pl-2">R$ 0,00</span>
            </div>

            <div class="flex items-center gap-2 ml-auto">
                @if(!$contratoFiltro || $contratoFiltro->forma_pagamento === 'carne')
                <button type="button" id="btn-imprimir-carne-lote" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded cursor-pointer transition">
                    <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Imprimir Carnê
                </button>
                @endif

                <button type="button" id="btn-emitir-asaas-lote" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded cursor-pointer transition">
                    <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Emitir no Asaas
                </button>

                <button type="button" id="btn-sincronizar-asaas-lote" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded cursor-pointer transition">
                    <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Sincronizar Asaas
                </button>

                <button type="button" id="btn-desmarcar-todos" class="text-xs text-slate-400 hover:text-white px-2 py-1 underline transition cursor-pointer">
                    Desmarcar
                </button>
            </div>
        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('check-todos-titulos');
    const itemChecks = document.querySelectorAll('.check-titulo-item');
    const barraLote = document.getElementById('barra-acoes-lote');
    const contadorSpan = document.getElementById('contador-selecionados');
    const totalSpan = document.getElementById('total-selecionados');
    const btnImprimir = document.getElementById('btn-imprimir-carne-lote');
    const btnEmitirAsaas = document.getElementById('btn-emitir-asaas-lote');
    const btnSincronizarAsaas = document.getElementById('btn-sincronizar-asaas-lote');
    const btnDesmarcar = document.getElementById('btn-desmarcar-todos');
    const inputLoteIds = document.getElementById('lote-titulo-ids');
    const formLote = document.getElementById('form-lote-acoes');

    function atualizarEstadoLote() {
        const selecionados = Array.from(itemChecks).filter(cb => cb.checked);
        const count = selecionados.length;

        if (count > 0) {
            let total = 0;
            const ids = [];
            selecionados.forEach(cb => {
                ids.push(cb.value);
                total += parseFloat(cb.getAttribute('data-valor') || 0);
            });

            contadorSpan.textContent = count;
            totalSpan.textContent = 'R$ ' + total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            inputLoteIds.value = ids.join(',');

            barraLote.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
            barraLote.classList.add('translate-y-0', 'opacity-100');
        } else {
            barraLote.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
            barraLote.classList.remove('translate-y-0', 'opacity-100');
            inputLoteIds.value = '';
        }

        if (checkAll) {
            checkAll.checked = (count === itemChecks.length && count > 0);
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            itemChecks.forEach(cb => cb.checked = checkAll.checked);
            atualizarEstadoLote();
        });
    }

    itemChecks.forEach(cb => {
        cb.addEventListener('change', atualizarEstadoLote);
    });

    if (btnDesmarcar) {
        btnDesmarcar.addEventListener('click', function () {
            if (checkAll) checkAll.checked = false;
            itemChecks.forEach(cb => cb.checked = false);
            atualizarEstadoLote();
        });
    }

    if (btnImprimir) {
        btnImprimir.addEventListener('click', function () {
            const ids = inputLoteIds.value;
            if (!ids) return;
            const url = "{{ route('financeiro.titulos.imprimir-carne') }}?titulo_ids=" + encodeURIComponent(ids);
            window.open(url, '_blank');
        });
    }

    if (btnEmitirAsaas) {
        btnEmitirAsaas.addEventListener('click', function () {
            const ids = inputLoteIds.value;
            if (!ids) return;
            if (confirm('Deseja emitir no Asaas as ' + contadorSpan.textContent + ' cobranças selecionadas?')) {
                formLote.action = "{{ route('financeiro.titulos.emitir-lote-asaas') }}";
                formLote.submit();
            }
        });
    }

    if (btnSincronizarAsaas) {
        btnSincronizarAsaas.addEventListener('click', function () {
            const ids = inputLoteIds.value;
            if (!ids) return;
            if (confirm('Deseja consultar e sincronizar no Asaas as ' + contadorSpan.textContent + ' cobranças selecionadas?')) {
                formLote.action = "{{ route('financeiro.titulos.sincronizar-lote-asaas') }}";
                formLote.submit();
            }
        });
    }
});
</script>
@endsection
