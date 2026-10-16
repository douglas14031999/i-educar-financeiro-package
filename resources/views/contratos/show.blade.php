@extends('financeiro::layouts.app')

@section('title', 'Contrato ' . $contrato->numero_contrato)
@section('breadcrumb', 'Contrato ' . $contrato->numero_contrato)
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
    <style>
        .show-info-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #64748b;
        }
        .show-info-value {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            margin-top: 3px;
        }
        .show-info-sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .show-payment-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            border-radius: 4px;
            border: 1px solid;
        }
        .show-payment-banner.pix     { background:#e0f2fe; border-color:#bae6fd; color:#0369a1; }
        .show-payment-banner.cartao  { background:#fef3c7; border-color:#fde68a; color:#92400e; }
        .show-payment-banner.boleto  { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; }
        .show-payment-banner.carne   { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
        .show-payment-banner.quitado { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
        .show-payment-banner.dinheiro{ background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
        .show-payment-banner.aguardando { background:#fffbeb; border-color:#fde68a; color:#92400e; }
    </style>
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-5" data-purpose="contrato-show-container">

        @php
            /* ── Constrói ações do cabeçalho ── */
            $contratoActions = '<div class="flex flex-wrap items-center gap-2">';

            $primeiroTitulo = $contrato->titulos->first();
            $cobrancaAtiva = $primeiroTitulo?->cobrancaGateway;
            $forma = $contrato->forma_pagamento ?? '';

            // Ações específicas conforme a forma de pagamento
            if ($forma === 'carne') {
                $contratoActions .= '
                    <a href="' . route('financeiro.contratos.imprimir-carne', $contrato->id) . '"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-green"
                       style="text-decoration:none;" title="Imprimir carnê oficial com todas as lâminas escolares">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                        Imprimir Carnê
                    </a>';

                if (in_array($contrato->status, ['aguardando_pagamento', 'ativo'])) {
                    $contratoActions .= '
                        <a href="' . route('financeiro.contratos.checkout', $contrato->id) . '"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue"
                           style="text-decoration:none;" title="Ver resumo do carnê e cobranças">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                            Ver Checkout / Pagamento
                        </a>';
                }
            } elseif ($forma === 'pix') {
                $contratoActions .= '
                    <a href="' . route('financeiro.contratos.checkout', $contrato->id) . '"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-green"
                       style="text-decoration:none;" title="Ver QR Code Pix e Chave Copia e Cola">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                        Ver Checkout / Pagamento
                    </a>';
            } elseif ($forma === 'boleto') {
                if ($cobrancaAtiva?->link_boleto_pdf) {
                    $contratoActions .= '
                        <a href="' . $cobrancaAtiva->link_boleto_pdf . '"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-green"
                           style="text-decoration:none;" title="Baixar Boleto Bancário (PDF)">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                            Baixar Boleto (PDF)
                        </a>';
                }
                $contratoActions .= '
                    <a href="' . route('financeiro.contratos.checkout', $contrato->id) . '"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue"
                       style="text-decoration:none;" title="Ver Boleto Bancário e Linha Digitável">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                        Ver Checkout / Boleto
                    </a>';
            } elseif ($forma === 'cartao_credito') {
                $contratoActions .= '
                    <a href="' . route('financeiro.contratos.checkout', $contrato->id) . '"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-green"
                       style="text-decoration:none;" title="Acessar link para pagamento com Cartão de Crédito">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                        Ver Checkout / Cartão
                    </a>';
            } elseif (in_array($forma, ['cartao_presencial', 'dinheiro'])) {
                $contratoActions .= '
                    <a href="' . route('financeiro.contratos.checkout', $contrato->id) . '"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-green"
                       style="text-decoration:none;" title="Ver detalhes do pagamento e comprovante">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                        Comprovante / Detalhes
                    </a>';
            } elseif (in_array($contrato->status, ['aguardando_pagamento', 'ativo'])) {
                $contratoActions .= '
                    <a href="' . route('financeiro.contratos.checkout', $contrato->id) . '"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-green"
                       style="text-decoration:none;" title="Ver checkout e opções de pagamento">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                        Ver Checkout / Pagamento
                    </a>';
            }

            // Botão Ver Títulos
            $contratoActions .= '
                <a href="' . route('financeiro.titulos.index', ['contrato_id' => $contrato->id]) . '"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue"
                   style="text-decoration:none;" title="Ver todas as parcelas">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                    Ver Títulos
                </a>';

            if (in_array($contrato->status, ['ativo', 'inadimplente'])) {
                $contratoActions .= '
                    <form method="POST" action="' . route('financeiro.contratos.gerar-titulos', $contrato->id) . '"
                          style="display:inline;"
                          onsubmit="return confirm(\'Deseja verificar e gerar eventuais títulos faltantes?\');">
                        ' . csrf_field() . '
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                            Gerar Títulos Faltantes
                        </button>
                    </form>';

                if (!empty($temTitulosVencidos) && $contrato->responsavel_financeiro_id) {
                    $contratoActions .= '
                        <a href="' . route('financeiro.renegociacao.index', ['responsavel_id' => $contrato->responsavel_financeiro_id]) . '"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-danger"
                           style="text-decoration:none;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                            Renegociar Débitos
                        </a>';
                }

                $contratoActions .= '
                    <button type="button"
                            onclick="document.getElementById(\'modal-cancelar\').style.display=\'flex\';"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-danger">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                        Rescindir / Cancelar
                    </button>';
            }

            $contratoActions .= '
                <a href="' . route('financeiro.contratos.index') . '"
                   class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline"
                   style="text-decoration:none;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                    Voltar
                </a>
            </div>';

            /* ── Labels de forma de pagamento ── */
            $formaLabel = match($contrato->forma_pagamento ?? '') {
                'dinheiro'        => 'Dinheiro (Caixa)',
                'cartao_presencial' => 'Cartão Presencial (POS)',
                'cartao_credito'  => 'Cartão de Crédito (Link Asaas)',
                'pix'             => 'Pix Instantâneo',
                'boleto'          => 'Boleto Bancário',
                'carne'           => 'Carnê Escolar (Parcelado)',
                default           => ($contrato->forma_pagamento ? ucfirst($contrato->forma_pagamento) : 'Não definida'),
            };

            $statusLabel = match($contrato->status) {
                'quitado'             => ['label'=>'Quitado',             'color'=>'#059669','bg'=>'#f0fdf4','border'=>'#bbf7d0'],
                'aguardando_pagamento'=> ['label'=>'Aguardando Pagamento','color'=>'#b45309','bg'=>'#fffbeb','border'=>'#fde68a'],
                'ativo'               => ['label'=>'Ativo',              'color'=>'#0369a1','bg'=>'#eff6ff','border'=>'#bfdbfe'],
                'inadimplente'        => ['label'=>'Inadimplente',        'color'=>'#be123c','bg'=>'#fff1f2','border'=>'#fecdd3'],
                'cancelado'           => ['label'=>'Cancelado',           'color'=>'#6b7280','bg'=>'#f9fafb','border'=>'#e5e7eb'],
                default               => ['label'=>ucfirst($contrato->status),'color'=>'#334155','bg'=>'#f8fafc','border'=>'#e2e8f0'],
            };

            /* ── Cobrança ativa do primeiro título (para dados de Pix/Boleto/Link) ── */
            $cobrancaAtiva = $contrato->titulos->first()?->cobrancaGateway ?? null;
        @endphp

        {{-- ── CABEÇALHO ── --}}
        @include('financeiro::partials.page-header', [
            'title'      => 'Contrato ' . $contrato->numero_contrato,
            'subtitle'   => 'Matrícula #' . $contrato->matricula_id . ' · ' . \iEducar\Financeiro\Support\AlunoLookup::nomeComCpf($contrato->aluno_id) . ' · Ano Letivo ' . $contrato->ano_letivo,
            'breadcrumbs'=> [
                ['label' => 'Contratos', 'url' => route('financeiro.contratos.index')],
                ['label' => 'Contrato #' . $contrato->numero_contrato],
            ],
            'actions'    => $contratoActions,
        ])

        {{-- ── BANNER DE STATUS (Aguardando pagamento) ── --}}
        @if($contrato->status === 'aguardando_pagamento')
            <div class="show-payment-banner aguardando">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
                <div>
                    <span style="font-size:12px;font-weight:700;">Aguardando Confirmação de Pagamento via {{ $formaLabel }}</span>
                    <div style="font-size:11px;margin-top:2px;">
                        O contrato será automaticamente quitado pelo Webhook Asaas assim que o pagamento for confirmado.
                    </div>
                </div>
                <a href="{{ route('financeiro.contratos.checkout', $contrato->id) }}"
                   class="ml-auto inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue"
                   style="text-decoration:none;white-space:nowrap;flex-shrink:0;">
                    Ver Checkout
                </a>
            </div>
        @elseif($contrato->status === 'quitado')
            <div class="show-payment-banner quitado">
                <svg width="20" height="20" fill="none" stroke="#059669" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
                <div>
                    <span style="font-size:12px;font-weight:700;color:#059669;">Contrato Quitado</span>
                    <div style="font-size:11px;color:#166534;margin-top:2px;">
                        Todas as obrigações financeiras foram liquidadas.
                        @if($contrato->quitado_em)
                            Quitado em: <strong>{{ \Carbon\Carbon::parse($contrato->quitado_em)->format('d/m/Y H:i') }}</strong>
                        @endif
                        via <strong>{{ $formaLabel }}</strong>.
                    </div>
                </div>
            </div>
        @elseif($contrato->status === 'inadimplente')
            <div class="show-payment-banner" style="background:#fff1f2;border-color:#fecdd3;color:#be123c;">
                <svg width="20" height="20" fill="none" stroke="#be123c" viewBox="0 0 24 24" style="flex-shrink:0;">
                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
                <div>
                    <span style="font-size:12px;font-weight:700;">Contrato Inadimplente — parcelas em atraso</span>
                    <div style="font-size:11px;margin-top:2px;">
                        Acione a Régua de Cobrança ou Renegociação para regularizar.
                    </div>
                </div>
            </div>
        @endif

        {{-- ── FICHA CADASTRAL DO CONTRATO ── --}}
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin:0;color:white;">
                        Ficha Cadastral do Contrato
                    </h2>
                </div>
                <div class="flex items-center gap-3">
                    <span style="display:inline-block;padding:3px 10px;font-size:11px;font-weight:700;letter-spacing:.06em;
                                 text-transform:uppercase;border-radius:20px;
                                 color:{{ $statusLabel['color'] }};background:{{ $statusLabel['bg'] }};border:1px solid {{ $statusLabel['border'] }};">
                        {{ $statusLabel['label'] }}
                    </span>
                    <span class="text-xs text-slate-300 font-mono">ID #{{ $contrato->id }}</span>
                </div>
            </div>

            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Responsável --}}
                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="show-info-label">Responsável Principal</div>
                    <div class="show-info-value">
                        @if($contrato->responsavel)
                            <a href="{{ route('financeiro.responsaveis.show', $contrato->responsavel_financeiro_id) }}"
                               style="color:#47728f;text-decoration:none;">
                                {{ $contrato->responsavel->nome }}
                            </a>
                        @else
                            Não informado
                        @endif
                    </div>
                    @if(optional($contrato->responsavel)->cpf_cnpj_formatado)
                        <div class="show-info-sub font-mono">CPF: {{ $contrato->responsavel->cpf_cnpj_formatado }}</div>
                    @endif
                </div>

                {{-- Plano --}}
                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="show-info-label">Plano Vinculado</div>
                    <div class="show-info-value">{{ $contrato->plano->nome ?? '—' }}</div>
                    <div class="show-info-sub">Vencimento: todo dia {{ sprintf('%02d', $contrato->dia_vencimento) }}</div>
                </div>

                {{-- Valores --}}
                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="show-info-label">Valores (Bruto / Desconto)</div>
                    <div class="show-info-value font-mono">R$ {{ number_format($contrato->valor_total_bruto, 2, ',', '.') }}</div>
                    <div class="show-info-sub font-mono">
                        Desc: R$ {{ number_format($contrato->valor_total_descontos, 2, ',', '.') }}
                    </div>
                </div>

                {{-- Valor Líquido --}}
                <div class="p-3 border border-emerald-200" style="background:#f0fdf4;">
                    <div class="show-info-label" style="color:#166534;">Valor Líquido Contratado</div>
                    <div class="font-bold font-mono mt-1" style="font-size:18px;color:#059669;">
                        R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}
                    </div>
                    <div class="show-info-sub" style="color:#16a34a;">
                        {{ $contrato->titulos->count() }} parcela(s) gerada(s)
                    </div>
                </div>

                {{-- Forma de Pagamento (FASE 7) --}}
                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="show-info-label">Forma de Pagamento</div>
                    <div class="show-info-value">{{ $formaLabel }}</div>
                    @if($contrato->forma_pagamento === 'cartao_presencial')
                        @if($contrato->cartao_bandeira)
                            <div class="show-info-sub">Bandeira: <strong>{{ strtoupper($contrato->cartao_bandeira) }}</strong></div>
                        @endif
                        @if($contrato->cartao_nsu)
                            <div class="show-info-sub font-mono">NSU: {{ $contrato->cartao_nsu }}</div>
                        @endif
                    @endif
                </div>

                {{-- Período do Contrato --}}
                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="show-info-label">Período do Contrato</div>
                    @if($contrato->data_inicio)
                        <div class="show-info-value">
                            {{ \Carbon\Carbon::parse($contrato->data_inicio)->format('d/m/Y') }}
                            @if($contrato->data_termino)
                                → {{ \Carbon\Carbon::parse($contrato->data_termino)->format('d/m/Y') }}
                            @endif
                        </div>
                    @else
                        <div class="show-info-value">Ano letivo {{ $contrato->ano_letivo }}</div>
                    @endif
                    <div class="show-info-sub">Criado em {{ $contrato->created_at?->format('d/m/Y') }}</div>
                </div>

                {{-- Quitação (FASE 7) --}}
                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="show-info-label">Situação da Quitação</div>
                    @if($contrato->status === 'quitado' && $contrato->quitado_em)
                        <div class="show-info-value" style="color:#059669;">
                            Quitado em {{ \Carbon\Carbon::parse($contrato->quitado_em)->format('d/m/Y H:i') }}
                        </div>
                    @elseif($contrato->status === 'aguardando_pagamento')
                        <div class="show-info-value" style="color:#b45309;">Aguardando confirmação</div>
                        <div class="show-info-sub">Via webhook automático</div>
                    @elseif($contrato->status === 'cancelado')
                        <div class="show-info-value" style="color:#6b7280;">Cancelado</div>
                        @if($contrato->cancelado_em)
                            <div class="show-info-sub">Em {{ \Carbon\Carbon::parse($contrato->cancelado_em)->format('d/m/Y') }}</div>
                        @endif
                    @else
                        <div class="show-info-value">Em aberto</div>
                    @endif
                </div>

                {{-- Gateway / Link (FASE 7) --}}
                @if($contrato->payment_link_url || $cobrancaAtiva?->pix_copia_cola || $cobrancaAtiva?->linha_digitavel)
                    <div class="p-3 bg-slate-50 border border-slate-200">
                        <div class="show-info-label">Link / Chave de Pagamento</div>
                        @if($contrato->payment_link_url)
                            <div class="show-info-value" style="font-size:11px;word-break:break-all;">
                                <a href="{{ $contrato->payment_link_url }}" target="_blank"
                                   style="color:#47728f;text-decoration:none;">
                                    Abrir Link Asaas ↗
                                </a>
                            </div>
                            @if($cobrancaAtiva?->expira_em)
                                <div class="show-info-sub">
                                    Expira: {{ \Carbon\Carbon::parse($cobrancaAtiva->expira_em)->format('d/m/Y H:i') }}
                                </div>
                            @endif
                        @elseif($cobrancaAtiva?->pix_copia_cola)
                            <div class="show-info-value" style="font-size:10px;font-family:monospace;word-break:break-all;color:#334155;">
                                {{ substr($cobrancaAtiva->pix_copia_cola, 0, 40) }}...
                            </div>
                            <div class="show-info-sub">Pix Copia e Cola</div>
                        @elseif($cobrancaAtiva?->linha_digitavel)
                            <div class="show-info-value" style="font-size:11px;font-family:monospace;">
                                {{ $cobrancaAtiva->linha_digitavel }}
                            </div>
                            <div class="show-info-sub">Linha digitável do boleto</div>
                        @endif
                    </div>
                @endif

            </div>

            {{-- Motivo de cancelamento --}}
            @if($contrato->motivo_cancelamento)
                <div class="px-5 pb-5">
                    <div class="p-3 bg-red-50 border border-red-200 text-red-800 text-xs">
                        <span class="font-semibold">Motivo do Cancelamento:</span>
                        {{ $contrato->motivo_cancelamento }}
                    </div>
                </div>
            @endif
        </div>

        {{-- ── GRADE DE PARCELAS ── --}}
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="parcelas-section">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 10h16M4 14h16M4 18h16"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin:0;color:white;">
                        Grade de Parcelas e Cobranças ({{ $contrato->titulos->count() }})
                    </h2>
                </div>
                <div class="text-xs text-slate-300">
                    Clique em "Baixar" para registrar liquidação imediata no balcão
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4 text-center">Parcela</th>
                            <th class="py-3 px-4">Vencimento</th>
                            <th class="py-3 px-4">Responsável</th>
                            <th class="py-3 px-4">Descrição</th>
                            <th class="py-3 px-4 text-right">Saldo</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($contrato->titulos as $titulo)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4 text-center font-bold font-mono text-slate-800">
                                    {{ $titulo->numero_parcela == 0 ? 'Matrícula' : sprintf('%02d/%02d', $titulo->numero_parcela, $titulo->total_parcelas) }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-700">
                                    {{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-slate-900">{{ $titulo->responsavel?->nome ?? '—' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ $titulo->observacoes ?? ucfirst($titulo->tipo) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    R$ {{ number_format($titulo->status === 'pago' ? $titulo->valor_nominal : ($titulo->valor_saldo > 0 ? $titulo->valor_saldo : $titulo->valor_nominal), 2, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @php
                                        $badge = match($titulo->status) {
                                            'pago'       => ['Pago',       'bg-emerald-100 text-emerald-800 border-emerald-200', '#22c55e'],
                                            'parcial'    => ['Parcial',    'bg-amber-100 text-amber-800 border-amber-200',       '#f59e0b'],
                                            'vencido'    => ['Vencido',    'bg-red-100 text-red-800 border-red-200',             '#ef4444'],
                                            'cancelado'  => ['Cancelado',  'bg-slate-100 text-slate-700 border-slate-200',       '#94a3b8'],
                                            'renegociado'=> ['Renegociado','bg-orange-100 text-orange-800 border-orange-200',    '#f97316'],
                                            default      => ['Aberto',     'bg-blue-100 text-blue-800 border-blue-200',          '#3b82f6'],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $badge[1] }}">
                                        <span class="w-1.5 h-1.5 rounded-full" style="background:{{ $badge[2] }};"></span>
                                        {{ $badge[0] }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if(in_array($titulo->status, ['aberto', 'parcial', 'vencido']))
                                            <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}"
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 btn-ieducar-action btn-ieducar-green"
                                               title="Baixar no Balcão" style="text-decoration:none;">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                                </svg>
                                                Baixar
                                            </a>
                                        @elseif($titulo->status === 'pago')
                                            <a href="{{ route('financeiro.titulos.recibo-pdf', $titulo->id) }}"
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium border border-slate-200 transition"
                                               title="Imprimir Recibo" target="_blank" style="text-decoration:none;">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                                </svg>
                                                Recibo
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400 font-mono">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-400 text-sm">
                                    Nenhuma parcela gerada para este contrato.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ── MODAL DE CANCELAMENTO ── --}}
        <div id="modal-cancelar" style="display:none;"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white max-w-lg w-full border border-slate-200 shadow-xl overflow-hidden">
                <div class="px-5 py-3.5 text-white flex items-center justify-between"
                     style="background:linear-gradient(to right, #dc2626, #b91c1c);">
                    <div class="flex items-center gap-2">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                        <h3 class="text-sm font-semibold tracking-wide" style="margin:0;color:white;">
                            Confirmar Cancelamento do Contrato
                        </h3>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-cancelar').style.display='none';"
                            class="text-red-200 hover:text-white bg-transparent border-none cursor-pointer">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
                <form method="post" action="{{ route('financeiro.contratos.cancelar', $contrato->id) }}" class="p-6 space-y-4">
                    @csrf
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Ao cancelar este contrato, <strong>apenas as parcelas futuras não vencidas</strong>
                        serão canceladas. Parcelas já liquidadas ou débitos em atraso permanecerão no histórico.
                    </p>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Motivo do Cancelamento <span class="text-red-500">*</span>
                        </label>
                        <textarea name="motivo" required rows="3"
                                  class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-red-500 transition"
                                  placeholder="Ex: Transferência de escola solicitada pela família..."></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button"
                                onclick="document.getElementById('modal-cancelar').style.display='none';"
                                class="px-4 py-2 btn-ieducar-action btn-ieducar-outline">
                            Voltar
                        </button>
                        <button type="submit" class="px-4 py-2 btn-ieducar-action btn-ieducar-danger">
                            Confirmar Cancelamento
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

@if(!in_array($contrato->status, ['quitado', 'cancelado']))
@push('scripts')
<script>
(function() {
    var STATUS_URL = '{{ route("financeiro.contratos.status-pagamento", $contrato->id) }}';
    var SSE_URL = '{{ route("financeiro.api.stream", ["contrato_id" => $contrato->id]) }}';
    var interval = null;
    var sseSource = null;
    var finalizado = false;

    function recarregarPagina() {
        if (finalizado) return;
        finalizado = true;
        if (interval) clearInterval(interval);
        if (sseSource) sseSource.close();
        setTimeout(function() {
            window.location.reload();
        }, 1000);
    }

    if (window.EventSource) {
        try {
            sseSource = new EventSource(SSE_URL);
            sseSource.addEventListener('PAGAMENTO_CONFIRMADO', function() {
                recarregarPagina();
            });
        } catch (e) {}
    }

    interval = setInterval(function() {
        if (finalizado) return;
        fetch(STATUS_URL, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && (data.pago === true || data.status === 'quitado')) {
                    recarregarPagina();
                }
            })
            .catch(function() {});
    }, 4000);
})();
</script>
@endpush
@endif
@endsection
