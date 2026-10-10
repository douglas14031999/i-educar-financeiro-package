@extends('financeiro::layouts.app')

@section('title', 'Painel de Inadimplência & Aging List')
@section('breadcrumb', 'Relatórios > Inadimplência')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="inadimplencia-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Painel de Inadimplência Escolar (Aging List)',
            'subtitle' => 'Monitoramento etário de cobranças e verificação jurídica para rematrícula (Art. 5º da Lei Federal nº 9.870/1999)',
            'breadcrumbs' => [
                ['label' => 'Relatórios', 'url' => route('financeiro.relatorios.index')],
                ['label' => 'Inadimplência']
            ],
            'actions' => '
                <a href="' . route('financeiro.relatorios.index') . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar aos Relatórios
                </a>
            '
        ])

        <!-- Indicadores por Faixa de Atraso (Aging) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($aging['faixas'] as $chave => $f)
                @php
                    $borderCor = 'border-l-brand-petroleum';
                    $textoCor = 'text-brand-petroleum';
                    if ($chave === 'faixa_acima_90') {
                        $borderCor = 'border-l-red-600';
                        $textoCor = 'text-red-700';
                    } elseif ($chave === 'faixa_61_90') {
                        $borderCor = 'border-l-amber-600';
                        $textoCor = 'text-amber-700';
                    } elseif ($chave === 'faixa_31_60') {
                        $borderCor = 'border-l-amber-500';
                        $textoCor = 'text-amber-600';
                    }
                @endphp
                <div class="bg-white border border-slate-200 border-l-4 {{ $borderCor }} shadow-xs p-4">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">{{ $f['nome'] }}</div>
                    <div class="text-lg md:text-xl font-bold font-mono {{ $textoCor }} mt-1">
                        R$ {{ number_format($f['total'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5">
                        {{ $f['qtd'] }} {{ $f['qtd'] === 1 ? 'título vencido' : 'títulos vencidos' }}
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Barra de Total Geral Inadimplente -->
        <div class="bg-red-50/70 border border-red-200 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-red-900">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 text-red-600 rounded-sm">
                    <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-red-800">Saldo Total Inadimplente Consolidado:</span>
                    <div class="text-xl md:text-2xl font-bold font-mono text-red-700">
                        R$ {{ number_format($aging['total_geral'], 2, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="text-xs font-semibold text-red-700 bg-red-100 px-3 py-1.5 rounded-full border border-red-200 self-start sm:self-auto">
                {{ $aging['total_titulos'] }} parcelas vencidas em cobrança ativa
            </div>
        </div>

        <!-- Consulta de Situação para Rematrícula (Art. 5º da Lei Federal nº 9.870/1999) -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="rematricula-card">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Consulta de Regularidade Financeira para Rematrícula (Lei 9.870/99)
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    Secretaria Escolar
                </span>
            </div>

            <div class="p-5">
                <form method="get" action="{{ route('financeiro.relatorios.inadimplencia') }}" class="flex flex-wrap items-end gap-3">
                    <div class="w-full sm:w-60">
                        <label for="aluno_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Código do Aluno (i-Educar):
                        </label>
                        <input type="number" name="aluno_id" id="aluno_id" value="{{ $alunoId }}" placeholder="Ex: 508" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                    </div>
                    <div class="flex items-center gap-2 pt-1 sm:pt-0">
                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Verificar Débitos
                        </button>
                        @if($alunoId)
                            <a href="{{ route('financeiro.relatorios.inadimplencia') }}" class="px-3 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium transition" style="text-decoration: none;">
                                Limpar
                            </a>
                        @endif
                    </div>
                </form>

                @if($resultadoAluno)
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        @if($resultadoAluno['pode_rematricular'])
                            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start gap-3">
                                <svg class="w-6 h-6 text-emerald-600 shrink-0 mt-0.5" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                <div>
                                    <h4 class="font-bold text-sm text-emerald-900" style="margin: 0;">REMATRÍCULA AUTORIZADA — Aluno Regular</h4>
                                    <p class="text-xs text-emerald-800 mt-1 leading-relaxed">{{ $resultadoAluno['fundamentacao_legal'] }}</p>
                                </div>
                            </div>
                        @else
                            <div class="p-4 bg-red-50 border border-red-200 text-red-900 flex items-start gap-3">
                                <svg class="w-6 h-6 text-red-600 shrink-0 mt-0.5" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                <div>
                                    <h4 class="font-bold text-sm text-red-900" style="margin: 0;">REMATRÍCULA BLOQUEADA POR INADIMPLÊNCIA</h4>
                                    <p class="text-xs text-red-800 mt-1 leading-relaxed">
                                        O aluno possui <strong>{{ $resultadoAluno['qtd_titulos_vencidos'] }} título(s) vencido(s)</strong> totalizando 
                                        <strong class="font-mono">R$ {{ number_format($resultadoAluno['total_debito'], 2, ',', '.') }}</strong> 
                                        (Maior atraso registrado: {{ $resultadoAluno['maior_atraso_dias'] }} dias).
                                    </p>
                                    <p class="text-[11px] text-red-700 mt-1 italic">
                                        {{ $resultadoAluno['fundamentacao_legal'] }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        <!-- Listagem dos Principais Títulos Vencidos por Faixa -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="titulos-inadimplentes-table">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Títulos em Atraso (Amostragem Recente para Ação de Cobrança)
                    </h2>
                </div>
                <div class="text-xs text-slate-300">
                    Ações de cobrança imediata via WhatsApp ou Baixa no Balcão
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4" width="70">ID</th>
                            <th class="py-3 px-4" width="100">Vencimento</th>
                            <th class="py-3 px-4" width="110">Dias Atraso</th>
                            <th class="py-3 px-4">Responsável / Aluno</th>
                            <th class="py-3 px-4" width="110">Competência</th>
                            <th class="py-3 px-4 text-right" width="130">Saldo Devedor</th>
                            <th class="py-3 px-4 text-center" width="140">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @php $countExibidos = 0; @endphp
                        @foreach($aging['faixas'] as $f)
                            @foreach($f['titulos'] as $titulo)
                                @if($countExibidos < 50)
                                    @php $countExibidos++; @endphp
                                    <tr class="hover:bg-slate-50/80 transition duration-150">
                                        <td class="py-3.5 px-4 font-mono font-medium text-slate-500">
                                            #{{ $titulo->id }}
                                        </td>
                                        <td class="py-3.5 px-4 font-mono font-bold text-red-600">
                                            {{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200 font-mono">
                                                {{ \Carbon\Carbon::parse($titulo->data_vencimento)->diffInDays(now()) }} dias
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="font-semibold text-slate-900">
                                                {{ $titulo->responsavel->nome ?? '-' }}
                                            </div>
                                            <div class="text-[11px] text-slate-400">
                                                Aluno #{{ $titulo->aluno_id }}
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 font-mono text-slate-600">
                                            {{ $titulo->competencia }}
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-mono font-bold text-red-700">
                                            R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <a href="{{ route('financeiro.titulos.show', $titulo->id) }}" class="inline-flex items-center p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium border border-slate-200 transition" title="Ver Detalhes do Título" style="text-decoration: none;">
                                                    <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('financeiro.titulos.notificar-whatsapp', $titulo->id) }}" target="_blank" class="inline-flex items-center p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-medium border border-emerald-200 transition" title="Notificar via WhatsApp" style="text-decoration: none;">
                                                    <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}" class="inline-flex items-center gap-1 px-2 py-1.5 btn-ieducar-action btn-ieducar-green" title="Baixa no Balcão" style="text-decoration: none;">
                                                    <svg class="w-3 h-3" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                                    </svg>
                                                    Baixar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endforeach

                        @if($countExibidos === 0)
                            <tr>
                                <td colspan="7" class="py-12 px-4 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-emerald-500 mb-3" width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        <span class="text-base font-semibold text-emerald-800">Parabéns! Nenhuma cobrança em atraso encontrada</span>
                                        <p class="text-xs text-slate-500 mt-1 max-w-sm">
                                            Todos os títulos de mensalidades e taxas escolares estão rigorosamente em dia.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</div>
@endsection
