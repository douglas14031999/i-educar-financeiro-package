@extends('financeiro::layouts.app')

@section('title', 'Renegociação e Acordos de Dívidas')
@section('breadcrumb', 'Renegociação de Dívidas')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="renegociacao-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Renegociação de Dívidas e Acordos Financeiros',
            'subtitle' => 'Novação de dívidas escolares, concessão de descontos e emissão do Instrumento Particular de Confissão de Dívida',
            'breadcrumbs' => [
                ['label' => 'Renegociação de Dívidas']
            ],
            'actions' => '
                <div class="flex items-center gap-2">
                    <a href="' . route('financeiro.baixas.create') . '" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Receber pagamento em dinheiro ou cartão">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Baixa no Caixa
                    </a>
                    <a href="' . route('financeiro.titulos.index') . '" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Ver Mensalidades
                    </a>
                </div>
            '
        ])

        <!-- Etapa 1: Selecionar Responsável Financeiro Devedor -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="etapa1-card">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        1. Selecionar Responsável Financeiro Devedor
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    Etapa 1 de 3
                </span>
            </div>

            <div class="p-5">
                <form method="get" action="{{ route('financeiro.renegociacao.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                    <div class="flex-1">
                        <label for="responsavel_id" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Responsável Cadastrado:
                        </label>
                        <select name="responsavel_id" id="responsavel_id" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition" onchange="this.form.submit();" required>
                            <option value="">-- Selecione o Responsável para Consultar Débitos --</option>
                            @foreach($responsaveis as $resp)
                                <option value="{{ $resp->id }}" {{ $responsavelId == $resp->id ? 'selected' : '' }}>
                                    {{ $resp->nome }} (CPF: {{ $resp->cpf_cnpj_formatado }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Carregar Títulos
                        </button>
                    </div>
                </form>
            </div>
        </section>

        @if($responsavelId)
            <!-- Formulário de Simulação (Etapa 2 e Etapa 3) -->
            <form method="post" action="{{ route('financeiro.renegociacao.simular') }}" class="space-y-6">
                @csrf

                <!-- Etapa 2: Definir Condições da Renegociação -->
                <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="etapa2-card">
                    <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                                2. Definir Parâmetros Comerciais da Renegociação
                            </h2>
                        </div>
                        <span class="text-xs text-slate-300">
                            Etapa 2 de 3
                        </span>
                    </div>

                    <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="desconto_valor" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Desconto Concedido no Total (R$)
                            </label>
                            <input type="number" step="0.01" min="0" name="desconto_valor" id="desconto_valor" value="0.00" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">Abatimento promocional ou de juros/multas negociado</p>
                        </div>

                        <div>
                            <label for="qtd_parcelas" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Parcelamento do Acordo
                            </label>
                            <select name="qtd_parcelas" id="qtd_parcelas" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                @for($p = 1; $p <= 24; $p++)
                                    <option value="{{ $p }}">{{ $p }}x parcela(s)</option>
                                @endfor
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Número de boletos ou cobranças em que a dívida será dividida</p>
                        </div>

                        <div>
                            <label for="primeiro_vencimento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Vencimento da 1ª Parcela <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="primeiro_vencimento" id="primeiro_vencimento" value="{{ \Carbon\Carbon::today()->addDays(5)->format('Y-m-d') }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">As parcelas subsequentes vencerão a cada 30 dias</p>
                        </div>
                    </div>
                </section>

                <!-- Etapa 3: Selecione os Títulos que Entrarão no Acordo -->
                <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="etapa3-card">
                    <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                                3. Selecione os Títulos em Aberto ou Vencidos ({{ $titulosPendentes->count() }})
                            </h2>
                        </div>
                        <div class="text-xs text-slate-300">
                            Marque os débitos que serão consolidados neste acordo
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs md:text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                                    <th class="py-3 px-4 text-center" width="50">
                                        <input type="checkbox" id="check-todos" onclick="document.querySelectorAll('.check-titulo').forEach(cb => cb.checked = this.checked);" {{ empty($tituloId) ? 'checked' : '' }} class="w-4 h-4 text-brand-petroleum rounded border-slate-300 focus:ring-brand-petroleum">
                                    </th>
                                    <th class="py-3 px-4" width="70">ID</th>
                                    <th class="py-3 px-4">Competência / Descrição</th>
                                    <th class="py-3 px-4">Vencimento</th>
                                    <th class="py-3 px-4 text-right">Valor Nominal</th>
                                    <th class="py-3 px-4 text-right">Saldo Devedor</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($titulosPendentes as $t)
                                    <tr class="hover:bg-slate-50/80 transition duration-150 {{ (!empty($tituloId) && $t->id == $tituloId) ? 'bg-amber-50/50' : '' }}">
                                        <td class="py-3.5 px-4 text-center">
                                            <input type="checkbox" name="titulos_ids[]" value="{{ $t->id }}" class="check-titulo w-4 h-4 text-brand-petroleum rounded border-slate-300 focus:ring-brand-petroleum" {{ (empty($tituloId) || $t->id == $tituloId) ? 'checked' : '' }}>
                                        </td>
                                        <td class="py-3.5 px-4 font-mono font-medium text-slate-500">
                                            #{{ $t->id }}
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="font-semibold text-slate-900">
                                                {{ $t->competencia }} — {{ $t->observacoes ?? 'Mensalidade Escolar' }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 font-medium">
                                                {{ \iEducar\Financeiro\Support\AlunoLookup::nomeComCpf($t->aluno_id) }}
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 font-mono {{ $t->isVencido() ? 'text-red-600 font-bold' : 'text-slate-700' }}">
                                            {{ \Carbon\Carbon::parse($t->data_vencimento)->format('d/m/Y') }}
                                            @if($t->isVencido())
                                                <span class="text-[10px] uppercase tracking-wider text-red-500 block">Em atraso</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-mono text-slate-600">
                                            R$ {{ number_format($t->valor_nominal, 2, ',', '.') }}
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-mono font-bold text-red-700">
                                            R$ {{ number_format($t->valor_saldo, 2, ',', '.') }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            @if($t->status === 'vencido')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                    Vencido
                                                </span>
                                            @elseif($t->status === 'parcial')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    Parcial
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                    Aberto
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-12 px-4 text-center text-slate-400">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-12 h-12 text-emerald-500 mb-3" width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                </svg>
                                                <span class="text-base font-semibold text-emerald-800">Nenhum débito em aberto</span>
                                                <p class="text-xs text-slate-500 mt-1 max-w-sm">
                                                    Este responsável financeiro está em dia com todas as suas obrigações escolares. Não há títulos para renegociar.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($titulosPendentes->isNotEmpty())
                        <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="text-xs text-slate-500">
                                Saldo total consolidado dos títulos selecionados passível de negociação
                            </div>
                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 btn-ieducar-action btn-ieducar-green">
                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Simular Proposta de Acordo &raquo;
                            </button>
                        </div>
                    @endif
                </section>
            </form>
        @endif

        <!-- Acordos e Termos Recentes Firmados -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="historico-acordos">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Acordos e Termos Recentes Firmados ({{ count($acordosRecentes) }})
                    </h2>
                </div>
                <div class="text-xs text-slate-300">
                    Histórico de confissões de dívida emitidas
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4">Nº do Acordo</th>
                            <th class="py-3 px-4">Data</th>
                            <th class="py-3 px-4">Responsável</th>
                            <th class="py-3 px-4 text-right">Valor do Acordo</th>
                            <th class="py-3 px-4 text-center">Parcelas</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Termo Jurídico</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($acordosRecentes as $ac)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4 font-mono font-bold text-brand-petroleum">
                                    {{ $ac->numero_acordo }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-600">
                                    {{ $ac->created_at->format('d/m/Y') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-900">
                                        {{ $ac->responsavel->nome ?? '-' }}
                                    </div>
                                    @if(optional($ac->responsavel)->cpf_cnpj_formatado)
                                        <div class="text-[11px] text-slate-400 font-mono">
                                            CPF: {{ $ac->responsavel->cpf_cnpj_formatado }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700">
                                    R$ {{ number_format($ac->valor_acordo, 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-medium text-slate-700">
                                    {{ $ac->qtd_parcelas }}x
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-100 text-teal-800 border border-teal-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                                        {{ ucfirst($ac->status) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('financeiro.renegociacao.termo-pdf', $ac->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-100 hover:bg-brand-petroleum hover:text-white text-slate-700 text-xs font-medium border border-slate-200 transition" title="Imprimir Termo de Confissão de Dívida" style="text-decoration: none;">
                                        <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        Termo PDF
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    Nenhum acordo formalizado registrado até o momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</div>
@endsection
