@extends('financeiro::layouts.app')

@section('title', 'Checkout — Contrato #' . $contrato->numero_contrato)
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
    <style>
        /* ================================================================
           CHECKOUT STYLES — Scoped to .financeiro-dashboard-container
           ================================================================ */

        /* --- Checkout Hero Card --- */
        .co-hero-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }

        /* --- Status Badge --- */
        .co-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            border-radius: 3px;
        }
        .co-badge-pix       { background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; }
        .co-badge-cartao    { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
        .co-badge-boleto    { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }
        .co-badge-carne     { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
        .co-badge-quitado   { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }

        /* --- Main Panel --- */
        .co-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            border-radius: 4px;
            overflow: hidden;
        }
        .co-panel-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .co-panel-body {
            padding: 24px;
        }

        /* --- QR Code Block --- */
        .co-qr-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            width: 220px;
            height: 220px;
        }
        .co-qr-wrapper img {
            width: 188px !important;
            height: 188px !important;
            display: block;
        }
        .co-qr-placeholder {
            width: 188px;
            height: 188px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 11px;
            text-align: center;
        }

        /* --- Pix Layout & Spacing --- */
        .co-pix-container {
            display: flex;
            flex-direction: column;
            gap: 28px;
        }
        .co-pix-qr-col {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        .co-pix-details-col {
            flex: 1;
            min-width: 0;
            width: 100%;
        }
        @media (min-width: 768px) {
            .co-pix-container {
                flex-direction: row;
                align-items: flex-start;
                gap: 36px !important;
            }
            .co-pix-qr-col {
                margin-right: 36px !important; /* Espaçamento garantido mesmo sem suporte a flex-gap */
            }
        }

        /* --- Pix Countdown --- */
        .co-countdown {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #c2410c;
        }
        .co-countdown-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ea580c;
            animation: co-pulse 1.5s infinite;
        }
        @keyframes co-pulse {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:.5; transform:scale(.85); }
        }

        /* --- Copy Input Group --- */
        .co-copy-group {
            display: flex;
            align-items: stretch;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
            background: #ffffff;
        }
        .co-copy-group input {
            flex: 1;
            min-width: 0;
            padding: 9px 12px;
            font-size: 11px;
            font-family: 'Courier New', monospace;
            color: #334155;
            background: #f8fafc;
            border: none;
            outline: none;
        }
        .co-copy-btn {
            padding: 0 14px;
            background: #47728f;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s;
            letter-spacing: .04em;
        }
        .co-copy-btn:hover { background: #355972; }
        .co-copy-btn.co-copied { background: #059669; }

        /* --- Real-time pulse indicator --- */
        .co-pulse-ring {
            position: relative;
            width: 10px;
            height: 10px;
            display: inline-flex;
            flex-shrink: 0;
        }
        .co-pulse-ring span:first-child {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: #38bdf8;
            opacity: .6;
            animation: co-ring 1.8s ease-out infinite;
        }
        .co-pulse-ring span:last-child {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #0284c7;
            display: block;
        }
        @keyframes co-ring {
            0%   { transform: scale(1); opacity: .6; }
            80%  { transform: scale(2.2); opacity: 0; }
            100% { transform: scale(2.2); opacity: 0; }
        }

        /* --- Success confetti card --- */
        .co-success-panel {
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 60%);
            border: 2px solid #bbf7d0;
            border-radius: 6px;
            text-align: center;
        }
        .co-success-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dcfce7, #a7f3d0);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 0 0 6px rgba(16,185,129,.12);
        }

        /* --- Step List (instruções numbered) --- */
        .co-step-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .co-step-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 12px;
            color: #475569;
            line-height: 1.5;
        }
        .co-step-num {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #47728f;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* --- Info Row (boleto details) --- */
        .co-info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
            color: #475569;
        }
        .co-info-row:last-child { border-bottom: none; }
        .co-info-row strong { color: #1e293b; font-weight: 600; }

        /* --- Responsive tweaks --- */
        @media (max-width: 640px) {
            .co-qr-wrapper { width: 180px; height: 180px; }
            .co-qr-wrapper img { width: 148px !important; height: 148px !important; }
        }
    </style>
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-5" data-purpose="contrato-checkout-container">

        {{-- ── CABEÇALHO ── --}}
        @include('financeiro::partials.page-header', [
            'title'    => 'Fechamento e Pagamento da Matrícula',
            'subtitle' => 'Contrato ' . $contrato->numero_contrato . ' · ' . \iEducar\Financeiro\Support\AlunoLookup::nomeComCpf($contrato->aluno_id) . ' · ' . $contrato->ano_letivo,
            'breadcrumbs' => [
                ['label' => 'Contratos', 'url' => route('financeiro.contratos.index')],
                ['label' => $contrato->numero_contrato, 'url' => route('financeiro.contratos.show', $contrato->id)],
                ['label' => 'Checkout'],
            ],
            'actions' => '
                <a href="' . route('financeiro.contratos.show', $contrato->id) . '"
                   class="inline-flex items-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-outline"
                   style="text-decoration:none;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                    Ver Contrato
                </a>
            '
        ])

        {{-- ── HERO: RESUMO DO CONTRATO ── --}}
        <div class="co-hero-card p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    {{-- Avatar / Ícone --}}
                    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                         style="background:rgba(71,114,143,.12);">
                        <svg width="22" height="22" fill="none" stroke="#47728f" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Contrato Formalizado</div>
                        <div class="text-base font-bold text-slate-900 mt-0.5">{{ $contrato->numero_contrato }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Responsável:
                            <span class="font-semibold text-slate-700">{{ $contrato->responsavel->nome ?? '—' }}</span>
                            @if($contrato->responsavel?->cpf_cnpj)
                                · CPF {{ $contrato->responsavel->cpf_cnpj }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-5 sm:gap-8 flex-wrap">
                    <div class="text-right">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Valor Total</div>
                        <div class="text-2xl font-extrabold text-slate-900">
                            R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}
                        </div>
                        <div class="text-[11px] text-slate-400">Ano Letivo {{ $contrato->ano_letivo }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Status</div>
                        @php
                            $statusMap = [
                                'quitado'            => ['label'=>'Quitado',            'color'=>'#059669','bg'=>'#f0fdf4','border'=>'#bbf7d0'],
                                'aguardando_pagamento'=>['label'=>'Aguardando Pagamento','color'=>'#b45309','bg'=>'#fffbeb','border'=>'#fde68a'],
                                'ativo'              => ['label'=>'Ativo',              'color'=>'#0369a1','bg'=>'#eff6ff','border'=>'#bfdbfe'],
                                'inadimplente'       => ['label'=>'Inadimplente',       'color'=>'#be123c','bg'=>'#fff1f2','border'=>'#fecdd3'],
                                'cancelado'          => ['label'=>'Cancelado',          'color'=>'#6b7280','bg'=>'#f9fafb','border'=>'#e5e7eb'],
                            ];
                            $st = $statusMap[$contrato->status] ?? ['label'=>ucfirst($contrato->status),'color'=>'#334155','bg'=>'#f8fafc','border'=>'#e2e8f0'];
                        @endphp
                        <span style="display:inline-block;padding:3px 10px;font-size:11px;font-weight:700;
                                     letter-spacing:.06em;text-transform:uppercase;border-radius:20px;
                                     color:{{ $st['color'] }};background:{{ $st['bg'] }};border:1px solid {{ $st['border'] }};">
                            {{ $st['label'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════════
             CENÁRIO 0 — RATEIO FINANCEIRO (2 OU MAIS RESPONSÁVEIS)
             ═══════════════════════════════════════════════════════════════════════ --}}
        @if(($ehRateio ?? false) && ($rateioResponsaveis ?? collect())->isNotEmpty())

            {{-- Banner Explicativo do Rateio --}}
            <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#47728f] text-white flex items-center justify-center flex-shrink-0 shadow-sm ring-2 ring-blue-100">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider m-0">Checkout com Rateio Financeiro</h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300">
                                {{ $rateioResponsaveis->count() }} Responsáveis Definidos
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-0.5 mb-0">
                            As cobranças foram geradas separadamente para cada responsável conforme as porcentagens e modalidades acordadas.
                        </p>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <span class="text-xs text-slate-500 font-semibold block">Total do Contrato</span>
                    <span class="text-base font-extrabold text-slate-900">R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}</span>
                </div>
            </div>

            {{-- Duas Seções: uma para cada responsável do rateio --}}
            <div class="space-y-6">
                @foreach($rateioResponsaveis as $idx => $itemRateio)
                    @php
                        $ordem = ($idx === 0) ? '1º' : (($idx === 1) ? '2º' : ($idx + 1) . 'º');
                        $respObj = $itemRateio->responsavel;
                        $nomeResp = $respObj?->nome ?? ('Responsável ' . ($idx + 1));
                        $cpfResp = $respObj?->cpf_cnpj ?? 'Não informado';
                        $telRespRaw = preg_replace('/\D/', '', $respObj?->telefone_celular ?? $respObj?->telefone_fixo ?? '');
                        $cobranca = $itemRateio->cobranca_ativa;
                        $forma = $itemRateio->forma_pagamento;
                        $titulosResp = $itemRateio->titulos ?? collect();
                        $primeiroTit = $itemRateio->primeiro_titulo ?? $titulosResp->first();
                        $estaQuitado = ($itemRateio->status === 'quitado') || ($contrato->status === 'quitado');
                    @endphp

                    <div class="co-panel border-2 {{ $idx === 0 ? 'border-sky-300' : 'border-indigo-300' }} shadow-sm">
                        {{-- Header da Seção do Responsável --}}
                        <div class="co-panel-header bg-slate-50/70 border-b border-slate-200">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-full {{ $idx === 0 ? 'bg-sky-600' : 'bg-indigo-600' }} text-white flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-xs">
                                        {{ $ordem }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-bold uppercase tracking-wider {{ $idx === 0 ? 'text-sky-800' : 'text-indigo-800' }}">
                                                {{ $ordem }} Responsável Financeiro
                                            </span>
                                            <span class="text-xs text-slate-400">·</span>
                                            <span class="text-sm font-bold text-slate-900">{{ $nomeResp }}</span>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1 flex items-center gap-3 flex-wrap">
                                            <span><strong>CPF:</strong> {{ $cpfResp }}</span>
                                            @if($respObj?->telefone_celular)
                                                <span>·</span>
                                                <span><strong>WhatsApp/Cel:</strong> {{ $respObj->telefone_celular }}</span>
                                            @endif
                                            @if($respObj?->pivot?->parentesco ?? $respObj?->parentesco)
                                                <span>·</span>
                                                <span><strong>Parentesco:</strong> {{ ucfirst($respObj?->pivot?->parentesco ?? $respObj?->parentesco) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 text-right flex-shrink-0">
                                    <div>
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Cota / Rateio</div>
                                        <div class="text-lg font-extrabold text-slate-900">
                                            R$ {{ number_format($itemRateio->valor_total, 2, ',', '.') }}
                                            <span class="text-xs font-semibold text-slate-500">({{ $itemRateio->percentual }}%)</span>
                                        </div>
                                    </div>
                                    <div>
                                        @if($estaQuitado)
                                            <span class="co-badge co-badge-quitado">✓ Quitado</span>
                                        @elseif($forma === 'pix')
                                            <span class="co-badge co-badge-pix">⚡ Pix Instantâneo</span>
                                        @elseif($forma === 'boleto')
                                            <span class="co-badge co-badge-boleto">📄 Boleto (1x)</span>
                                        @elseif($forma === 'carne')
                                            <span class="co-badge co-badge-carne">📚 Carnê ({{ $titulosResp->count() }}x)</span>
                                        @elseif($forma === 'cartao_credito')
                                            <span class="co-badge co-badge-cartao">🌐 Cartão Link (12x)</span>
                                        @else
                                            <span class="co-badge" style="background:#f1f5f9;color:#475569;">{{ ucfirst($forma) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Corpo da Seção Conforme a Modalidade do Responsável --}}
                        <div class="co-panel-body">
                            
                            {{-- SUB-CENÁRIO A: QUITADO --}}
                            @if($estaQuitado)
                                <div class="p-6 bg-emerald-50/70 border border-emerald-200 rounded-md text-center space-y-3">
                                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto text-xl font-bold">
                                        ✓
                                    </div>
                                    <h3 class="text-base font-bold text-slate-900 m-0">Pagamento Deste Responsável Confirmado!</h3>
                                    <p class="text-xs text-slate-600 max-w-md mx-auto">
                                        A cota deste responsável (R$ {{ number_format($itemRateio->valor_total, 2, ',', '.') }}) foi devidamente liquidada no sistema.
                                    </p>
                                    @if($primeiroTit)
                                        <div class="pt-2">
                                            <a href="{{ route('financeiro.titulos.recibo-pdf', $primeiroTit->id) }}" target="_blank"
                                               class="inline-flex items-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs font-semibold"
                                               style="text-decoration:none;">
                                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                                </svg>
                                                Imprimir Recibo Oficial
                                            </a>
                                        </div>
                                    @endif
                                </div>

                            {{-- SUB-CENÁRIO B: PIX --}}
                            @elseif($forma === 'pix')
                                <div class="co-pix-container flex flex-col md:flex-row items-center md:items-start" style="gap: 36px;">
                                    {{-- QR Code --}}
                                    <div class="co-pix-qr-col flex-shrink-0 flex flex-col items-center gap-3" style="margin-right: 36px;">
                                        <div class="co-qr-wrapper">
                                            @if($cobranca && ($cobranca->qr_code_base64 || $cobranca->pix_qr_code_base64))
                                                <img src="data:image/png;base64,{{ $cobranca->qr_code_base64 ?? $cobranca->pix_qr_code_base64 }}"
                                                     alt="QR Code Pix — {{ $nomeResp }}">
                                            @else
                                                <div class="co-qr-placeholder">
                                                    <svg width="40" height="40" fill="none" stroke="#94a3b8" viewBox="0 0 24 24">
                                                        <path d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                                                    </svg>
                                                    <span>QR Code sendo<br>gerado no Asaas...</span>
                                                    <span style="font-size:10px;color:#94a3b8;">Recarregue a página</span>
                                                </div>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-slate-500 text-center max-w-[200px]">
                                            Aponte a câmera do aplicativo bancário
                                        </span>
                                    </div>

                                    {{-- Ações Pix --}}
                                    <div class="co-pix-details-col flex-1 w-full space-y-4">
                                        {{-- Copia e Cola --}}
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-2">
                                                Pix Copia e Cola — Cota {{ $ordem }} Responsável
                                            </label>
                                            <div class="co-copy-group">
                                                <input type="text" id="co-pix-input-{{ $idx }}" readonly
                                                       value="{{ $cobranca?->pix_copia_cola ?? 'Chave Pix em processamento...' }}"
                                                       title="Código Pix Copia e Cola">
                                                <button type="button" class="co-copy-btn" id="co-pix-btn-{{ $idx }}"
                                                        onclick="copiaCola('co-pix-input-{{ $idx }}','co-pix-btn-{{ $idx }}')">
                                                    Copiar
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Info Box --}}
                                        <div class="bg-slate-50 border border-slate-200 rounded-md p-3.5 text-xs">
                                            <div class="co-info-row">
                                                <span>Valor da Cota ({{ $itemRateio->percentual }}%)</span>
                                                <strong class="text-sm text-slate-900">R$ {{ number_format($itemRateio->valor_total, 2, ',', '.') }}</strong>
                                            </div>
                                            <div class="co-info-row">
                                                <span>Validade</span>
                                                <strong>24 horas após emissão</strong>
                                            </div>
                                            <div class="co-info-row">
                                                <span>Confirmação Bancária</span>
                                                <strong class="text-emerald-700">Automática via Webhook Asaas</strong>
                                            </div>
                                        </div>

                                        {{-- Botões --}}
                                        <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                            @php
                                                $msgPixResp = urlencode(
                                                    "Olá " . $nomeResp . "!\n" .
                                                    "Segue o Pix para pagamento da sua cota (" . $itemRateio->percentual . "%) da matrícula no Contrato " . $contrato->numero_contrato . ".\n" .
                                                    "Valor: R$ " . number_format($itemRateio->valor_total, 2, ',', '.') . "\n\n" .
                                                    "Código Pix Copia e Cola:\n" .
                                                    ($cobranca?->pix_copia_cola ?? '')
                                                );
                                            @endphp
                                            @if($telRespRaw)
                                                <a href="https://wa.me/55{{ $telRespRaw }}?text={{ $msgPixResp }}" target="_blank"
                                                   class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs font-semibold"
                                                   style="text-decoration:none;">
                                                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                                    </svg>
                                                    Enviar Pix via WhatsApp
                                                </a>
                                            @endif
                                            @if($cobranca?->link_pagamento)
                                                <a href="{{ $cobranca->link_pagamento }}" target="_blank"
                                                   class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-blue text-xs font-semibold"
                                                   style="text-decoration:none;">
                                                    Abrir Página Asaas
                                                </a>
                                            @endif
                                        </div>

                                        {{-- Status em tempo real no rateio --}}
                                        <div class="flex items-center gap-2 pt-2 text-xs text-slate-500">
                                            <span class="co-pulse-ring">
                                                <span></span><span></span>
                                            </span>
                                            <span class="co-realtime-text">Monitorando confirmação bancária em tempo real...</span>
                                        </div>
                                    </div>
                                </div>

                            {{-- SUB-CENÁRIO C: BOLETO --}}
                            @elseif($forma === 'boleto')
                                <div class="space-y-4">
                                    <p class="text-xs text-slate-600 m-0">
                                        Boleto bancário à vista registrado no Asaas para <strong>{{ $nomeResp }}</strong>. O título será liquidado automaticamente após a compensação bancária.
                                    </p>

                                    {{-- Linha Digitável --}}
                                    @if($cobranca?->linha_digitavel)
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-2">
                                                Linha Digitável do Boleto — Cota {{ $ordem }} Responsável
                                            </label>
                                            <div class="co-copy-group">
                                                <input type="text" id="co-boleto-input-{{ $idx }}" readonly
                                                       value="{{ $cobranca->linha_digitavel }}"
                                                       title="Linha digitável do boleto">
                                                <button type="button" class="co-copy-btn" id="co-boleto-btn-{{ $idx }}"
                                                        onclick="copiaCola('co-boleto-input-{{ $idx }}','co-boleto-btn-{{ $idx }}')">
                                                    Copiar
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Detalhes --}}
                                    <div class="bg-slate-50 border border-slate-200 rounded-md p-3.5 text-xs">
                                        <div class="co-info-row">
                                            <span>Valor da Cota ({{ $itemRateio->percentual }}%)</span>
                                            <strong class="text-sm text-slate-900">R$ {{ number_format($itemRateio->valor_total, 2, ',', '.') }}</strong>
                                        </div>
                                        @if($primeiroTit?->data_vencimento)
                                            <div class="co-info-row">
                                                <span>Data de Vencimento</span>
                                                <strong>{{ \Carbon\Carbon::parse($primeiroTit->data_vencimento)->format('d/m/Y') }}</strong>
                                            </div>
                                        @endif
                                        <div class="co-info-row">
                                            <span>Status da Cobrança</span>
                                            <strong class="text-amber-700">Aguardando Pagamento</strong>
                                        </div>
                                    </div>

                                    {{-- Botões do Boleto --}}
                                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                        @if($cobranca?->link_boleto_pdf)
                                            <a href="{{ $cobranca->link_boleto_pdf }}" target="_blank"
                                               class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs font-semibold"
                                               style="text-decoration:none;">
                                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                                </svg>
                                                Baixar Boleto (PDF)
                                            </a>
                                        @endif
                                        @if($cobranca?->link_pagamento)
                                            <a href="{{ $cobranca->link_pagamento }}" target="_blank"
                                               class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-blue text-xs font-semibold"
                                               style="text-decoration:none;">
                                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                                </svg>
                                                Abrir Boleto Online
                                            </a>
                                        @endif
                                        @php
                                            $msgBoletoResp = urlencode(
                                                "Olá " . $nomeResp . "!\n" .
                                                "Segue o boleto para pagamento da sua cota (" . $itemRateio->percentual . "%) no Contrato " . $contrato->numero_contrato . ".\n" .
                                                "Valor: R$ " . number_format($itemRateio->valor_total, 2, ',', '.') . "\n" .
                                                ($cobranca?->linha_digitavel ? "Linha Digitável: " . $cobranca->linha_digitavel . "\n" : "") .
                                                ($cobranca?->link_boleto_pdf ? "Link do PDF: " . $cobranca->link_boleto_pdf : ($cobranca?->link_pagamento ?? ''))
                                            );
                                        @endphp
                                        @if($telRespRaw)
                                            <a href="https://wa.me/55{{ $telRespRaw }}?text={{ $msgBoletoResp }}" target="_blank"
                                               class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-outline text-xs font-semibold"
                                               style="text-decoration:none;">
                                                Enviar WhatsApp
                                            </a>
                                        @endif
                                    </div>
                                </div>

                            {{-- SUB-CENÁRIO D: CARNÊ ESCOLAR --}}
                            @elseif($forma === 'carne')
                                @php
                                    $qtdParcResp = $titulosResp->count();
                                    $valParcResp = $qtdParcResp > 0 ? round($itemRateio->valor_total / $qtdParcResp, 2) : 0;
                                    $titulosOrdenados = $titulosResp->sortBy('numero_parcela');
                                @endphp
                                <div class="space-y-4">
                                    <p class="text-xs text-slate-600 m-0">
                                        Carnê escolar individual com <strong>{{ $qtdParcResp }} parcelas</strong> geradas para <strong>{{ $nomeResp }}</strong>.
                                    </p>

                                    {{-- Resumo das Parcelas --}}
                                    <div class="bg-slate-50 border border-slate-200 rounded-md p-3.5 text-xs">
                                        <div class="co-info-row">
                                            <span>Total do Carnê deste Responsável</span>
                                            <strong class="text-sm text-slate-900">R$ {{ number_format($itemRateio->valor_total, 2, ',', '.') }} ({{ $itemRateio->percentual }}%)</strong>
                                        </div>
                                        <div class="co-info-row">
                                            <span>Quantidade de Parcelas</span>
                                            <strong>{{ $qtdParcResp }}x de aprox. R$ {{ number_format($valParcResp, 2, ',', '.') }}</strong>
                                        </div>
                                        <div class="co-info-row">
                                            <span>Primeiro Vencimento</span>
                                            <strong>{{ $titulosOrdenados->first() ? \Carbon\Carbon::parse($titulosOrdenados->first()->data_vencimento)->format('d/m/Y') : '—' }}</strong>
                                        </div>
                                        <div class="co-info-row">
                                            <span>Último Vencimento</span>
                                            <strong>{{ $titulosOrdenados->last() ? \Carbon\Carbon::parse($titulosOrdenados->last()->data_vencimento)->format('d/m/Y') : '—' }}</strong>
                                        </div>
                                    </div>

                                    {{-- Botões Carnê --}}
                                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                        <a href="{{ route('financeiro.contratos.imprimir-carne', ['id' => $contrato->id, 'responsavel_id' => $respObj?->id]) }}" target="_blank"
                                           class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs font-semibold"
                                           style="text-decoration:none;">
                                            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                            </svg>
                                            Imprimir Carnê Oficial (PDF)
                                        </a>
                                        @if($cobranca?->carne_url)
                                            <a href="{{ $cobranca->carne_url }}" target="_blank"
                                               class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-blue text-xs font-semibold"
                                               style="text-decoration:none;">
                                                Ver no Asaas
                                            </a>
                                        @endif
                                        @php
                                            $msgCarneResp = urlencode(
                                                "Olá " . $nomeResp . "!\n" .
                                                "Seu carnê escolar com " . $qtdParcResp . " parcelas referente ao Contrato " . $contrato->numero_contrato . " já está disponível.\n" .
                                                "Valor Total da sua cota: R$ " . number_format($itemRateio->valor_total, 2, ',', '.') . "\n" .
                                                "Qualquer dúvida procure a secretaria da escola."
                                            );
                                        @endphp
                                        @if($telRespRaw)
                                            <a href="https://wa.me/55{{ $telRespRaw }}?text={{ $msgCarneResp }}" target="_blank"
                                               class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-outline text-xs font-semibold"
                                               style="text-decoration:none;">
                                                Enviar WhatsApp
                                            </a>
                                        @endif
                                    </div>
                                </div>

                            {{-- SUB-CENÁRIO E: CARTÃO DE CRÉDITO ONLINE (LINK) --}}
                            @elseif($forma === 'cartao_credito')
                                @php
                                    $linkUrl = $cobranca?->link_pagamento ?? $contrato->payment_link_url;
                                @endphp
                                <div class="space-y-4">
                                    <p class="text-xs text-slate-600 m-0">
                                        Link de checkout do Asaas gerado para <strong>{{ $nomeResp }}</strong> pagar em até 12x no cartão de crédito com repasse de juros.
                                    </p>

                                    @if($linkUrl)
                                        <div>
                                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-2">
                                                Link de Pagamento Online — Cota {{ $ordem }} Responsável
                                            </label>
                                            <div class="co-copy-group">
                                                <input type="text" id="co-link-input-{{ $idx }}" readonly
                                                       value="{{ $linkUrl }}"
                                                       title="Link de pagamento Asaas">
                                                <button type="button" class="co-copy-btn" id="co-link-btn-{{ $idx }}"
                                                        onclick="copiaCola('co-link-input-{{ $idx }}','co-link-btn-{{ $idx }}')">
                                                    Copiar
                                                </button>
                                            </div>
                                        </div>

                                        <div class="bg-slate-50 border border-slate-200 rounded-md p-3.5 text-xs">
                                            <div class="co-info-row">
                                                <span>Valor da Cota ({{ $itemRateio->percentual }}%)</span>
                                                <strong class="text-sm text-slate-900">R$ {{ number_format($itemRateio->valor_total, 2, ',', '.') }}</strong>
                                            </div>
                                            <div class="co-info-row">
                                                <span>Parcelamento</span>
                                                <strong>Em até 12x no cartão com repasse de juros</strong>
                                            </div>
                                            <div class="co-info-row">
                                                <span>Confirmação</span>
                                                <strong class="text-emerald-700">Automática via Webhook</strong>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                            <a href="{{ $linkUrl }}" target="_blank"
                                               class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-blue text-xs font-semibold"
                                               style="text-decoration:none;">
                                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                                </svg>
                                                Abrir Checkout Asaas
                                            </a>
                                            @php
                                                $msgLinkResp = urlencode(
                                                    "Olá " . $nomeResp . "!\n" .
                                                    "Segue o link para pagar a sua cota (" . $itemRateio->percentual . "%) da matrícula em até 12x no cartão: " . $linkUrl
                                                );
                                            @endphp
                                            @if($telRespRaw)
                                                <a href="https://wa.me/55{{ $telRespRaw }}?text={{ $msgLinkResp }}" target="_blank"
                                                   class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs font-semibold"
                                                   style="text-decoration:none;">
                                                    Enviar WhatsApp
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <div class="p-3 bg-amber-50 border border-amber-200 rounded text-xs text-amber-800">
                                            Link de pagamento em processamento no gateway. Recarregue a página em instantes.
                                        </div>
                                    @endif
                                </div>

                            {{-- SUB-CENÁRIO F: DINHEIRO / CARTÃO PRESENCIAL / OUTROS --}}
                            @else
                                <div class="p-4 bg-slate-50 border border-slate-200 rounded-md text-xs space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-700">Modalidade Presencial: {{ ucfirst($forma) }}</span>
                                        <span class="font-bold text-slate-900">R$ {{ number_format($itemRateio->valor_total, 2, ',', '.') }}</span>
                                    </div>
                                    <p class="text-slate-500 m-0">
                                        Cobrança registrada para quitação no caixa da escola.
                                    </p>
                                    @if($primeiroTit)
                                        <div class="pt-1">
                                            <a href="{{ route('financeiro.titulos.recibo-pdf', $primeiroTit->id) }}" target="_blank"
                                               class="inline-flex items-center gap-2 px-3 py-1.5 btn-ieducar-action btn-ieducar-outline text-xs"
                                               style="text-decoration:none;">
                                                Emitir Recibo
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>

        {{-- ═══════════════════════════════════════════════════════════════════════
             CENÁRIOS TRADICIONAIS (TITULAR ÚNICO)
             ═══════════════════════════════════════════════════════════════════════ --}}
        @elseif($contrato->status === 'quitado')

            <div class="co-success-panel p-8 md:p-10">
                <div class="co-success-icon">
                    <svg width="36" height="36" fill="none" stroke="#059669" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-900 m-0">Pagamento Confirmado!</h2>
                <p class="text-sm text-slate-500 mt-2 mb-6 max-w-md mx-auto">
                    @if($contrato->forma_pagamento === 'cartao_presencial')
                        Baixa registrada via <strong class="text-slate-700">Cartão Presencial (Maquininha POS)</strong>.
                        @if($contrato->cartao_bandeira)
                            Bandeira: <strong class="text-slate-700">{{ strtoupper($contrato->cartao_bandeira) }}</strong>.
                        @endif
                        @if($contrato->cartao_nsu)
                            NSU/Autorização: <strong class="text-slate-700">{{ $contrato->cartao_nsu }}</strong>.
                        @endif
                        @if($contrato->cartao_tipo)
                            Modalidade: <strong class="text-slate-700">{{ match($contrato->cartao_tipo) {
                                'debito'            => 'Débito',
                                'credito_vista'     => 'Crédito à Vista',
                                'credito_parcelado' => 'Crédito Parcelado',
                                default             => $contrato->cartao_tipo,
                            } }}</strong>.
                        @endif
                    @else
                        Baixa registrada no <strong class="text-slate-700">Caixa Escolar (Dinheiro à Vista)</strong>.
                        O contrato foi automaticamente quitado no sistema.
                    @endif
                </p>

                {{-- Detalhe do Cartão POS --}}
                @if($contrato->forma_pagamento === 'cartao_presencial' && ($contrato->cartao_bandeira || $contrato->cartao_nsu))
                    <div class="max-w-sm mx-auto mb-6 bg-white border border-slate-200 rounded-md overflow-hidden">
                        @if($contrato->cartao_bandeira)
                            <div class="co-info-row px-4">
                                <span>Bandeira</span>
                                <strong>{{ strtoupper($contrato->cartao_bandeira) }}</strong>
                            </div>
                        @endif
                        @if($contrato->cartao_nsu)
                            <div class="co-info-row px-4">
                                <span>NSU / Autorização</span>
                                <strong class="font-mono">{{ $contrato->cartao_nsu }}</strong>
                            </div>
                        @endif
                        @if($contrato->cartao_tipo)
                            <div class="co-info-row px-4">
                                <span>Modalidade</span>
                                <strong>{{ match($contrato->cartao_tipo) {
                                    'debito' => 'Débito',
                                    'credito_vista' => 'Crédito à Vista',
                                    'credito_parcelado' => 'Crédito Parcelado',
                                    default => $contrato->cartao_tipo,
                                } }}</strong>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-center gap-3">
                    @if($primeiroTitulo)
                        <a href="{{ route('financeiro.titulos.recibo-pdf', $primeiroTitulo->id) }}" target="_blank"
                           class="inline-flex items-center gap-2 px-6 py-2.5 btn-ieducar-action btn-ieducar-green text-sm font-semibold"
                           style="text-decoration:none;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                            Imprimir Recibo Oficial
                        </a>
                    @endif
                    <a href="{{ route('financeiro.contratos.show', $contrato->id) }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 btn-ieducar-action btn-ieducar-outline text-sm"
                       style="text-decoration:none;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                        Ver Contrato Completo
                    </a>
                </div>
            </div>

        {{-- ═══════════════════════════════════════════════════════════════════════
             CENÁRIO 2 — PIX INSTANTÂNEO (QR CODE 24H)
             ═══════════════════════════════════════════════════════════════════════ --}}
        @elseif($contrato->forma_pagamento === 'pix')

            <div class="co-panel">
                <div class="co-panel-header flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                             style="background:#e0f2fe;">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z" fill="#0369a1" stroke="#0369a1"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                            </svg>
                        </div>
                        <div>
                            <span class="co-badge co-badge-pix">⚡ Pagamento Instantâneo via Pix</span>
                            <div class="text-base font-bold text-slate-900 mt-1">Aguardando Confirmação do Pagamento</div>
                        </div>
                    </div>
                    <div id="co-countdown-pix" class="co-countdown">
                        <span class="co-countdown-dot"></span>
                        <span id="co-countdown-text">Calculando validade...</span>
                    </div>
                </div>

                <div class="co-panel-body">
                    <div class="co-pix-container flex flex-col md:flex-row items-center md:items-start" style="gap: 36px;">

                        {{-- QR CODE --}}
                        <div class="co-pix-qr-col flex-shrink-0 flex flex-col items-center gap-3" style="margin-right: 36px;">
                            <div class="co-qr-wrapper">
                                @if($cobrancaAtiva && ($cobrancaAtiva->qr_code_base64 || $cobrancaAtiva->pix_qr_code_base64))
                                    <img src="data:image/png;base64,{{ $cobrancaAtiva->qr_code_base64 ?? $cobrancaAtiva->pix_qr_code_base64 }}"
                                         alt="QR Code Pix — {{ $contrato->numero_contrato }}">
                                @else
                                    <div class="co-qr-placeholder">
                                        <svg width="40" height="40" fill="none" stroke="#94a3b8" viewBox="0 0 24 24">
                                            <path d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
                                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                                        </svg>
                                        <span>QR Code sendo<br>gerado no Asaas...</span>
                                        <span style="font-size:10px;color:#94a3b8;">Recarregue a página</span>
                                    </div>
                                @endif
                            </div>
                            <span class="text-[11px] text-slate-500 text-center max-w-[200px]">
                                Aponte a câmera do app do banco para pagar
                            </span>
                        </div>

                        {{-- AÇÕES --}}
                        <div class="co-pix-details-col flex-1 w-full space-y-5">

                            {{-- Pix Copia e Cola --}}
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-2">
                                    Pix Copia e Cola
                                </label>
                                <div class="co-copy-group" id="co-pix-group">
                                    <input type="text" id="co-pix-input" readonly
                                           value="{{ $cobrancaAtiva?->pix_copia_cola ?? 'Chave Pix em processamento...' }}"
                                           title="Código Pix Copia e Cola">
                                    <button type="button" class="co-copy-btn" id="co-pix-btn"
                                            onclick="copiaCola('co-pix-input','co-pix-btn')">
                                        Copiar
                                    </button>
                                </div>
                            </div>

                            {{-- Valor + Instruções --}}
                            <div class="bg-slate-50 border border-slate-200 rounded-md p-4">
                                <div class="co-info-row">
                                    <span>Valor a pagar</span>
                                    <strong class="text-base text-slate-900">R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}</strong>
                                </div>
                                <div class="co-info-row">
                                    <span>Validade</span>
                                    <strong>24 horas após emissão</strong>
                                </div>
                                <div class="co-info-row">
                                    <span>Confirmação</span>
                                    <strong class="text-emerald-700">Automática via Webhook</strong>
                                </div>
                            </div>

                            {{-- Instruções --}}
                            <ul class="co-step-list">
                                <li>
                                    <span class="co-step-num">1</span>
                                    <span>Abra o aplicativo do seu banco e escolha <strong>Pix</strong></span>
                                </li>
                                <li>
                                    <span class="co-step-num">2</span>
                                    <span>Escaneie o <strong>QR Code</strong> ao lado ou cole o <strong>código Pix</strong></span>
                                </li>
                                <li>
                                    <span class="co-step-num">3</span>
                                    <span>Confirme o valor de <strong>R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}</strong> e finalize</span>
                                </li>
                                <li>
                                    <span class="co-step-num">4</span>
                                    <span>Esta tela <strong>atualiza automaticamente</strong> após a confirmação bancária</span>
                                </li>
                            </ul>

                            {{-- Botões --}}
                            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                @php
                                    $telefoneResp = preg_replace('/\D/', '', $contrato->responsavel?->telefone_celular ?? '');
                                    $msgPix = urlencode(
                                        "Olá " . ($contrato->responsavel?->nome ?? '') . "!\n" .
                                        "Segue o Pix para o pagamento da matrícula — Contrato " . $contrato->numero_contrato . "\n" .
                                        "Valor: R$ " . number_format($contrato->valor_total_liquido, 2, ',', '.') . "\n\n" .
                                        ($cobrancaAtiva?->pix_copia_cola ?? '')
                                    );
                                @endphp
                                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                    @if($telefoneResp)
                                        <a href="https://wa.me/55{{ $telefoneResp }}?text={{ $msgPix }}" target="_blank"
                                           class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs font-semibold"
                                           style="text-decoration:none;">
                                            <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                            </svg>
                                            Enviar Pix via WhatsApp
                                        </a>
                                    @endif
                                    <a href="{{ route('financeiro.contratos.show', $contrato->id) }}"
                                       class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-outline text-xs font-semibold"
                                       style="text-decoration:none;">
                                        Ver Contrato
                                    </a>
                                </div>

                            {{-- Status em tempo real --}}
                            <div class="flex items-center gap-2 pt-1 text-xs text-slate-500" id="co-realtime-status">
                                <span class="co-pulse-ring">
                                    <span></span><span></span>
                                </span>
                                <span id="co-realtime-text">Monitorando confirmação bancária em tempo real...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        {{-- ═══════════════════════════════════════════════════════════════════════
             CENÁRIO 3 — CARTÃO DE CRÉDITO (LINK DE PAGAMENTO ASAAS ATÉ 12X)
             ═══════════════════════════════════════════════════════════════════════ --}}
        @elseif($contrato->forma_pagamento === 'cartao_credito')

            <div class="co-panel">
                <div class="co-panel-header flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                         style="background:#fef3c7;">
                        <svg width="20" height="20" fill="none" stroke="#b45309" viewBox="0 0 24 24">
                            <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                    </div>
                    <div>
                        <span class="co-badge co-badge-cartao">🌐 Cartão de Crédito Online — Asaas</span>
                        <div class="text-base font-bold text-slate-900 mt-1">Link de Pagamento em até 12x</div>
                    </div>
                </div>

                <div class="co-panel-body space-y-5">
                    <p class="text-sm text-slate-500 m-0">
                        O link foi gerado com <strong class="text-slate-700">repasse automático de juros</strong> ao responsável.
                        Validade: <strong class="text-slate-700">5 dias úteis</strong>.
                        @if($cobrancaAtiva?->expira_em)
                            Expira em: <strong class="text-slate-700">{{ \Carbon\Carbon::parse($cobrancaAtiva->expira_em)->format('d/m/Y H:i') }}</strong>.
                        @endif
                    </p>

                    @if($contrato->payment_link_url)
                        {{-- URL do link --}}
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-2">
                                Link de Pagamento Asaas
                            </label>
                            <div class="co-copy-group" id="co-link-group">
                                <input type="text" id="co-link-input" readonly
                                       value="{{ $contrato->payment_link_url }}"
                                       title="Link de Pagamento Asaas">
                                <button type="button" class="co-copy-btn" id="co-link-btn"
                                        onclick="copiaCola('co-link-input','co-link-btn')">
                                    Copiar
                                </button>
                            </div>
                        </div>

                        {{-- Detalhes --}}
                        <div class="bg-slate-50 border border-slate-200 rounded-md p-4">
                            <div class="co-info-row">
                                <span>Valor total</span>
                                <strong>R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}</strong>
                            </div>
                            <div class="co-info-row">
                                <span>Parcelamento</span>
                                <strong>Em até 12x no cartão de crédito</strong>
                            </div>
                            <div class="co-info-row">
                                <span>Juros</span>
                                <strong class="text-amber-700">Repassados ao responsável</strong>
                            </div>
                            <div class="co-info-row">
                                <span>Confirmação</span>
                                <strong class="text-emerald-700">Automática via Webhook Asaas</strong>
                            </div>
                        </div>

                        {{-- Botões --}}
                        <div class="flex flex-wrap items-center gap-2.5 pt-1">
                            <a href="{{ $contrato->payment_link_url }}" target="_blank"
                               class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-blue text-xs font-semibold"
                               style="text-decoration:none;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                </svg>
                                Abrir Checkout Asaas
                            </a>

                            @php
                                $telLink = preg_replace('/\D/', '', $contrato->responsavel?->telefone_celular ?? '');
                                $msgLink = urlencode(
                                    "Olá " . ($contrato->responsavel?->nome ?? '') . "!\n" .
                                    "Segue o link para pagamento da matrícula em até 12x no cartão de crédito.\n" .
                                    "Contrato: " . $contrato->numero_contrato . "\n" .
                                    "Valor: R$ " . number_format($contrato->valor_total_liquido, 2, ',', '.') . "\n\n" .
                                    $contrato->payment_link_url
                                );
                            @endphp
                            @if($telLink)
                                <a href="https://wa.me/55{{ $telLink }}?text={{ $msgLink }}" target="_blank"
                                   class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs font-semibold"
                                   style="text-decoration:none;">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                                    </svg>
                                    Enviar Link via WhatsApp
                                </a>
                            @endif
                        </div>

                        <p class="text-[11px] text-slate-400 m-0 text-center pt-1">
                            💡 O responsável pode pagar de qualquer dispositivo clicando no link acima.
                        </p>

                    @else
                        <div class="flex items-center gap-3 p-4 bg-amber-50 border border-amber-200 rounded-md text-sm text-amber-800">
                            <svg width="18" height="18" fill="none" stroke="#b45309" viewBox="0 0 24 24" class="flex-shrink-0">
                                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                            O link de pagamento ainda está sendo processado pelo Asaas.
                            <button onclick="window.location.reload()" class="ml-auto text-xs font-bold underline bg-transparent border-none cursor-pointer text-amber-900">
                                Atualizar página
                            </button>
                        </div>
                    @endif
                </div>
            </div>

        {{-- ═══════════════════════════════════════════════════════════════════════
             CENÁRIO 4 — BOLETO BANCÁRIO À VISTA
             ═══════════════════════════════════════════════════════════════════════ --}}
        @elseif($contrato->forma_pagamento === 'boleto')

            <div class="co-panel">
                <div class="co-panel-header flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                         style="background:#eff6ff;">
                        <svg width="20" height="20" fill="none" stroke="#1d4ed8" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                    </div>
                    <div>
                        <span class="co-badge co-badge-boleto">📄 Boleto Bancário</span>
                        <div class="text-base font-bold text-slate-900 mt-1">Boleto Emitido no Gateway Asaas</div>
                    </div>
                </div>

                <div class="co-panel-body space-y-5">
                    <p class="text-sm text-slate-500 m-0">
                        O boleto foi registrado no Asaas. O contrato será
                        <strong class="text-slate-700">automaticamente quitado</strong>
                        via Webhook assim que o pagamento for confirmado pelo banco.
                    </p>

                    {{-- Linha Digitável --}}
                    @if($primeiroTitulo?->cobrancaGateway?->linha_digitavel)
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-2">
                                Linha Digitável
                            </label>
                            <div class="co-copy-group">
                                <input type="text" id="co-boleto-input" readonly
                                       value="{{ $primeiroTitulo->cobrancaGateway->linha_digitavel }}"
                                       title="Linha digitável do boleto">
                                <button type="button" class="co-copy-btn" id="co-boleto-btn"
                                        onclick="copiaCola('co-boleto-input','co-boleto-btn')">
                                    Copiar
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Detalhes --}}
                    <div class="bg-slate-50 border border-slate-200 rounded-md p-4">
                        <div class="co-info-row">
                            <span>Valor</span>
                            <strong>R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}</strong>
                        </div>
                        @if($primeiroTitulo?->data_vencimento)
                            <div class="co-info-row">
                                <span>Vencimento</span>
                                <strong>{{ \Carbon\Carbon::parse($primeiroTitulo->data_vencimento)->format('d/m/Y') }}</strong>
                            </div>
                        @endif
                        <div class="co-info-row">
                            <span>Status</span>
                            <strong class="text-amber-700">Aguardando Pagamento</strong>
                        </div>
                        <div class="co-info-row">
                            <span>Baixa</span>
                            <strong class="text-emerald-700">Automática ao compensar</strong>
                        </div>
                    </div>

                    {{-- Botões --}}
                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                        @if($primeiroTitulo?->cobrancaGateway?->link_boleto_pdf)
                            <a href="{{ $primeiroTitulo->cobrancaGateway->link_boleto_pdf }}" target="_blank"
                               class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs font-semibold"
                               style="text-decoration:none;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                </svg>
                                Baixar Boleto (PDF)
                            </a>
                        @endif
                        @if($primeiroTitulo?->cobrancaGateway?->link_pagamento)
                            <a href="{{ $primeiroTitulo->cobrancaGateway->link_pagamento }}" target="_blank"
                               class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-blue text-xs font-semibold"
                               style="text-decoration:none;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                </svg>
                                Abrir Boleto Online
                            </a>
                        @endif
                        <a href="{{ route('financeiro.contratos.show', $contrato->id) }}"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-outline text-xs font-semibold"
                           style="text-decoration:none;">
                            Ver Contrato
                        </a>
                    </div>
                </div>
            </div>

        {{-- ═══════════════════════════════════════════════════════════════════════
             CENÁRIO 5 — CARNÊ ESCOLAR (PARCELAMENTO ASAAS)
             ═══════════════════════════════════════════════════════════════════════ --}}
        @elseif($contrato->forma_pagamento === 'carne')

            <div class="co-panel">
                <div class="co-panel-header flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                         style="background:#f0fdf4;">
                        <svg width="20" height="20" fill="none" stroke="#166534" viewBox="0 0 24 24">
                            <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                    </div>
                    <div>
                        <span class="co-badge co-badge-carne">📚 Carnê Escolar Parcelado</span>
                        <div class="text-base font-bold text-slate-900 mt-1">Parcelas Registradas no Asaas</div>
                    </div>
                </div>

                <div class="co-panel-body space-y-5">
                    <p class="text-sm text-slate-500 m-0">
                        O carnê foi gerado e as <strong class="text-slate-700">{{ $contrato->titulos->count() }} parcelas</strong>
                        foram registradas no Asaas. Cada boleto de parcela será automaticamente liquidado ao ser pago.
                    </p>

                    {{-- Parcelas resumo --}}
                    @php
                        $titulos = $contrato->titulos->sortBy('numero_parcela');
                        $totalParcelas = $titulos->count();
                        $valorParcela = $totalParcelas > 0 ? round($contrato->valor_total_liquido / $totalParcelas, 2) : 0;
                    @endphp
                    <div class="bg-slate-50 border border-slate-200 rounded-md p-4">
                        <div class="co-info-row">
                            <span>Total parcelado</span>
                            <strong>R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}</strong>
                        </div>
                        <div class="co-info-row">
                            <span>Número de parcelas</span>
                            <strong>{{ $totalParcelas }}x</strong>
                        </div>
                        <div class="co-info-row">
                            <span>Valor por parcela (aprox.)</span>
                            <strong>R$ {{ number_format($valorParcela, 2, ',', '.') }}</strong>
                        </div>
                        <div class="co-info-row">
                            <span>Primeiro vencimento</span>
                            <strong>{{ $titulos->first() ? \Carbon\Carbon::parse($titulos->first()->data_vencimento)->format('d/m/Y') : '—' }}</strong>
                        </div>
                        <div class="co-info-row">
                            <span>Último vencimento</span>
                            <strong>{{ $titulos->last() ? \Carbon\Carbon::parse($titulos->last()->data_vencimento)->format('d/m/Y') : '—' }}</strong>
                        </div>
                    </div>

                    {{-- Botões --}}
                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                        <a href="{{ route('financeiro.contratos.imprimir-carne', $contrato->id) }}" target="_blank"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs font-semibold"
                           style="text-decoration:none;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                            Imprimir Carnê Oficial (PDF)
                        </a>
                        <a href="{{ route('financeiro.contratos.show', $contrato->id) }}"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2 btn-ieducar-action btn-ieducar-outline text-xs font-semibold"
                           style="text-decoration:none;">
                            Ver Todas as Parcelas
                        </a>
                    </div>

                    <p class="text-[11px] text-slate-400 m-0 text-center">
                        💡 O status de cada parcela será atualizado automaticamente via Webhook Asaas.
                    </p>
                </div>
            </div>

        {{-- ═══════════════════════════════════════════════════════════════════════
             CENÁRIO 6 — FALLBACK (forma não reconhecida)
             ═══════════════════════════════════════════════════════════════════════ --}}
        @else
            <div class="co-panel">
                <div class="co-panel-body text-center space-y-4 py-8">
                    <div class="text-4xl">📋</div>
                    <h3 class="text-lg font-bold text-slate-900 m-0">Contrato Registrado</h3>
                    <p class="text-sm text-slate-500 max-w-md mx-auto">
                        O contrato foi criado com sucesso. Acesse os detalhes para gerenciar parcelas e emitir cobranças.
                    </p>
                    <a href="{{ route('financeiro.contratos.show', $contrato->id) }}"
                       class="inline-flex items-center gap-2 px-6 py-2.5 btn-ieducar-action btn-ieducar-blue text-sm"
                       style="text-decoration:none;">
                        Ver Contrato Completo
                    </a>
                </div>
            </div>
        @endif

        {{-- ── FOOTER LINKS ── --}}
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500 pt-2 pb-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('financeiro.contratos.index') }}" class="hover:text-[#47728f] transition" style="text-decoration:none;">
                    ← Todos os Contratos
                </a>
                <a href="{{ route('financeiro.titulos.index') }}" class="hover:text-[#47728f] transition" style="text-decoration:none;">
                    Ver Mensalidades
                </a>
            </div>
            <span>Contrato <strong class="text-slate-700">{{ $contrato->numero_contrato }}</strong> · Criado em {{ $contrato->created_at?->format('d/m/Y H:i') }}</span>
        </div>

    </main>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── COPY BUTTON ──────────────────────────────────────────────────────────
    window.copiaCola = function (inputId, btnId) {
        var input = document.getElementById(inputId);
        var btn   = document.getElementById(btnId);
        if (!input || !btn) return;

        var val = input.value;
        if (!val || val === 'Chave Pix em processamento...' || val === 'Código Pix em processamento...') {
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(val).then(function () {
                markCopied(btn);
            }).catch(function () {
                legacyCopy(input, btn);
            });
        } else {
            legacyCopy(input, btn);
        }
    };

    function markCopied(btn) {
        var orig = btn.textContent;
        btn.textContent = '✓ Copiado!';
        btn.classList.add('co-copied');
        setTimeout(function () {
            btn.textContent = orig;
            btn.classList.remove('co-copied');
        }, 2500);
    }

    function legacyCopy(input, btn) {
        input.select();
        try {
            document.execCommand('copy');
            markCopied(btn);
        } catch (e) {}
        window.getSelection && window.getSelection().removeAllRanges();
    }

    // ── PIX COUNTDOWN E MONITORAMENTO REALTIME ──────────────────────────────
    @php
        $temPixAtivo = ($contrato->forma_pagamento === 'pix') 
            || (($ehRateio ?? false) && ($rateioResponsaveis ?? collect())->contains(fn($r) => $r->forma_pagamento === 'pix'));

        $titulosBase = $contrato->titulos;
        $initialHash = md5($titulosBase->map(function ($t) {
            $cg = $t->cobrancasGateway->sortByDesc('id')->first();
            return "{$t->id}:{$t->status}:{$t->valor_pago}:" . ($cg?->status_externo ?? 'none');
        })->join('|'));
        $initialPagosCount = $titulosBase->filter(fn ($t) => in_array($t->status, ['pago', 'liquidado']))->count();
        $tituloPixParaSSE = $titulosBase->first(function ($t) {
            return $t->cobrancasGateway->contains(fn($c) => $c->tipo_cobranca === 'pix')
                || str_contains(strtolower($t->observacoes ?? ''), 'pix');
        }) ?? $primeiroTitulo;
    @endphp
    @if($temPixAtivo && $contrato->status !== 'quitado')
        (function () {
            var expiresAt = null;
            @if($cobrancaAtiva?->expira_em)
                expiresAt = new Date('{{ \Carbon\Carbon::parse($cobrancaAtiva->expira_em)->toIso8601String() }}');
            @elseif($contrato->created_at)
                expiresAt = new Date('{{ $contrato->created_at->addHours(24)->toIso8601String() }}');
            @endif

            var el = document.getElementById('co-countdown-text');

            function tick() {
                if (!expiresAt || !el) return;
                var now  = new Date();
                var diff = expiresAt - now;

                if (diff <= 0) {
                    el.textContent = 'QR Code expirado — recarregue a página';
                    var dot = document.querySelector('.co-countdown-dot');
                    if (dot) { dot.style.background = '#dc2626'; dot.style.animation = 'none'; }
                    return;
                }

                var h = Math.floor(diff / 3600000);
                var m = Math.floor((diff % 3600000) / 60000);
                var s = Math.floor((diff % 60000) / 1000);

                el.textContent = 'Válido por ' +
                    (h > 0 ? h + 'h ' : '') +
                    String(m).padStart(2, '0') + 'min ' +
                    String(s).padStart(2, '0') + 's';
            }

            tick();
            setInterval(tick, 1000);
        })();

        // ── MONITORAMENTO REALTIME (SSE + POLLING FALLBACK) ───────────────────
        (function () {
            var STATUS_URL = '{{ route("financeiro.contratos.status-pagamento", $contrato->id) }}';
            var SSE_URL = '{{ route("financeiro.api.stream", ["contrato_id" => $contrato->id, "titulo_id" => $tituloPixParaSSE?->id ?? 0]) }}';
            var initialHash = '{{ $initialHash }}';
            var initialPagos = {{ (int) $initialPagosCount }};
            var interval = null;
            var sseSource = null;
            var finalizado = false;

            function confirmarPagamento() {
                if (finalizado) return;
                finalizado = true;
                if (interval) clearInterval(interval);
                if (sseSource) sseSource.close();

                var statusEl = document.getElementById('co-realtime-text');
                if (statusEl) {
                    statusEl.textContent = '✅ Pagamento confirmado via Pix! Atualizando...';
                    statusEl.style.color = '#059669';
                    statusEl.style.fontWeight = '700';
                }
                var dotEl = document.getElementById('co-realtime-dot');
                if (dotEl) {
                    dotEl.style.background = '#059669';
                    dotEl.style.animation = 'none';
                }

                // Atualiza também todos os indicadores de tempo real no rateio
                var todosStatus = document.querySelectorAll('.co-realtime-text');
                todosStatus.forEach(function (el) {
                    el.textContent = '✅ Pagamento identificado! Atualizando página...';
                    el.style.color = '#059669';
                    el.style.fontWeight = '700';
                });

                setTimeout(function () {
                    window.location.reload();
                }, 1000);
            }

            // 1. Escuta via Server-Sent Events (SSE) instantâneo
            if (window.EventSource) {
                try {
                    sseSource = new EventSource(SSE_URL);
                    sseSource.addEventListener('PAGAMENTO_CONFIRMADO', function () {
                        confirmarPagamento();
                    });
                    sseSource.onerror = function () {
                        // Se falhar o SSE, o polling abaixo continua garantindo a atualização
                    };
                } catch (e) {}
            }

            // 2. Polling leve a cada 3.5 segundos como redundância garantida
            var attempts = 0;
            var MAX_ATTEMPTS = 1000; // ~60 min

            interval = setInterval(function () {
                if (finalizado) {
                    clearInterval(interval);
                    return;
                }
                attempts++;
                if (attempts > MAX_ATTEMPTS) {
                    clearInterval(interval);
                    if (sseSource) sseSource.close();
                    var el = document.getElementById('co-realtime-text');
                    if (el) el.textContent = 'Monitoramento em pausa. Recarregue a página para verificar.';
                    return;
                }

                fetch(STATUS_URL, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data) {
                            var mudou = (data.pago === true) ||
                                        (data.status === 'quitado') ||
                                        (data.pix_pago === true && initialPagos === 0) ||
                                        (data.titulos_hash && data.titulos_hash !== initialHash) ||
                                        (typeof data.titulos_pagos !== 'undefined' && data.titulos_pagos > initialPagos) ||
                                        (data.titulo_status === 'pago') ||
                                        (data.titulo_status === 'liquidado');

                            if (mudou) {
                                confirmarPagamento();
                            }
                        }
                    })
                    .catch(function () {});
            }, 3500);
        })();
    @endif

})();
</script>
@endpush
