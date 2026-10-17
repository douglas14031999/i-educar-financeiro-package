@php
    $modoAtual = $modo ?? 'contrato';
    $formaContrato = strtolower(trim((string) ($contratoFiltro?->forma_pagamento ?? '')));
    $todosTitulosPagos = $titulos->isNotEmpty() && $titulos->every(fn($t) => in_array($t->status, ['pago', 'liquidado']) || (float)$t->valor_saldo <= 0.005);
    $contratoSemAcoesLote = $contratoFiltro && (
        $formaContrato === 'pix' ||
        $formaContrato === 'dinheiro' ||
        $formaContrato === 'cartao_presencial' ||
        in_array($contratoFiltro->status, ['quitado', 'cancelado']) ||
        $todosTitulosPagos
    );
    $temAcoesEmLote = !$contratoSemAcoesLote;
@endphp

<!-- Cabeçalho Superior com Gradiente Petróleo e Alternador de Visão -->
<div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded bg-white/10 flex items-center justify-center text-cyan-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
        </div>
        <div>
            <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                @if($modoAtual === 'contrato')
                    Mensalidades e Carnês por Aluno ({{ $contratosPaginados ? $contratosPaginados->total() : 0 }} Contratos)
                @else
                    Todas as Cobranças Registradas ({{ $titulos->total() }} Títulos)
                @endif
            </h2>
            <span class="text-[11px] text-cyan-100/90">
                @if($status === 'renegociado')
                    Exibindo histórico de títulos renegociados (arquivados)
                @elseif($status === 'vencido')
                    Exibindo parcelas em atraso com cobrança pendente
                @else
                    Exibindo cobranças ativas com dados sincronizados
                @endif
            </span>
        </div>
    </div>

    <!-- Alternador de Modo de Visualização -->
    <div class="flex items-center gap-2 self-start sm:self-auto">
        <div class="inline-flex rounded-md shadow-xs bg-[#365971] p-0.5 border border-white/20">
            <button type="button" onclick="mudarModoVisualizacao('contrato')" class="px-2.5 py-1 text-xs font-semibold rounded transition flex items-center gap-1.5 {{ $modoAtual === 'contrato' ? 'bg-white text-slate-800 shadow-xs' : 'text-cyan-100 hover:text-white' }}" title="Visualização agrupada por Aluno / Contrato">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
                Por Aluno / Contrato
            </button>
            <button type="button" onclick="mudarModoVisualizacao('lista')" class="px-2.5 py-1 text-xs font-semibold rounded transition flex items-center gap-1.5 {{ $modoAtual === 'lista' ? 'bg-white text-slate-800 shadow-xs' : 'text-cyan-100 hover:text-white' }}" title="Visualização em lista detalhada de parcelas individuais">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
                Lista de Parcelas
            </button>
        </div>
    </div>
</div>

@if($modoAtual === 'contrato' && $contratosPaginados)
    <!-- ========================================================================= -->
    <!-- MODO 1: AGRUPADO POR ALUNO / CONTRATO COM ACORDEÃO EXPANSÍVEL (RECOMENDADO) -->
    <!-- ========================================================================= -->
    <div class="divide-y divide-slate-200">
        @forelse($contratosPaginados as $contrato)
            @php
                $alunoId = $contrato->aluno_id ?? $contrato->titulos->first()?->aluno_id;
                $alunoNome = \iEducar\Financeiro\Support\AlunoLookup::nomeComCpf($alunoId);
                $turmaNome = \iEducar\Financeiro\Support\AlunoLookup::turma($alunoId, $contrato->ano_letivo);
                $titulosContrato = $contrato->titulos;
                $titulosAtivos = $titulosContrato->where('status', '!=', 'renegociado');
                $totalParcelas = $titulosAtivos->count();
                $pagasCount = $titulosAtivos->whereIn('status', ['pago', 'liquidado'])->count();
                $atrasadasCount = $titulosAtivos->filter(fn($t) => ($t->isVencido() || $t->status === 'vencido') && !in_array($t->status, ['pago', 'liquidado', 'renegociado']))->count();
                $abertasCount = $titulosAtivos->filter(fn($t) => !$t->isVencido() && in_array($t->status, ['aberto', 'parcial']))->count();
                $saldoDevedor = (float) $titulosAtivos->sum('valor_saldo');
                $percentualPago = $totalParcelas > 0 ? round(($pagasCount / $totalParcelas) * 100) : 0;
                $proximoTitulo = $titulosAtivos->whereIn('status', ['aberto', 'parcial'])->sortBy('data_vencimento')->first();
                $proximaData = $proximoTitulo ? \Carbon\Carbon::parse($proximoTitulo->data_vencimento)->format('d/m/Y') : null;
                $contratoIdSafe = 'contrato-acordeon-' . $contrato->id;
            @endphp
            <div class="bg-white hover:bg-slate-50/70 transition-colors">
                <!-- Linha Mestra do Contrato -->
                <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 cursor-pointer select-none" onclick="toggleAcordeon('{{ $contratoIdSafe }}')">
                    
                    <!-- Coluna 1: Aluno e Contrato -->
                    <div class="flex items-start gap-3.5 min-w-[280px] lg:max-w-[340px]">
                        <button type="button" id="btn-icon-{{ $contratoIdSafe }}" class="mt-0.5 w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center shrink-0 transition-transform duration-200" title="Expandir/Recolher parcelas">
                            <svg class="w-4 h-4 transition-transform duration-200" id="chevron-{{ $contratoIdSafe }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </button>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-slate-900 text-sm hover:text-brand-petroleum transition">
                                    {{ $alunoNome }}
                                </span>
                                @if($turmaNome)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $turmaNome }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-slate-700">Contrato #{{ $contrato->numero_contrato }}</span>
                                <span>•</span>
                                <span>Ano {{ $contrato->ano_letivo }}</span>
                                <span>•</span>
                                <span class="capitalize">{{ str_replace('_', ' ', $contrato->forma_pagamento ?? 'Carnê') }}</span>
                            </div>
                            <div class="text-[11px] text-slate-600">
                                <span class="text-slate-400">Responsável:</span> <strong>{{ $contrato->responsavel->nome ?? '-' }}</strong>
                                @if(!empty($contrato->responsavel->cpf_cnpj_formatado))
                                    <span class="text-slate-400 font-mono text-[10px]">({{ $contrato->responsavel->cpf_cnpj_formatado }})</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Coluna 2: Progresso do Carnê e Situação -->
                    <div class="flex-1 min-w-[220px] max-w-sm">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-semibold text-slate-700 flex items-center gap-1.5">
                                Carnê: <strong>{{ $pagasCount }}</strong> de <strong>{{ $totalParcelas }}</strong> parcelas pagas
                            </span>
                            <span class="font-bold {{ $percentualPago == 100 ? 'text-emerald-600' : 'text-slate-600' }}">
                                {{ $percentualPago }}%
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full transition-all duration-300 {{ $atrasadasCount > 0 ? 'bg-gradient-to-r from-emerald-500 to-rose-500' : ($percentualPago == 100 ? 'bg-emerald-500' : 'bg-[#47728f]') }}" style="width: {{ $percentualPago }}%;"></div>
                        </div>
                        <div class="mt-1.5 flex items-center gap-2 text-[11px] text-slate-500">
                            @if($atrasadasCount > 0)
                                <span class="text-rose-600 font-semibold flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    {{ $atrasadasCount }} {{ $atrasadasCount == 1 ? 'parcela em atraso' : 'parcelas em atraso' }}
                                </span>
                            @elseif($percentualPago == 100)
                                <span class="text-emerald-600 font-semibold flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Carnê 100% Quitado
                                </span>
                            @elseif($proximaData)
                                <span class="text-slate-600">
                                    Próximo vencimento: <strong>{{ $proximaData }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Coluna 3: Saldo e Status Geral -->
                    <div class="text-left sm:text-right min-w-[150px]">
                        <span class="block text-[11px] uppercase tracking-wider font-semibold text-slate-400">Saldo Devedor:</span>
                        <span class="text-base font-extrabold {{ $saldoDevedor > 0.005 ? ($atrasadasCount > 0 ? 'text-rose-700' : 'text-slate-900') : 'text-emerald-600' }}">
                            R$ {{ number_format($saldoDevedor, 2, ',', '.') }}
                        </span>
                        <div class="mt-1">
                            @if($atrasadasCount > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    Inadimplente ({{ $atrasadasCount }})
                                </span>
                            @elseif($percentualPago == 100)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Quitado
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-800 border border-sky-200">
                                    Em dia
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Coluna 4: Ações do Contrato -->
                    <div class="flex items-center gap-2 self-end lg:self-center shrink-0" onclick="event.stopPropagation()">
                        @if($contrato->forma_pagamento === 'carne')
                            <a href="{{ route('financeiro.contratos.imprimir-carne', $contrato->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-700 hover:bg-cyan-800 text-white text-xs font-semibold rounded shadow-2xs transition" style="text-decoration: none;" title="Imprimir Carnê Oficial Escolar">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Imprimir Carnê
                            </a>
                        @endif

                        @if($atrasadasCount > 0 && $contrato->responsavel_financeiro_id)
                            <a href="{{ route('financeiro.renegociacao.index', ['responsavel_id' => $contrato->responsavel_financeiro_id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded shadow-2xs transition" style="text-decoration: none;" title="Renegociar parcelas em atraso deste responsável">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Renegociar
                            </a>
                        @endif

                        <a href="{{ route('financeiro.contratos.show', $contrato->id) }}" class="inline-flex items-center justify-center p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded transition" title="Ver Detalhes do Contrato">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Sub-Tabela com as Parcelas do Contrato (Acordeão) -->
                <div id="{{ $contratoIdSafe }}" class="hidden bg-slate-50/90 border-t border-slate-200 px-4 py-3 sm:px-6 sm:py-4 transition-all">
                    <div class="bg-white rounded border border-slate-200 shadow-2xs overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-100/90 border-b border-slate-200 flex items-center justify-between text-xs font-semibold text-slate-700">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                <span>Demonstrativo Individual das Parcelas do Contrato #{{ $contrato->numero_contrato }}</span>
                            </div>
                            <span class="text-slate-500 font-normal">
                                Total: {{ $titulosContrato->count() }} parcelas registradas
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse m-0">
                                <thead>
                                    <tr class="bg-slate-50 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                                        <th class="py-2.5 px-3 text-center" style="width: 70px;">Parcela</th>
                                        <th class="py-2.5 px-3">Vencimento</th>
                                        <th class="py-2.5 px-3 text-right">Valor Original</th>
                                        <th class="py-2.5 px-3 text-right">Saldo Atual</th>
                                        <th class="py-2.5 px-3 text-center">Status</th>
                                        <th class="py-2.5 px-3 text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    @foreach($titulosContrato as $parcela)
                                        @php
                                            $hoje = \Carbon\Carbon::today();
                                            $dataVenc = \Carbon\Carbon::parse($parcela->data_vencimento)->startOfDay();
                                            $diasDiff = (int) $hoje->diffInDays($dataVenc, false);
                                            $isPago = in_array($parcela->status, ['pago', 'liquidado']) || (float)$parcela->valor_saldo <= 0.005;
                                            $isReneg = $parcela->status === 'renegociado';
                                            $isAtraso = !$isPago && !$isReneg && ($parcela->status === 'vencido' || ($diasDiff < 0 && (float)$parcela->valor_saldo > 0.005));
                                        @endphp
                                        <tr class="hover:bg-slate-50/80 transition-colors {{ $isAtraso ? 'bg-rose-50/20' : '' }} {{ $isReneg ? 'opacity-60 bg-slate-50/50' : '' }}">
                                            <td class="py-2.5 px-3 text-center font-bold text-slate-900">
                                                {{ $parcela->numero_parcela == 0 ? 'Matrícula' : sprintf('%02d/%02d', $parcela->numero_parcela, $parcela->total_parcelas) }}
                                            </td>
                                            <td class="py-2.5 px-3 whitespace-nowrap font-medium {{ $isAtraso ? 'text-rose-700 font-bold' : 'text-slate-800' }}">
                                                {{ $dataVenc->format('d/m/Y') }}
                                                @if($isAtraso)
                                                    <span class="block text-[10px] text-rose-500 font-semibold">{{ abs($diasDiff) }} dias atrasado</span>
                                                @elseif($diasDiff === 0 && !$isPago)
                                                    <span class="block text-[10px] text-amber-600 font-bold">Vence Hoje</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-right text-slate-600">
                                                R$ {{ number_format($parcela->valor_nominal, 2, ',', '.') }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-bold {{ $isAtraso ? 'text-rose-700' : ($isPago ? 'text-emerald-700' : 'text-slate-900') }}">
                                                R$ {{ number_format($isPago ? $parcela->valor_nominal : $parcela->valor_saldo, 2, ',', '.') }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                                @if($isPago)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Liquidado</span>
                                                @elseif($isAtraso)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Em Atraso</span>
                                                @elseif($isReneg)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-200 text-slate-700 line-through">Renegociado</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800">Pendente</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    @if(!$isPago && !$isReneg)
                                                        <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $parcela->id]) }}" class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[11px] font-semibold transition" title="Receber no caixa escolar">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                                            Receber
                                                        </a>
                                                    @elseif($isPago)
                                                        <a href="{{ route('financeiro.titulos.recibo-pdf', $parcela->id) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-medium transition" title="Imprimir Recibo de Pagamento">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                                            Recibo
                                                        </a>
                                                    @endif

                                                    @if($parcela->cobrancaGateway && $parcela->cobrancaGateway->link_boleto_pdf)
                                                        <a href="{{ $parcela->cobrancaGateway->link_boleto_pdf }}" target="_blank" class="p-1 text-blue-600 hover:text-blue-800 transition" title="Baixar Boleto Bancário Asaas">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                                        </a>
                                                    @endif

                                                    <a href="{{ route('financeiro.titulos.show', $parcela->id) }}" class="p-1 text-slate-400 hover:text-slate-700 transition" title="Ver Detalhes">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-12 text-center text-slate-500">
                <div class="max-w-sm mx-auto space-y-2">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <p class="font-bold text-slate-700 text-sm m-0">Nenhum contrato localizado</p>
                    <p class="text-xs text-slate-400 m-0">Nenhum aluno ou contrato corresponde aos critérios de pesquisa digitados.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Rodapé com Paginação dos Contratos -->
    <footer class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-600 gap-3">
        <span>
            Mostrando <strong>{{ $contratosPaginados->firstItem() ?? 0 }}</strong> a <strong>{{ $contratosPaginados->lastItem() ?? 0 }}</strong> de <strong>{{ $contratosPaginados->total() }}</strong> contratos de alunos
        </span>
        <div class="pagination-wrapper ajax-pagination">
            {{ $contratosPaginados->appends(request()->except('page'))->links() }}
        </div>
    </footer>

@else
    <!-- ========================================================================= -->
    <!-- MODO 2: LISTA PLANA TRADICIONAL DE TODAS AS PARCELAS                       -->
    <!-- ========================================================================= -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
            <thead>
                <tr class="bg-slate-100/90 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                    @if($temAcoesEmLote)
                        <th class="py-3 px-3 text-center" style="width: 40px;" scope="col">
                            <input type="checkbox" id="check-todos-titulos" class="cursor-pointer rounded border-slate-300 text-brand-petroleum focus:ring-brand-petroleum" title="Selecionar todos os títulos da página">
                        </th>
                    @endif
                    <th class="py-3 px-4" scope="col">Vencimento</th>
                    <th class="py-3 px-4 text-center" scope="col">Parcela</th>
                    <th class="py-3 px-4" scope="col">Aluno / Matrícula</th>
                    <th class="py-3 px-4" scope="col">Responsável Financeiro</th>
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
                        @if($temAcoesEmLote)
                            <td class="py-3 px-3 text-center">
                                @if(!$isPago && !$isCancelado && !$isRenegociado)
                                    <input type="checkbox" class="check-titulo-item cursor-pointer rounded border-slate-300 text-brand-petroleum focus:ring-brand-petroleum" value="{{ $titulo->id }}" data-valor="{{ $valorSaldoNum }}" data-pago="{{ $isPago ? '1' : '0' }}">
                                @else
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>
                        @endif
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
                            @else
                                <span class="block text-[10px] text-slate-400">Em {{ $diasDiff }} dias</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap font-bold {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                            {{ $titulo->numero_parcela == 0 ? 'Matr.' : sprintf('%02d/%02d', $titulo->numero_parcela, $titulo->total_parcelas) }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-900 block">
                                {{ \iEducar\Financeiro\Support\AlunoLookup::nomeComCpf($titulo->aluno_id) }}
                            </span>
                            @if($titulo->contrato)
                                <span class="text-[11px] text-slate-500 font-normal">Contrato #{{ $titulo->contrato->numero_contrato }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ route('financeiro.responsaveis.show', $titulo->responsavel_financeiro_id) }}" class="font-semibold text-slate-800 hover:text-brand-petroleum hover:underline" style="text-decoration: none;">
                                {{ $titulo->responsavel->nome ?? '-' }}
                            </a>
                            @if(!empty($titulo->responsavel->cpf_cnpj_formatado))
                                <span class="block text-[10px] text-slate-400 font-mono">{{ $titulo->responsavel->cpf_cnpj_formatado }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-bold col-saldo {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                            R$ {{ number_format($isPago ? $titulo->valor_nominal : $titulo->valor_saldo, 2, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap col-status">
                            @if($isPago)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Liquidado</span>
                            @elseif($isAtrasado)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Em Atraso</span>
                            @elseif($isRenegociado)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 line-through">Renegociado</span>
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
                                    @if($isAtrasado && $titulo->responsavel_financeiro_id)
                                        <a href="{{ route('financeiro.renegociacao.index', ['responsavel_id' => $titulo->responsavel_financeiro_id]) }}" class="inline-flex items-center justify-center p-1.5 text-purple-600 hover:text-purple-700 hover:bg-purple-50 rounded transition" title="Renegociar Débito / Acordo">
                                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                                        </a>
                                    @endif
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
                        <td colspan="{{ $temAcoesEmLote ? 8 : 7 }}" class="py-12 text-center text-slate-500">
                            <div class="max-w-sm mx-auto space-y-2">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                    </svg>
                                </div>
                                <p class="font-bold text-slate-700 text-sm m-0">Nenhuma cobrança encontrada</p>
                                <p class="text-xs text-slate-400 m-0">Nenhum título corresponde aos critérios de pesquisa digitados.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Rodapé com Paginação da Lista Plana -->
    <footer class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-600 gap-3">
        <span>
            Mostrando <strong>{{ $titulos->firstItem() ?? 0 }}</strong> a <strong>{{ $titulos->lastItem() ?? 0 }}</strong> de <strong>{{ $titulos->total() }}</strong> cobranças
        </span>
        <div class="pagination-wrapper ajax-pagination">
            {{ $titulos->appends(request()->except('page'))->links() }}
        </div>
    </footer>
@endif

<script>
// Função para expandir e recolher o acordeão do contrato
function toggleAcordeon(id) {
    const el = document.getElementById(id);
    const chevron = document.getElementById('chevron-' + id);
    if (!el) return;
    
    if (el.classList.contains('hidden')) {
        el.classList.remove('hidden');
        if (chevron) chevron.classList.add('rotate-180');
    } else {
        el.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-180');
    }
}

// Alternar entre modo Aluno/Contrato e Lista Plana via AJAX
function mudarModoVisualizacao(novoModo) {
    const inputModo = document.getElementById('filtro-modo');
    if (inputModo) {
        inputModo.value = novoModo;
    }
    if (typeof aplicarFiltroAjax === 'function') {
        aplicarFiltroAjax();
    } else {
        const url = new URL(window.location.href);
        url.searchParams.set('modo', novoModo);
        window.location.href = url.toString();
    }
}
</script>
