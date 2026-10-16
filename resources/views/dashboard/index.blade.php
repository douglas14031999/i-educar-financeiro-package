@extends('financeiro::layouts.app')

@section('title', 'Painel Geral Financeiro')
@section('breadcrumb', 'Painel Geral')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="dashboard-container">
        @include('financeiro::partials.page-header', [
            'title' => 'Painel Geral de Gestão Financeira',
            'subtitle' => 'Indicadores operacionais, fluxo de recebimentos e controle de cobrança escolar',
            'breadcrumbs' => [
                ['label' => 'Painel Geral']
            ],
            'actions' => '
                <a href="' . route('financeiro.baixas.create') . '" class="inline-flex items-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Nova Baixa de Balcão
                </a>
                <a href="' . route('financeiro.titulos.index') . '" class="inline-flex items-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                    <svg aria-hidden="true" class="w-4 h-4 text-slate-300" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Ver Todas as Mensalidades
                </a>
            '
        ])

        <!-- Indicadores Financeiros -->
        <section aria-label="Indicadores Financeiros" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" data-purpose="kpi-grid">
            <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
                <div class="absolute top-0 inset-x-0 h-1 bg-emerald-500"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Receita Recebida no Mês</span>
                    <span class="p-1 bg-emerald-50 text-emerald-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span id="kpi-total-recebido" class="text-2xl font-bold tracking-tight text-slate-900">R$ {{ number_format($kpis['recebido_mes'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Liquidações via Asaas, Pix e Caixa</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
                <div class="absolute top-0 inset-x-0 h-1 bg-cyan-600"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">A Receber no Mês Atual</span>
                    <span class="p-1 bg-cyan-50 text-cyan-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-slate-900">R$ {{ number_format($kpis['a_receber_mes'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Parcelas vincendas neste período</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
                <div class="absolute top-0 inset-x-0 h-1 bg-rose-500"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Total em Atraso</span>
                    <span class="p-1 bg-rose-50 text-rose-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-slate-900">R$ {{ number_format($kpis['total_inadimplente'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;"><strong class="font-medium text-slate-700">{{ $kpis['titulos_vencidos_count'] ?? 0 }}</strong> parcelas aguardando acerto</p>
                </div>
            </article>

            <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
                <div class="absolute top-0 inset-x-0 h-1 bg-amber-500"></div>
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Taxa de Inadimplência</span>
                    <span class="p-1 bg-amber-50 text-amber-600">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    </span>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($kpis['taxa_inadimplencia'] ?? 0, 1, ',', '.') }}%</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Em relação à receita faturada</p>
                </div>
            </article>
        </section>

        <!-- Tabela -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-container">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">Próximos Vencimentos e Cobranças Recentes</h2>
                </div>
                <a class="text-xs font-medium text-cyan-100 hover:text-white transition flex items-center gap-1" href="{{ route('financeiro.titulos.index') }}" style="text-decoration: none;">
                    Histórico completo <span aria-hidden="true">»</span>
                </a>
            </div>
            
            <!-- Barra de Filtros Rápidos e Ações -->
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex p-1 bg-slate-200/70 border border-slate-200 rounded-lg gap-1 shadow-2xs">
                    <button class="dash-filter-btn group inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-md transition-all {{ ($filtro ?? 'todos') === 'todos' ? 'bg-white shadow-xs text-slate-900 ring-1 ring-slate-900/5 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }}" type="button" data-filter="todos" aria-pressed="{{ ($filtro ?? 'todos') === 'todos' ? 'true' : 'false' }}">
                        <svg class="w-3.5 h-3.5 text-slate-500 group-hover:text-slate-700" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        <span>Todos</span>
                        <span class="dash-badge inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-slate-200/80 text-slate-700 min-w-5">
                            {{ $counts['todos'] ?? count($titulosRecentes) }}
                        </span>
                    </button>
                    <button class="dash-filter-btn group inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-md transition-all {{ ($filtro ?? 'todos') === 'pendentes' ? 'bg-white shadow-xs text-amber-900 ring-1 ring-amber-200 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }}" type="button" data-filter="pendentes" aria-pressed="{{ ($filtro ?? 'todos') === 'pendentes' ? 'true' : 'false' }}">
                        <svg class="w-3.5 h-3.5 text-amber-500 group-hover:text-amber-600" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        <span>Pendentes</span>
                        <span class="dash-badge inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200/60 min-w-5">
                            {{ $counts['pendentes'] ?? 0 }}
                        </span>
                    </button>
                    <button class="dash-filter-btn group inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-md transition-all {{ ($filtro ?? 'todos') === 'atrasados' ? 'bg-white shadow-xs text-rose-800 ring-1 ring-rose-200 font-bold' : 'text-slate-600 hover:text-rose-700 hover:bg-white/60' }}" type="button" data-filter="atrasados" aria-pressed="{{ ($filtro ?? 'todos') === 'atrasados' ? 'true' : 'false' }}">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                        </span>
                        <span>Em Atraso</span>
                        <span class="dash-badge inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-800 border border-rose-200/60 min-w-5">
                            {{ $counts['atrasados'] ?? 0 }}
                        </span>
                    </button>
                    <button class="dash-filter-btn group inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-md transition-all {{ ($filtro ?? 'todos') === 'pagos' ? 'bg-white shadow-xs text-emerald-800 ring-1 ring-emerald-200 font-bold' : 'text-slate-600 hover:text-emerald-700 hover:bg-white/60' }}" type="button" data-filter="pagos" aria-pressed="{{ ($filtro ?? 'todos') === 'pagos' ? 'true' : 'false' }}">
                        <svg class="w-3.5 h-3.5 text-emerald-500 group-hover:text-emerald-600" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        <span>Pagos / Liquidados</span>
                        <span class="dash-badge inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200/60 min-w-5">
                            {{ $counts['pagos'] ?? 0 }}
                        </span>
                    </button>
                </div>
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('financeiro.planos.create') }}" class="text-xs font-semibold inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 hover:border-emerald-500 text-slate-700 hover:text-emerald-700 hover:bg-emerald-50/50 rounded-md transition shadow-2xs" style="text-decoration: none;">
                        <svg class="w-3.5 h-3.5 text-emerald-600" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        Novo Plano de Pagamento
                    </a>
                    <a href="{{ route('financeiro.responsaveis.create') }}" class="text-xs font-semibold inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 hover:border-brand-petroleum text-slate-700 hover:text-brand-petroleum hover:bg-slate-50 rounded-md transition shadow-2xs" style="text-decoration: none;">
                        <svg class="w-3.5 h-3.5 text-slate-600" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        Cadastrar Responsável
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
                    <thead>
                        <tr class="bg-slate-100/90 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4" scope="col">Vencimento</th>
                            <th class="py-3 px-4" scope="col">Aluno</th>
                            <th class="py-3 px-4" scope="col">Responsável Financeiro</th>
                            <th class="py-3 px-4" scope="col">Turma / Série</th>
                            <th class="py-3 px-4 text-center" scope="col">Parcela</th>
                            <th class="py-3 px-4 text-right" scope="col">Valor</th>
                            <th class="py-3 px-4 text-center" scope="col">Status</th>
                            <th class="py-3 px-4 text-center" scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($titulosRecentes ?? [] as $titulo)
                            @php
                                $hoje = \Carbon\Carbon::today();
                                $dataVenc = \Carbon\Carbon::parse($titulo->data_vencimento)->startOfDay();
                                $diasDiff = (int) $hoje->diffInDays($dataVenc, false);

                                $isPago = $titulo->isLiquidado() || $titulo->status === 'pago';
                                $isRenegociado = $titulo->status === 'renegociado';
                                $isCancelado = $titulo->status === 'cancelado';
                                $isParcial = $titulo->status === 'parcial';
                                $isAtrasado = !$isPago && !$isRenegociado && !$isCancelado && ($titulo->status === 'vencido' || $titulo->isVencido() || ($diasDiff < 0 && (float)$titulo->valor_saldo > 0.005));

                                $tipoFiltro = 'pendente';
                                if ($isPago) {
                                    $tipoFiltro = 'pago';
                                } elseif ($isAtrasado) {
                                    $tipoFiltro = 'atrasado';
                                } elseif ($isRenegociado) {
                                    $tipoFiltro = 'renegociado';
                                } elseif ($isCancelado) {
                                    $tipoFiltro = 'cancelado';
                                }

                                $bgClass = $isAtrasado ? 'hover:bg-rose-50/40 bg-rose-50/20' : ($isPago ? 'hover:bg-slate-50/80 bg-emerald-50/10' : 'hover:bg-slate-50/80');

                                // Garante que o valor exibido seja o valor nominal da parcela e nunca R$ 0,00 quando liquidado
                                $valorNominal = (float) $titulo->valor_nominal;
                                $valorSaldo = (float) $titulo->valor_saldo;
                                $valorPago = (float) $titulo->valor_pago;
                                $valorExibicao = $valorNominal > 0 ? $valorNominal : ($valorPago > 0 ? $valorPago : $valorSaldo);
                            @endphp
                            <tr class="{{ $bgClass }} transition-colors dash-row" data-type="{{ $tipoFiltro }}">
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
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ $titulo->aluno_identificacao ?? \iEducar\Financeiro\Support\AlunoLookup::nomeComCpf($titulo->aluno_id) }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $titulo->responsavel->nome ?? 'Não informado' }}</td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700">
                                        {{ $titulo->turma_nome ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap font-medium">{{ $titulo->numero_parcela == 0 ? 'Matr.' : sprintf('%02d/%02d', $titulo->numero_parcela, $titulo->total_parcelas) }}</td>
                                <td class="py-3 px-4 text-right font-bold {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                                    R$ {{ number_format($valorExibicao, 2, ',', '.') }}
                                    @if($isParcial && $valorSaldo > 0)
                                        <span class="block text-[10px] font-normal text-cyan-700">Saldo: R$ {{ number_format($valorSaldo, 2, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($isPago)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Liquidado</span>
                                    @elseif($isAtrasado)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Em Atraso</span>
                                    @elseif($isParcial)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-50 text-cyan-800 border border-cyan-200">Parcial</span>
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
                                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                        </a>
                                        @if(!$isPago && !$isCancelado && !$isRenegociado)
                                            <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}" class="inline-flex items-center justify-center p-1.5 text-amber-600 hover:text-amber-700 hover:bg-amber-50 rounded transition" title="Baixa Presencial no Caixa">
                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                            </a>
                                            <a href="{{ route('financeiro.titulos.notificar-whatsapp', $titulo->id) }}" target="_blank" class="inline-flex items-center justify-center p-1.5 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded transition" title="Enviar Cobrança / Link por WhatsApp">
                                                <svg class="w-4 h-4" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.824z"/></svg>
                                            </a>
                                        @elseif($isPago)
                                            <a href="{{ route('financeiro.titulos.recibo-pdf', $titulo->id) }}" class="inline-flex items-center justify-center p-1.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded transition" title="Imprimir Recibo de Pagamento" target="_blank">
                                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                    Não há cobranças recentes geradas.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="dash-empty-filter-row" class="hidden" style="display: none;">
                            <td colspan="8" class="py-8 text-center text-slate-500">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                <span id="dash-empty-filter-msg">Nenhuma cobrança encontrada para este filtro.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer class="p-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                <span>Mostrando cobranças operacionais priorizadas por urgência e vencimento</span>
                <span class="text-[11px] text-slate-400">Total exibido: {{ count($titulosRecentes ?? []) }} registro(s)</span>
            </footer>
        </section>

        <!-- Paineis Inferiores -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6" data-purpose="info-panels">
            <article class="bg-white border border-slate-200 shadow-xs p-5 flex flex-col justify-between" data-purpose="automation-card">
                <div>
                    <div class="flex items-center gap-2.5 text-brand-petroleum mb-3 pb-2 border-b border-slate-100">
                        <span class="p-1.5 bg-blue-50 text-blue-700">
                            <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        </span>
                        <h3 class="font-bold text-slate-900 text-sm md:text-base" style="margin: 0;">Régua de Cobrança Automática</h3>
                    </div>
                    <p class="text-xs md:text-sm text-slate-600 leading-relaxed mb-4" style="margin-top: 0;">
                        Disparo diário de lembretes e notificações de vencimento com links diretos de Boleto e Pix para os responsáveis por E-mail e WhatsApp.
                    </p>
                    <div class="flex items-center gap-2 text-xs text-emerald-700 font-medium bg-emerald-50/80 px-3 py-2 mb-4 border border-emerald-100">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Integração ativa: Lembretes automáticos enviados às 08h00.
                    </div>
                </div>
                <div>
                    <a class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-petroleum hover:text-brand-petroleum-dark group transition" href="{{ route('financeiro.configuracoes.gateway') }}" style="text-decoration: none;">
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        Configurações do Gateway Asaas e Chaves de Integração »
                    </a>
                </div>
            </article>

            <article class="bg-white border border-slate-200 shadow-xs p-5 flex flex-col justify-between" data-purpose="reports-card">
                <div>
                    <div class="flex items-center gap-2.5 text-brand-petroleum mb-3 pb-2 border-b border-slate-100">
                        <span class="p-1.5 bg-amber-50 text-amber-700">
                            <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        </span>
                        <h3 class="font-bold text-slate-900 text-sm md:text-base" style="margin: 0;">Relatórios Gerenciais</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-3" style="margin-top: 0;">
                        Emissão de relatórios e certidões oficiais conforme legislação educacional:
                    </p>
                    <ul class="space-y-2 text-xs md:text-sm" style="list-style: none; padding-left: 0;">
                        <li>
                            <a class="flex items-center gap-2 text-brand-petroleum hover:text-cyan-700 transition font-medium group" href="{{ route('financeiro.relatorios.inadimplencia') }}" style="text-decoration: none;">
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                Relatório de Inadimplência
                            </a>
                        </li>
                        <li>
                            <a class="flex items-center gap-2 text-brand-petroleum hover:text-cyan-700 transition font-medium group" href="{{ route('financeiro.relatorios.informe-irpf') }}" style="text-decoration: none;">
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                Demonstrativo de Pagamentos para IRPF
                            </a>
                        </li>
                        <li>
                            <a class="flex items-center gap-2 text-brand-petroleum hover:text-cyan-700 transition font-medium group" href="{{ route('financeiro.relatorios.quitacao-anual') }}" style="text-decoration: none;">
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                Declaração de Quitação Anual de Débitos
                            </a>
                        </li>
                    </ul>
                </div>
            </article>
        </section>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterButtons = document.querySelectorAll('.dash-filter-btn');
    const rows = document.querySelectorAll('.dash-row');
    const emptyRow = document.getElementById('dash-empty-filter-row');
    const emptyMsg = document.getElementById('dash-empty-filter-msg');

    function applyFilter(filter) {
        let visibleCount = 0;

        rows.forEach(function (row) {
            const type = row.getAttribute('data-type');
            let show = false;

            if (filter === 'todos') {
                show = true;
            } else if (filter === 'pendentes') {
                show = (type === 'pendente');
            } else if (filter === 'atrasados') {
                show = (type === 'atrasado');
            } else if (filter === 'pagos') {
                show = (type === 'pago');
            }

            if (show) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (emptyRow) {
            if (visibleCount === 0 && rows.length > 0) {
                emptyRow.classList.remove('hidden');
                emptyRow.style.display = '';
                if (emptyMsg) {
                    const labels = {
                        'pendentes': 'Nenhum título pendente / a vencer encontrado.',
                        'atrasados': 'Excelente! Nenhum título em atraso encontrado.',
                        'pagos': 'Nenhum título liquidado encontrado.',
                        'todos': 'Nenhuma cobrança recente gerada.'
                    };
                    emptyMsg.textContent = labels[filter] || 'Nenhuma cobrança encontrada.';
                }
            } else {
                emptyRow.classList.add('hidden');
                emptyRow.style.display = 'none';
            }
        }

        // Atualiza a estilização dos botões
        filterButtons.forEach(function (btn) {
            const bFilter = btn.getAttribute('data-filter');
            const isActive = (bFilter === filter);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');

            const baseClass = 'dash-filter-btn group inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-md transition-all ';
            if (isActive) {
                if (filter === 'atrasados') {
                    btn.className = baseClass + 'bg-white shadow-xs text-rose-800 ring-1 ring-rose-200 font-bold';
                } else if (filter === 'pagos') {
                    btn.className = baseClass + 'bg-white shadow-xs text-emerald-800 ring-1 ring-emerald-200 font-bold';
                } else if (filter === 'pendentes') {
                    btn.className = baseClass + 'bg-white shadow-xs text-amber-900 ring-1 ring-amber-200 font-bold';
                } else {
                    btn.className = baseClass + 'bg-white shadow-xs text-slate-900 ring-1 ring-slate-900/5 font-bold';
                }
            } else {
                if (bFilter === 'atrasados') {
                    btn.className = baseClass + 'text-slate-600 hover:text-rose-700 hover:bg-white/60';
                } else if (bFilter === 'pagos') {
                    btn.className = baseClass + 'text-slate-600 hover:text-emerald-700 hover:bg-white/60';
                } else {
                    btn.className = baseClass + 'text-slate-600 hover:text-slate-900 hover:bg-white/60';
                }
            }
        });
    }

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const filter = this.getAttribute('data-filter');
            applyFilter(filter);
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('filtro', filter);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}
        });
    });

    // Se houver filtro na URL, aciona imediatamente
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const urlFilter = urlParams.get('filtro');
        if (urlFilter && ['todos', 'pendentes', 'atrasados', 'pagos'].includes(urlFilter)) {
            applyFilter(urlFilter);
        }
    } catch (e) {}
});
</script>
@endsection
