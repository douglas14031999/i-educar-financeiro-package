@php
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
    <span class="text-xs font-medium text-cyan-100 flex items-center gap-1.5">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        Total de registros: {{ $titulos->total() }}
    </span>
</div>

<!-- Tabela Responsiva -->
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
                    @if($temAcoesEmLote)
                        <td class="py-3 px-3 text-center">
                            @if(!$isPago && !$isCancelado)
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
                            {{ \iEducar\Financeiro\Support\AlunoLookup::nomeComCpf($titulo->aluno_id) }}
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

<!-- Rodapé com Paginação -->
<footer class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-600 gap-3">
    <span>
        Mostrando <strong>{{ $titulos->firstItem() ?? 0 }}</strong> a <strong>{{ $titulos->lastItem() ?? 0 }}</strong> de <strong>{{ $titulos->total() }}</strong> cobranças
    </span>
    <div class="pagination-wrapper ajax-pagination">
        {{ $titulos->appends(request()->except('page'))->links() }}
    </div>
</footer>
