@extends('financeiro::layouts.app')

@php
    $anoAtual = $anoAtual ?? $anoLetivo ?? date('Y');
@endphp

@section('title', 'Novo Contrato Financeiro Escolar')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
    <style>
        /* ==========================================================================
           RESET E BLINDAGEM CONTRA O CSS GLOBAL DO I-EDUCAR
           ========================================================================== */
        .financeiro-dashboard-container * {
            box-sizing: border-box !important;
        }

        /* Estilização dos passos do Stepper */
        .step-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        .step-item.active {
            border-color: #47728f !important;
            background: #f0f7fa !important;
            box-shadow: 0 1px 3px rgba(71, 114, 143, 0.12) !important;
        }
        .step-item.completed {
            border-color: #a7f3d0 !important;
            background: #f0fdf4 !important;
        }
        .step-circle {
            width: 30px !important;
            height: 30px !important;
            min-width: 30px !important;
            min-height: 30px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            flex-shrink: 0 !important;
            box-sizing: border-box !important;
            transition: all 0.2s ease;
        }
        .step-circle.pending {
            background-color: #f1f5f9 !important;
            color: #64748b !important;
            border: 1px solid #cbd5e1 !important;
        }
        .step-circle.current {
            background-color: #47728f !important;
            color: #ffffff !important;
            border: 1px solid #355972 !important;
        }
        .step-circle.done {
            background-color: #059669 !important;
            color: #ffffff !important;
            border: 1px solid #047857 !important;
        }

        /* Input Groups Blindados (Prepend Addon) */
        .financeiro-input-group {
            display: flex !important;
            align-items: stretch !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 4px !important;
            overflow: hidden !important;
            background-color: #ffffff !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
        }
        .financeiro-input-group:focus-within {
            border-color: #47728f !important;
            box-shadow: 0 0 0 1px #47728f !important;
        }
        .financeiro-input-addon {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 12px !important;
            background-color: #f8fafc !important;
            border-right: 1px solid #e2e8f0 !important;
            color: #47728f !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            flex-shrink: 0 !important;
            user-select: none !important;
        }
        .financeiro-input-field {
            flex: 1 1 0% !important;
            width: 100% !important;
            border: none !important;
            outline: none !important;
            padding: 10px 14px !important;
            font-size: 13px !important;
            color: #1e293b !important;
            background: transparent !important;
            box-shadow: none !important;
            font-family: inherit !important;
            line-height: 1.4 !important;
        }
        .financeiro-input-field:focus {
            outline: none !important;
            box-shadow: none !important;
            border: none !important;
        }

        /* Atalhos do dia de vencimento (Pills elegantes) */
        .btn-dia-preset {
            padding: 3px 12px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            border-radius: 12px !important;
            border: 1px solid #cbd5e1 !important;
            background-color: #f8fafc !important;
            color: #475569 !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            line-height: 1.5 !important;
        }
        .btn-dia-preset:hover {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
            border-color: #94a3b8 !important;
        }
        .btn-dia-preset.active {
            background-color: #47728f !important;
            color: #ffffff !important;
            border-color: #355972 !important;
            box-shadow: 0 1px 2px rgba(71, 114, 143, 0.25) !important;
        }

        /* Botões do Card Lateral */
        .btn-emitir-contrato {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            width: 100% !important;
            padding: 12px 18px !important;
            background-color: #059669 !important;
            border: 1px solid #047857 !important;
            border-radius: 4px !important;
            color: #ffffff !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            letter-spacing: 0.01em !important;
            cursor: pointer !important;
            box-shadow: 0 1px 3px rgba(5, 150, 105, 0.3) !important;
            transition: background-color 0.15s ease, transform 0.05s ease !important;
            text-decoration: none !important;
        }
        .btn-emitir-contrato:hover {
            background-color: #047857 !important;
            color: #ffffff !important;
        }
        .btn-emitir-contrato:active {
            transform: scale(0.99) !important;
        }
        .btn-cancelar-operacao {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            padding: 9px 16px !important;
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 4px !important;
            color: #475569 !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            text-decoration: none !important;
        }
        .btn-cancelar-operacao:hover {
            background-color: #f8fafc !important;
            border-color: #94a3b8 !important;
            color: #0f172a !important;
        }

        /* Radio Cards de Pagamento */
        .payment-radio-card {
            position: relative;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 4px;
        }
        .payment-radio-card:hover {
            border-color: #47728f;
            box-shadow: 0 2px 6px rgba(71, 114, 143, 0.08);
            transform: translateY(-1px);
        }
        .payment-radio-card.selected {
            border-color: #47728f !important;
            background-color: #f0f7fa !important;
            box-shadow: 0 0 0 1px #47728f, 0 3px 8px rgba(71, 114, 143, 0.12) !important;
        }

        .aluno-search-dropdown {
            max-height: 320px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        .aluno-search-dropdown::-webkit-scrollbar {
            width: 6px;
        }
        .aluno-search-dropdown::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 3px;
        }
        .resp-card-selected {
            border-color: #059669 !important;
            background-color: #f0fdf4 !important;
        }
        .sticky-summary-card {
            position: sticky;
            top: 24px;
        }
    </style>
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="contrato-create-container">
        
        {{-- Cabeçalho Padrão i-Educar com Breadcrumb no Topo --}}
        @include('financeiro::partials.page-header', [
            'title' => 'Novo Contrato Financeiro Escolar',
            'subtitle' => 'Localize o aluno matriculado, defina o responsável financeiro e configure as condições de pagamento e parcelamento',
            'breadcrumbs' => [
                ['label' => 'Contratos', 'url' => route('financeiro.contratos.index')],
                ['label' => 'Novo Contrato']
            ],
            'actions' => '
                <a href="' . route('financeiro.contratos.index') . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline text-xs md:text-sm" style="text-decoration: none;">
                    <svg class="w-4 h-4 text-[#47728f]" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar para Contratos
                </a>
            '
        ])

        {{-- Stepper de Progresso Operacional (4 Etapas com layout blindado) --}}
        <div class="bg-white border border-slate-200 shadow-xs rounded-sm p-3.5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                
                {{-- Passo 1 --}}
                <div id="step_indicator_1" class="step-item active">
                    <div id="step_circle_1" class="step-circle current">
                        1
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-[#47728f]">Passo 1</span>
                        <span id="step_label_1" class="block text-xs font-semibold text-slate-900 truncate">Aluno & Matrícula</span>
                    </div>
                </div>

                {{-- Passo 2 --}}
                <div id="step_indicator_2" class="step-item">
                    <div id="step_circle_2" class="step-circle pending">
                        2
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Passo 2</span>
                        <span id="step_label_2" class="block text-xs font-semibold text-slate-600 truncate">Responsável Financeiro</span>
                    </div>
                </div>

                {{-- Passo 3 --}}
                <div id="step_indicator_3" class="step-item">
                    <div id="step_circle_3" class="step-circle pending">
                        3
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Passo 3</span>
                        <span id="step_label_3" class="block text-xs font-semibold text-slate-600 truncate">Forma de Pagamento</span>
                    </div>
                </div>

                {{-- Passo 4 --}}
                <div id="step_indicator_4" class="step-item">
                    <div id="step_circle_4" class="step-circle pending">
                        4
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Passo 4</span>
                        <span id="step_label_4" class="block text-xs font-semibold text-slate-600 truncate">Plano & Parcelas</span>
                    </div>
                </div>

            </div>
        </div>

        {{-- FORMULÁRIO PRINCIPAL: LAYOUT EM 2 COLUNAS (FORMULÁRIO 8 COLS + RESUMO STICKY 4 COLS) --}}
        <form id="formcadastro" method="post" action="{{ route('financeiro.contratos.store') }}" class="m-0 p-0">
            @csrf

            {{-- Campos Ocultos para Submissão do Contrato --}}
            <input type="hidden" name="aluno_id" id="aluno_id" value="{{ old('aluno_id', $preAlunoId ?? '') }}" required>
            <input type="hidden" name="matricula_id" id="matricula_id" value="{{ old('matricula_id', $preMatriculaId ?? '') }}" required>
            <input type="hidden" name="escola_id" id="escola_id" value="{{ old('escola_id') }}">
            <input type="hidden" name="ano_letivo" id="ano_letivo" value="{{ old('ano_letivo', $anoAtual) }}" required>
            <input type="hidden" name="responsavel_financeiro_id" id="responsavel_financeiro_id" value="{{ old('responsavel_financeiro_id', $preResponsavelId ?? '') }}">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                {{-- COLUNA PRINCIPAL (8 COLUNAS): ETAPAS DO FORMULÁRIO --}}
                <div class="lg:col-span-8 space-y-6">

                    {{-- ========================================================================= --}}
                    {{-- ETAPA 1: ALUNO & MATRÍCULA NO I-EDUCAR                                     --}}
                    {{-- ========================================================================= --}}
                    <div class="bg-white border border-slate-200 shadow-xs rounded-sm overflow-hidden">
                        {{-- Topo do Card com Azul Oficial do i-Educar --}}
                        <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-white/20 text-white flex items-center justify-center font-bold text-xs">1</span>
                                <h2 class="text-sm font-bold tracking-wide" style="margin: 0; color: white;">
                                    Aluno & Matrícula Acadêmica
                                </h2>
                            </div>
                            <span class="text-xs text-slate-200 flex items-center gap-1">
                                <span class="text-amber-300 font-bold">*</span> Obrigatório
                            </span>
                        </div>

                        <div class="p-5 md:p-6 space-y-4">
                            {{-- Campo de Busca com Input Group Addon (Zero Sobreposição) --}}
                            <div id="bloco_busca_aluno" class="relative">
                                <label for="termo_busca_aluno" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Localizar Aluno Matriculado (Base Oficial i-Educar) <span class="text-rose-500">*</span>
                                </label>
                                
                                <div class="financeiro-input-group">
                                    <div class="financeiro-input-addon">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                    </div>

                                    <input type="text" 
                                           id="termo_busca_aluno" 
                                           placeholder="Digite ao menos 2 letras do nome ou dígitos do CPF do aluno..." 
                                           autocomplete="off"
                                           class="financeiro-input-field">

                                    {{-- Spinner de Carregamento integrado ao input group --}}
                                    <div id="busca_spinner" class="hidden items-center justify-center px-3.5 text-[#47728f] bg-white">
                                        <svg class="animate-spin" width="18" height="18" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between text-[11px] text-slate-500 mt-2">
                                    <span class="flex items-center gap-1 text-slate-600">
                                        <svg width="14" height="14" fill="none" stroke="#47728f" viewBox="0 0 24 24">
                                            <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        Dica: Busca instantânea conectada aos registros do ano letivo {{ $anoAtual }}.
                                    </span>
                                    <span class="text-slate-400 font-mono text-[10px]">API i-Educar</span>
                                </div>

                                {{-- Dropdown de Resultados da Busca --}}
                                <div id="dropdown_resultados_aluno" class="aluno-search-dropdown absolute z-30 inset-x-0 mt-1 bg-white border border-slate-200 shadow-xl rounded-sm hidden">
                                    <ul id="lista_resultados_aluno" class="divide-y divide-slate-100 text-sm m-0 p-0"></ul>
                                </div>
                            </div>

                            {{-- Alerta Caso o Aluno Não Seja Encontrado --}}
                            <div id="alerta_aluno_nao_encontrado" class="hidden p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">⚠️</span>
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wider text-amber-800 m-0">Nenhum aluno localizado</p>
                                        <p class="text-xs text-amber-700 mt-0.5 m-0">Verifique a grafia do nome ou o CPF. Caso seja um novo cadastro, registre-o no i-Educar.</p>
                                    </div>
                                </div>
                                <a href="/intranet/atendidos_cad.php" target="_blank" rel="noopener noreferrer" class="px-3.5 py-1.5 btn-ieducar-action btn-ieducar-blue text-xs whitespace-nowrap" style="text-decoration: none;">
                                    + Cadastrar Aluno no i-Educar ↗
                                </a>
                            </div>

                            {{-- Card de Aluno Selecionado --}}
                            <div id="card_aluno_selecionado" class="hidden bg-[#f0f7fa] border border-[#47728f]/40 p-4 md:p-5 rounded-sm relative overflow-hidden">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-start gap-4">
                                        <div class="w-14 h-14 bg-[#47728f] text-white font-bold flex items-center justify-center text-lg rounded-sm shadow-xs shrink-0">
                                            <span id="aluno_avatar_iniciais">AL</span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h3 id="aluno_nome_display" class="text-base font-bold text-slate-900 m-0">NOME DO ALUNO</h3>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    ✓ Aluno Identificado
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-600 mt-1.5 flex flex-wrap gap-x-4 gap-y-1">
                                                <span>Código: <strong id="aluno_cod_display" class="text-slate-800">#0000</strong></span>
                                                <span>CPF: <strong id="aluno_cpf_display" class="text-slate-800 font-mono">000.000.000-00</strong></span>
                                                <span>Nascimento: <strong id="aluno_nasc_display" class="text-slate-800">--/--/----</strong></span>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" id="btn_trocar_aluno" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-outline text-xs self-start sm:self-center">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        Alterar Aluno
                                    </button>
                                </div>

                                {{-- Seletor da Matrícula do Aluno --}}
                                <div class="mt-4 pt-4 border-t border-[#47728f]/20">
                                    <label for="seletor_matricula_ativa" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Matrícula Acadêmica e Turma Vinculada <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="seletor_matricula_ativa" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 text-slate-800 text-xs md:text-sm rounded-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition shadow-2xs font-medium">
                                        {{-- Populado dinamicamente via JS --}}
                                    </select>
                                    <p class="text-[11px] text-slate-500 mt-1.5 flex items-center gap-1" id="matricula_detalhes_helper">
                                        <svg width="14" height="14" fill="none" stroke="#47728f" viewBox="0 0 24 24">
                                            <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        Os títulos e cobranças serão vinculados a esta matrícula e escola no ano letivo ativo.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ========================================================================= --}}
                    {{-- ETAPA 2: RESPONSÁVEL FINANCEIRO IDENTIFICADO                               --}}
                    {{-- ========================================================================= --}}
                    <div class="bg-white border border-slate-200 shadow-xs rounded-sm overflow-hidden">
                        <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-white/20 text-white flex items-center justify-center font-bold text-xs">2</span>
                                <h2 class="text-sm font-bold tracking-wide" style="margin: 0; color: white;">
                                    Responsável Financeiro (Titular da Cobrança)
                                </h2>
                            </div>
                            <span class="text-xs text-slate-200">Emissão de Boletos & Carnês</span>
                        </div>

                        <div class="p-5 md:p-6 space-y-4">
                            {{-- Placeholder Inicial (quando nenhum aluno foi selecionado) --}}
                            <div id="placeholder_sem_aluno" class="py-8 px-4 bg-slate-50 border border-dashed border-slate-300 rounded-sm text-center text-slate-500 flex flex-col items-center justify-center gap-2">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                    </svg>
                                </div>
                                <p class="text-xs font-bold text-slate-700 m-0 uppercase tracking-wide">Aguardando identificação do aluno no Passo 1</p>
                                <p class="text-xs text-slate-500 m-0 max-w-md">Ao selecionar o aluno acima, todos os responsáveis vinculados no i-Educar (mãe, pai ou responsável legal) serão listados aqui para seleção em 1 clique.</p>
                            </div>

                            {{-- Bloco com Tabela de Responsáveis --}}
                            <div id="bloco_responsaveis_secao" class="space-y-4 hidden">
                                {{-- Card de Confirmação do Responsável Selecionado --}}
                                <div id="alerta_responsavel_selecionado" class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-sm text-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 hidden">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                                        <div>
                                            <span class="text-emerald-950 font-bold">Titular da Cobrança Definido:</span>
                                            <span id="nome_responsavel_selecionado" class="font-bold text-emerald-800 ml-1">--</span>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        Vínculo Confirmado
                                    </span>
                                </div>

                                {{-- Tabela de Responsáveis --}}
                                <div class="bg-white border border-slate-200 overflow-hidden shadow-2xs rounded-sm">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left text-xs divide-y divide-slate-200 m-0">
                                            <thead class="bg-slate-100/90 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                                                <tr>
                                                    <th class="py-3 px-4">Parentesco</th>
                                                    <th class="py-3 px-4">Nome do Responsável</th>
                                                    <th class="py-3 px-4">CPF</th>
                                                    <th class="py-3 px-4">WhatsApp / Fone</th>
                                                    <th class="py-3 px-4 text-center">Status Cadastral</th>
                                                    <th class="py-3 px-4 text-right">Ação</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tabela_responsaveis_corpo" class="divide-y divide-slate-100 bg-white">
                                                {{-- Populado via JS --}}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ========================================================================= --}}
                    {{-- ETAPA 3: FORMA DE PAGAMENTO E CHECKOUT DA MATRÍCULA                       --}}
                    {{-- ========================================================================= --}}
                    <div class="bg-white border border-slate-200 shadow-xs rounded-sm overflow-hidden">
                        <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-white/20 text-white flex items-center justify-center font-bold text-xs">3</span>
                                <h2 class="text-sm font-bold tracking-wide" style="margin: 0; color: white;">
                                    Condição de Pagamento e Checkout da Matrícula
                                </h2>
                            </div>
                            <span class="text-xs text-emerald-200 font-semibold flex items-center gap-1">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                6 Modalidades Integradas
                            </span>
                        </div>

                        <div class="p-5 md:p-6 space-y-4">
                            <p class="text-xs text-slate-600 m-0">
                                Escolha a forma de quitação para automatizar a baixa no caixa da escola e a emissão dos títulos no gateway Asaas:
                            </p>

                            {{-- Grid das 6 Opções de Pagamento com Cards Modernos --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                                
                                {{-- 1. Dinheiro à Vista --}}
                                <label class="payment-radio-card p-3.5 flex flex-col justify-between" id="card_pagto_dinheiro">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200">
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="block text-xs font-bold text-slate-900">Dinheiro (À Vista)</span>
                                                <input type="radio" name="forma_pagamento" value="dinheiro" class="text-[#47728f] focus:ring-[#47728f]" {{ old('forma_pagamento') == 'dinheiro' ? 'checked' : '' }}>
                                            </div>
                                            <span class="block text-[11px] text-slate-500 mt-1 leading-snug">Baixa imediata no caixa de balcão e recibo autenticado.</span>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Nasce Quitado</span>
                                        <span class="text-[10px] text-slate-400">Balcão</span>
                                    </div>
                                </label>

                                {{-- 2. Cartão Presencial (Maquininha POS) --}}
                                <label class="payment-radio-card p-3.5 flex flex-col justify-between" id="card_pagto_cartao_presencial">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-full bg-cyan-50 text-cyan-700 flex items-center justify-center shrink-0 border border-cyan-200">
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="block text-xs font-bold text-slate-900">Cartão Presencial</span>
                                                <input type="radio" name="forma_pagamento" value="cartao_presencial" class="text-[#47728f] focus:ring-[#47728f]" {{ old('forma_pagamento') == 'cartao_presencial' ? 'checked' : '' }}>
                                            </div>
                                            <span class="block text-[11px] text-slate-500 mt-1 leading-snug">Passado na maquininha física da escola (coleta NSU).</span>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Nasce Quitado</span>
                                        <span class="text-[10px] text-slate-400">POS Escola</span>
                                    </div>
                                </label>

                                {{-- 3. Cartão Online (Link Asaas até 12x) --}}
                                <label class="payment-radio-card p-3.5 flex flex-col justify-between" id="card_pagto_cartao_credito">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-full bg-blue-50 text-[#47728f] flex items-center justify-center shrink-0 border border-blue-200">
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="block text-xs font-bold text-slate-900">Cartão Online (Link)</span>
                                                <input type="radio" name="forma_pagamento" value="cartao_credito" class="text-[#47728f] focus:ring-[#47728f]" {{ old('forma_pagamento') == 'cartao_credito' ? 'checked' : '' }}>
                                            </div>
                                            <span class="block text-[11px] text-slate-500 mt-1 leading-snug">Link de crédito em até 12x com repasse de juros (5 dias úteis).</span>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Aguardando Webhook</span>
                                        <span class="text-[10px] text-slate-400">Até 12x</span>
                                    </div>
                                </label>

                                {{-- 4. PIX Instantâneo (Validade 24h) --}}
                                <label class="payment-radio-card p-3.5 flex flex-col justify-between" id="card_pagto_pix">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-full bg-teal-50 text-teal-700 flex items-center justify-center shrink-0 border border-teal-200">
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="block text-xs font-bold text-slate-900">PIX Instantâneo (24h)</span>
                                                <input type="radio" name="forma_pagamento" value="pix" class="text-[#47728f] focus:ring-[#47728f]" {{ old('forma_pagamento') == 'pix' ? 'checked' : '' }}>
                                            </div>
                                            <span class="block text-[11px] text-slate-500 mt-1 leading-snug">QR Code dinâmico na tela com monitoramento em tempo real.</span>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-teal-700 uppercase tracking-wider">Confirmação em 3s</span>
                                        <span class="text-[10px] text-slate-400">24 Horas</span>
                                    </div>
                                </label>

                                {{-- 5. Boleto Bancário (1x) --}}
                                <label class="payment-radio-card p-3.5 flex flex-col justify-between" id="card_pagto_boleto">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-full bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200">
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="block text-xs font-bold text-slate-900">Boleto à Vista (1x)</span>
                                                <input type="radio" name="forma_pagamento" value="boleto" class="text-[#47728f] focus:ring-[#47728f]" {{ old('forma_pagamento') == 'boleto' ? 'checked' : '' }}>
                                            </div>
                                            <span class="block text-[11px] text-slate-500 mt-1 leading-snug">Boleto único do Asaas com PDF e envio imediato no WhatsApp.</span>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Compensação D+1</span>
                                        <span class="text-[10px] text-slate-400">À Vista</span>
                                    </div>
                                </label>

                                {{-- 6. Carnê Escolar (2x a 12x) --}}
                                <label class="payment-radio-card p-3.5 flex flex-col justify-between" id="card_pagto_carne">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-100 text-[#47728f] flex items-center justify-center shrink-0 border border-slate-300">
                                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="block text-xs font-bold text-slate-900">Carnê Escolar (Asaas)</span>
                                                <input type="radio" name="forma_pagamento" value="carne" class="text-[#47728f] focus:ring-[#47728f]" {{ old('forma_pagamento') == 'carne' ? 'checked' : '' }}>
                                            </div>
                                            <span class="block text-[11px] text-slate-500 mt-1 leading-snug">Todas as mensalidades do ano em carnê PDF do Asaas.</span>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-[#47728f] uppercase tracking-wider">Contrato Ativo</span>
                                        <span class="text-[10px] text-slate-400">Parcelado</span>
                                    </div>
                                </label>

                            </div>

                            {{-- Bloco Condicional: Detalhes do Cartão Presencial (POS) --}}
                            <div id="bloco_cartao_presencial_detalhes" class="p-4 bg-slate-50 border border-slate-200 rounded-sm space-y-3 hidden">
                                <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    <svg width="16" height="16" fill="none" stroke="#47728f" viewBox="0 0 24 24">
                                        <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                    </svg>
                                    <span>Dados do Comprovante da Maquininha (POS)</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Bandeira do Cartão</label>
                                        <select name="cartao_bandeira" class="w-full text-xs p-2.5 bg-white border border-slate-300 rounded-sm focus:outline-none focus:border-[#47728f]">
                                            <option value="Visa">Visa</option>
                                            <option value="Mastercard">Mastercard</option>
                                            <option value="Elo">Elo</option>
                                            <option value="Hipercard">Hipercard</option>
                                            <option value="Amex">American Express</option>
                                            <option value="Outra">Outra</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tipo de Operação</label>
                                        <select name="cartao_tipo" class="w-full text-xs p-2.5 bg-white border border-slate-300 rounded-sm focus:outline-none focus:border-[#47728f]">
                                            <option value="credito_vista">Crédito à Vista</option>
                                            <option value="debito">Débito</option>
                                            <option value="credito_parcelado">Crédito Parcelado</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Código de Autorização / NSU</label>
                                        <input type="text" name="cartao_nsu" placeholder="Ex: 082914" class="w-full text-xs p-2.5 bg-white border border-slate-300 rounded-sm focus:outline-none focus:border-[#47728f]">
                                    </div>
                                </div>
                            </div>

                            {{-- Bloco Condicional: Detalhes do Cartão Online Link --}}
                            <div id="bloco_cartao_credito_detalhes" class="p-4 bg-slate-50 border border-slate-200 rounded-sm space-y-2 hidden">
                                <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    <svg width="16" height="16" fill="none" stroke="#47728f" viewBox="0 0 24 24">
                                        <path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                    </svg>
                                    <span>Configuração do Link de Pagamento Asaas</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Parcelamento Máximo Liberado</label>
                                        <select name="cartao_parcelas" class="w-full text-xs p-2.5 bg-white border border-slate-300 rounded-sm focus:outline-none focus:border-[#47728f]">
                                            <option value="12" selected>Até 12x com repasse de juros ao pagador</option>
                                            <option value="10">Até 10x com repasse de juros</option>
                                            <option value="6">Até 6x com repasse de juros</option>
                                            <option value="1">Apenas 1x (à vista no crédito)</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center text-xs text-slate-500 pt-5">
                                        <span>* Link gerado com validade de 5 dias úteis e repasse automático de taxas.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ========================================================================= --}}
                    {{-- ETAPA 4: PLANO DE COBRANÇA E CONDIÇÕES COMERCIAIS                         --}}
                    {{-- ========================================================================= --}}
                    <div class="bg-white border border-slate-200 shadow-xs rounded-sm overflow-hidden">
                        <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-white/20 text-white flex items-center justify-center font-bold text-xs">4</span>
                                <h2 class="text-sm font-bold tracking-wide" style="margin: 0; color: white;">
                                    Plano de Cobrança e Condições Comerciais
                                </h2>
                            </div>
                            <span class="text-xs text-slate-200">Anuidade & Mensalidades</span>
                        </div>

                        <div class="p-5 md:p-6 space-y-4">
                            <div>
                                <label for="plano_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Plano de Pagamento / Anuidade <span class="text-rose-500">*</span>
                                </label>
                                <select name="plano_id" id="plano_id" required class="w-full px-3.5 py-2.5 bg-white border border-slate-300 text-slate-800 text-xs md:text-sm rounded-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition shadow-2xs font-semibold">
                                    <option value="">Selecione o plano configurado...</option>
                                    @foreach($planos as $plano)
                                        <option value="{{ $plano->id }}" 
                                                data-valor="{{ $plano->valor_total }}" 
                                                data-nome="{{ $plano->nome }}"
                                                {{ old('plano_id') == $plano->id ? 'selected' : '' }}>
                                            {{ $plano->nome }} — R$ {{ number_format($plano->valor_total, 2, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-slate-500 mt-1.5">
                                    Determina o valor global anual do contrato para o ano letivo.
                                </p>
                            </div>

                            {{-- Quantidade de Prestações (Exibido para Cartão ou Carnê) --}}
                            <div id="bloco_qtd_parcelas" class="hidden">
                                <label for="qtd_parcelas" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Quantidade de Prestações <span class="text-rose-500">*</span>
                                </label>
                                <select name="qtd_parcelas" id="qtd_parcelas" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 text-slate-800 text-xs md:text-sm rounded-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition shadow-2xs font-semibold">
                                    @for($p = 12; $p >= 1; $p--)
                                        <option value="{{ $p }}" {{ old('qtd_parcelas', 12) == $p ? 'selected' : '' }}>
                                            {{ $p }}x {{ $p === 1 ? '(Pagamento Integral)' : 'prestações mensais' }}
                                        </option>
                                    @endfor
                                </select>
                                <p class="text-[11px] text-slate-500 mt-1.5">
                                    As parcelas serão geradas a partir do mês atual com vencimento no dia selecionado.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1" id="grid_vencimento_desconto">
                                {{-- Dia de Vencimento com Segmented Control e Input (Exibido apenas para Cartão Online, Carnê ou Boleto) --}}
                                <div id="bloco_dia_vencimento" class="hidden">
                                    <label for="dia_vencimento" id="label_dia_vencimento" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Dia de Vencimento Mensal <span class="text-rose-500">*</span>
                                    </label>
                                    
                                    <div class="financeiro-input-group">
                                        <div class="financeiro-input-addon" style="font-size: 11px;">
                                            Dia
                                        </div>
                                        <input type="number" 
                                               name="dia_vencimento" 
                                               id="dia_vencimento" 
                                               min="1" 
                                               max="31" 
                                               value="{{ old('dia_vencimento', 10) }}" 
                                               class="financeiro-input-field font-bold">
                                    </div>
                                    
                                    {{-- Botões de Atalho Estilizados como Pills --}}
                                    <div id="atalhos_dia_vencimento" class="flex items-center gap-1.5 mt-2 flex-wrap">
                                        <span class="text-[11px] text-slate-400 mr-1">Atalhos:</span>
                                        <button type="button" class="btn-dia-preset" data-dia="5">Dia 05</button>
                                        <button type="button" class="btn-dia-preset active" data-dia="10">Dia 10</button>
                                        <button type="button" class="btn-dia-preset" data-dia="15">Dia 15</button>
                                        <button type="button" class="btn-dia-preset" data-dia="20">Dia 20</button>
                                    </div>

                                    <p id="hint_boleto_vencimento" class="text-[11px] text-amber-600 font-semibold mt-1.5 hidden">
                                        * Vencimento do boleto calculado para 2 dias após a data atual.
                                    </p>
                                </div>

                                {{-- Desconto Total Concedido (Blindado com Prepend R$) --}}
                                <div id="bloco_desconto" class="sm:col-span-2">
                                    <label for="valor_total_descontos" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Desconto Total Concedido (R$)
                                    </label>
                                    
                                    <div class="financeiro-input-group">
                                        <div class="financeiro-input-addon" style="background-color: #f1f5f9; color: #475569;">
                                            R$
                                        </div>
                                        <input type="number" 
                                               name="valor_total_descontos" 
                                               id="valor_total_descontos" 
                                               step="0.01" 
                                               min="0" 
                                               value="{{ old('valor_total_descontos', '0.00') }}" 
                                               class="financeiro-input-field font-semibold text-slate-900">
                                    </div>
                                    
                                    <p class="text-[11px] text-slate-500 mt-2">
                                        Bolsa de estudos, convênio empresarial ou desconto comercial direto.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- COLUNA LATERAL (4 COLUNAS): ESPELHO DO CONTRATO / STICKY SUMMARY EM TEMPO REAL --}}
                <div class="lg:col-span-4 space-y-4">
                    <div class="bg-white border border-slate-200 shadow-md rounded-sm overflow-hidden sticky-summary-card">
                        
                        {{-- Topo do Resumo com Azul Oficial do i-Educar --}}
                        <div class="bg-[#47728f] px-4 py-3 text-white flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg width="20" height="20" fill="none" stroke="#ffffff" viewBox="0 0 24 24">
                                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                <span class="text-xs font-bold uppercase tracking-wider text-white">Espelho do Contrato</span>
                            </div>
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 bg-white/20 text-white rounded-full">Tempo Real</span>
                        </div>

                        <div class="p-4 space-y-4 text-xs">

                            {{-- Resumo Aluno --}}
                            <div class="pb-3 border-b border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Aluno Matriculado</span>
                                <div id="resumo_aluno_box" class="text-slate-500 italic">
                                    Aguardando identificação no Passo 1...
                                </div>
                            </div>

                            {{-- Resumo Responsável --}}
                            <div class="pb-3 border-b border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Responsável Financeiro</span>
                                <div id="resumo_resp_box" class="text-slate-500 italic">
                                    Aguardando seleção do titular...
                                </div>
                            </div>

                            {{-- Resumo Financeiro --}}
                            <div class="space-y-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Valores do Contrato</span>
                                
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Valor Bruto:</span>
                                    <span id="sim_valor_bruto" class="font-semibold text-slate-800">R$ 0,00</span>
                                </div>

                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Desconto Concedido:</span>
                                    <span id="sim_valor_desconto" class="font-semibold text-rose-600">- R$ 0,00</span>
                                </div>

                                <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-800 uppercase">Valor Líquido:</span>
                                    <span id="sim_valor_liquido" class="text-xl font-bold text-slate-900">R$ 0,00</span>
                                </div>

                                <div class="p-3 bg-[#f0f7fa] border border-[#47728f]/30 rounded-sm text-center">
                                    <span class="block text-[10px] font-bold text-[#47728f] uppercase tracking-wider">Grade de Parcelamento</span>
                                    <span id="sim_grade_parcelas" class="block text-sm font-extrabold text-[#47728f] mt-0.5">Selecione o plano</span>
                                    <span id="sim_dia_vencimento" class="block text-[11px] text-slate-500 mt-0.5">Vencimento todo dia 10</span>
                                </div>
                            </div>

                            {{-- Resumo Forma de Pagamento --}}
                            <div class="pt-2 border-t border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Modalidade Escolhida</span>
                                <div id="resumo_forma_pagamento" class="p-2.5 bg-slate-50 border border-slate-200 rounded-sm flex items-center justify-between">
                                    <span class="font-bold text-slate-800" id="resumo_forma_label">Carnê Escolar (Asaas)</span>
                                    <span class="text-[10px] font-semibold text-slate-600" id="resumo_forma_badge">Contrato Ativo</span>
                                </div>
                            </div>

                            {{-- Botões Oficiais i-Educar de Ação --}}
                            <div class="pt-3 border-t border-slate-200 space-y-2">
                                <button type="submit" id="btn_submit_contrato" class="btn-emitir-contrato">
                                    <svg width="18" height="18" fill="none" stroke="#ffffff" viewBox="0 0 24 24">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                    </svg>
                                    <span>Emitir Contrato e Gerar Parcelas</span>
                                </button>

                                <a href="{{ route('financeiro.contratos.index') }}" class="btn-cancelar-operacao">
                                    Cancelar Operação
                                </a>
                            </div>

                            <div class="p-2 bg-slate-50 text-[11px] text-slate-500 rounded-sm flex items-center gap-1.5 border border-slate-200">
                                <svg width="16" height="16" fill="none" stroke="#059669" viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                <span>Operação registrada no ano letivo ativo {{ $anoAtual }}.</span>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </form>

    </main>
</div>

{{-- MODAL CADASTRO RÁPIDO DE RESPONSÁVEL — Design System Oficial --}}
<div id="modal_complemento_responsavel"
     style="display:none; position:fixed; inset:0; z-index:9999;
            background:rgba(15,23,42,0.6); backdrop-filter:blur(3px);
            align-items:center; justify-content:center; padding:16px;">

    <div style="background:#fff; border:1px solid #cddce6; box-shadow:0 12px 40px rgba(0,0,0,0.22);
                max-width:580px; width:100%; max-height:92vh;
                display:flex; flex-direction:column; border-radius:3px; overflow:hidden;">

        {{-- Cabeçalho azul i-Educar #47728f --}}
        <div style="background-color:#47728f; padding:14px 20px; display:flex;
                    align-items:center; justify-content:space-between; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" fill="none" stroke="#ffffff" viewBox="0 0 24 24">
                    <path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
                <h3 style="margin:0; font-size:14px; font-weight:700; color:#ffffff;
                           font-family:'Inter',sans-serif; letter-spacing:0.02em;">
                    Completar Cadastro do Responsável Financeiro
                </h3>
            </div>
            <button type="button" id="btn_fechar_modal"
                    style="background:none; border:none; color:#fff; font-size:18px;
                           font-weight:700; cursor:pointer; line-height:1; padding:0 4px;"
                    title="Fechar">✕</button>
        </div>

        {{-- Corpo com scroll interno --}}
        <form id="form_modal_responsavel"
              style="padding:20px; overflow-y:auto; flex:1; display:flex; flex-direction:column; gap:14px; font-family:'Inter',sans-serif;">

            <input type="hidden" id="modal_pessoa_id" name="pessoa_id">
            <input type="hidden" id="modal_responsavel_id" name="responsavel_financeiro_id">
            <input type="hidden" id="modal_parentesco" name="parentesco">

            {{-- Aviso --}}
            <div style="margin:0; font-size:12px; padding:10px 14px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; display:flex; align-items:center; gap:8px;">
                <span style="font-size:16px;">ℹ️</span>
                <span><strong>Aviso:</strong> O CPF e dados de endereço são exigidos pelo Banco Central e pelo Asaas para registro de boletos e Pix.</span>
            </div>

            {{-- Nome --}}
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#334155;
                              text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                    Nome Completo <span style="color:#e11d48;">*</span>
                </label>
                <input type="text" id="modal_nome" name="nome" required
                       class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f]">
            </div>

            {{-- CPF + Celular --}}
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#334155;
                                  text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                        CPF <span style="color:#e11d48;">*</span>
                    </label>
                    <input type="text" id="modal_cpf" name="cpf_cnpj" required
                           placeholder="000.000.000-00" maxlength="14"
                           class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f]">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#334155;
                                  text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                        Telefone Celular / WhatsApp <span style="color:#e11d48;">*</span>
                    </label>
                    <input type="text" id="modal_celular" name="telefone_celular" required
                           placeholder="(00) 00000-0000" maxlength="15"
                           class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f]">
                </div>
            </div>

            {{-- E-mail --}}
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#334155;
                              text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                    E-mail para Cobranças e Recibos
                </label>
                <input type="email" id="modal_email" name="email"
                       placeholder="responsavel@email.com"
                       class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f]">
            </div>

            {{-- CEP + Bairro --}}
            <div style="display:grid; grid-template-columns:140px 1fr; gap:12px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#334155;
                                  text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                        CEP
                    </label>
                    <input type="text" id="modal_cep" name="cep"
                           placeholder="00000-000" maxlength="9"
                           class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f]">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#334155;
                                  text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                        Bairro
                    </label>
                    <input type="text" id="modal_bairro" name="bairro" placeholder="Bairro"
                           class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f]">
                </div>
            </div>

            {{-- Logradouro + Número --}}
            <div style="display:grid; grid-template-columns:1fr 90px; gap:12px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#334155;
                                  text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                        Logradouro / Rua
                    </label>
                    <input type="text" id="modal_logradouro" name="logradouro" placeholder="Rua, Av..."
                           class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f]">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#334155;
                                  text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                        Número
                    </label>
                    <input type="text" id="modal_numero" name="numero" placeholder="123"
                           class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f]">
                </div>
            </div>

            {{-- Cidade + UF --}}
            <div style="display:grid; grid-template-columns:1fr 80px; gap:12px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#334155;
                                  text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                        Cidade
                    </label>
                    <input type="text" id="modal_cidade" name="cidade" placeholder="Cidade"
                           class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f]">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#334155;
                                  text-transform:uppercase; letter-spacing:0.05em; margin-bottom:4px;">
                        UF
                    </label>
                    <input type="text" id="modal_estado" name="estado" placeholder="UF"
                           maxlength="2" style="text-transform:uppercase;"
                           class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f]">
                </div>
            </div>

            {{-- Erro inline --}}
            <div id="modal_erro_msg"
                 style="display:none; font-size:12px; color:#991b1b; background:#fef2f2;
                        border:1px solid #fecaca; padding:10px 14px; border-radius:3px; font-weight:600;"></div>

            {{-- Botões do Modal --}}
            <div style="display:flex; justify-content:flex-end; gap:10px;
                        padding-top:14px; border-top:1px solid #e2e8f0; flex-shrink:0; margin-top:auto;">
                <button type="button" id="btn_cancelar_modal" class="px-4 py-2 btn-ieducar-action btn-ieducar-outline text-xs">
                    Cancelar
                </button>
                <button type="submit" id="btn_salvar_modal" class="px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs font-semibold">
                    Salvar e Vincular ao Contrato
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const termoInput = document.getElementById('termo_busca_aluno');
    const dropdown = document.getElementById('dropdown_resultados_aluno');
    const listaResultados = document.getElementById('lista_resultados_aluno');
    const spinner = document.getElementById('busca_spinner');
    const alertaNaoEncontrado = document.getElementById('alerta_aluno_nao_encontrado');
    const cardAlunoSelecionado = document.getElementById('card_aluno_selecionado');
    const seletorMatricula = document.getElementById('seletor_matricula_ativa');
    const placeholderSemAluno = document.getElementById('placeholder_sem_aluno');
    const blocoResponsaveis = document.getElementById('bloco_responsaveis_secao');
    const tabelaResponsaveisCorpo = document.getElementById('tabela_responsaveis_corpo');
    const alertaRespSelecionado = document.getElementById('alerta_responsavel_selecionado');
    const nomeRespSelecionado = document.getElementById('nome_responsavel_selecionado');

    // Stepper Indicators
    const stepInd1 = document.getElementById('step_indicator_1');
    const stepCircle1 = document.getElementById('step_circle_1');
    const stepLabel1 = document.getElementById('step_label_1');

    const stepInd2 = document.getElementById('step_indicator_2');
    const stepCircle2 = document.getElementById('step_circle_2');
    const stepLabel2 = document.getElementById('step_label_2');

    const stepInd3 = document.getElementById('step_indicator_3');
    const stepCircle3 = document.getElementById('step_circle_3');
    const stepLabel3 = document.getElementById('step_label_3');

    const stepInd4 = document.getElementById('step_indicator_4');
    const stepCircle4 = document.getElementById('step_circle_4');
    const stepLabel4 = document.getElementById('step_label_4');

    // Resumo Lateral Elements
    const resumoAlunoBox = document.getElementById('resumo_aluno_box');
    const resumoRespBox = document.getElementById('resumo_resp_box');
    const resumoFormaLabel = document.getElementById('resumo_forma_label');
    const resumoFormaBadge = document.getElementById('resumo_forma_badge');

    // Inputs hidden do form principal
    const inputAlunoId = document.getElementById('aluno_id');
    const inputMatriculaId = document.getElementById('matricula_id');
    const inputEscolaId = document.getElementById('escola_id');
    const inputAnoLetivo = document.getElementById('ano_letivo');
    const inputResponsavelId = document.getElementById('responsavel_financeiro_id');

    // Simulação Financeira
    const selectPlano = document.getElementById('plano_id');
    const selectQtdParcelas = document.getElementById('qtd_parcelas');
    const blocoQtdParcelas = document.getElementById('bloco_qtd_parcelas');
    const inputVencimento = document.getElementById('dia_vencimento');
    const inputDesconto = document.getElementById('valor_total_descontos');
    const simValorBruto = document.getElementById('sim_valor_bruto');
    const simValorDesconto = document.getElementById('sim_valor_desconto');
    const simValorLiquido = document.getElementById('sim_valor_liquido');
    const simGradeParcelas = document.getElementById('sim_grade_parcelas');
    const simDiaVencimento = document.getElementById('sim_dia_vencimento');

    // Modal
    const modal = document.getElementById('modal_complemento_responsavel');
    const formModal = document.getElementById('form_modal_responsavel');
    const modalErro = document.getElementById('modal_erro_msg');

    let debounceTimer = null;
    let responsaveisCache = [];
    let matriculasCache = [];

    // Formatação Moeda BRL
    function formatMoney(valor) {
        return 'R$ ' + parseFloat(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Atualiza Simulação Financeira em Tempo Real
    function atualizarSimulacao() {
        const opt = selectPlano.selectedOptions[0];
        if (!opt || !opt.value) {
            simValorBruto.textContent = 'R$ 0,00';
            simValorDesconto.textContent = '- R$ 0,00';
            simValorLiquido.textContent = 'R$ 0,00';
            simGradeParcelas.textContent = 'Selecione o plano';
            return;
        }

        const valorBruto = parseFloat(opt.dataset.valor || 0);
        const desconto = Math.max(0, parseFloat(inputDesconto.value || 0));
        const valorLiquido = Math.max(0, valorBruto - desconto);
        const dia = parseInt(inputVencimento.value || 10);

        const formaSel = document.querySelector('input[name="forma_pagamento"]:checked');
        const formaVal = formaSel ? formaSel.value : '';

        const isPix = formaVal === 'pix';
        const isDinheiro = formaVal === 'dinheiro';
        const isCartaoPresencial = formaVal === 'cartao_presencial';
        const isBoleto = formaVal === 'boleto';
        const isAvista = isPix || isDinheiro || isBoleto;

        let parcelas = 1;
        if (!isAvista && selectQtdParcelas && selectQtdParcelas.value) {
            parcelas = parseInt(selectQtdParcelas.value);
        }

        const valorParcela = (parcelas > 0 && !isAvista) ? (valorLiquido / parcelas) : valorLiquido;

        simValorBruto.textContent = formatMoney(valorBruto);
        simValorDesconto.textContent = (desconto > 0 ? '- ' : '') + formatMoney(desconto);
        simValorLiquido.textContent = formatMoney(valorLiquido);

        // Previsão das datas a partir do mês atual
        const hoje = new Date();
        const diaHoje = hoje.getDate();
        let mesInicio = hoje.getMonth(); // 0-indexed
        let anoInicio = hoje.getFullYear();
        if (diaHoje > dia) {
            mesInicio += 1;
            if (mesInicio > 11) {
                mesInicio = 0;
                anoInicio += 1;
            }
        }
        let totalMeses = mesInicio + parcelas - 1;
        let anoFim = anoInicio + Math.floor(totalMeses / 12);
        let mesFim = totalMeses % 12;
        const nomeMeses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        const periodoStr = `${nomeMeses[mesInicio]}/${anoInicio}` + (parcelas > 1 ? ` a ${nomeMeses[mesFim]}/${anoFim}` : '');

        if (isPix) {
            simGradeParcelas.textContent = `1x de ${formatMoney(valorLiquido)} (Cota Única Instantânea)`;
            simDiaVencimento.textContent = 'Pagamento instantâneo no ato via Pix';
        } else if (isDinheiro) {
            simGradeParcelas.textContent = `1x de ${formatMoney(valorLiquido)} (À Vista em Dinheiro)`;
            simDiaVencimento.textContent = 'Pagamento no ato no caixa da escola (Nasce Quitado)';
        } else if (isCartaoPresencial) {
            simGradeParcelas.textContent = `${parcelas}x de ${formatMoney(valorParcela)} (Cartão POS)`;
            simDiaVencimento.textContent = parcelas > 1 ? `Parcelado em ${parcelas}x na maquininha` : 'À vista na maquininha física (Nasce Quitado)';
        } else if (isBoleto) {
            const dataBoleto = new Date();
            dataBoleto.setDate(dataBoleto.getDate() + 2);
            const dataBoletoFormatada = `${String(dataBoleto.getDate()).padStart(2, '0')}/${String(dataBoleto.getMonth() + 1).padStart(2, '0')}/${dataBoleto.getFullYear()}`;
            simGradeParcelas.textContent = `1x de ${formatMoney(valorLiquido)} (Boleto Único)`;
            simDiaVencimento.textContent = `Vencimento em 2 dias (${dataBoletoFormatada})`;
        } else if (formaVal === 'cartao_credito') {
            simGradeParcelas.textContent = `${parcelas}x de ${formatMoney(valorParcela)} (Link Cartão)`;
            simDiaVencimento.textContent = `Vencimento todo dia ${dia < 10 ? '0' + dia : dia} (${periodoStr})`;
        } else if (formaVal === 'carne') {
            simGradeParcelas.textContent = `${parcelas}x de ${formatMoney(valorParcela)} (Carnê Escolar)`;
            simDiaVencimento.textContent = `Vencimento todo dia ${dia < 10 ? '0' + dia : dia} (${periodoStr})`;
        } else {
            simGradeParcelas.textContent = `${parcelas}x de ${formatMoney(valorParcela)}`;
            simDiaVencimento.textContent = `Vencimento todo dia ${dia < 10 ? '0' + dia : dia} (${periodoStr})`;
        }

        // Stepper: Se aluno, responsável e forma de pagamento já foram preenchidos, Passo 4 Concluído!
        if (inputAlunoId.value && inputResponsavelId.value && formaSel) {
            stepInd4.className = 'step-item completed';
            stepCircle4.className = 'step-circle done';
            stepCircle4.innerHTML = '✓';
            stepLabel4.textContent = isAvista ? `1x de ${formatMoney(valorLiquido)}` : `${parcelas}x de ${formatMoney(valorParcela)}`;
        } else if (inputAlunoId.value && inputResponsavelId.value) {
            stepLabel4.textContent = `${parcelas} parcelas`;
        }
    }

    selectPlano.addEventListener('change', atualizarSimulacao);
    if (selectQtdParcelas) {
        selectQtdParcelas.addEventListener('change', atualizarSimulacao);
    }
    inputVencimento.addEventListener('input', function() {
        const val = parseInt(this.value);
        document.querySelectorAll('.btn-dia-preset').forEach(b => {
            if (parseInt(b.dataset.dia) === val) {
                b.classList.add('active');
            } else {
                b.classList.remove('active');
            }
        });
        atualizarSimulacao();
    });
    inputDesconto.addEventListener('input', atualizarSimulacao);

    // Atalhos para dia de vencimento
    document.querySelectorAll('.btn-dia-preset').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-dia-preset').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            inputVencimento.value = this.dataset.dia;
            atualizarSimulacao();
        });
    });

    // 1. Busca de Alunos com Debounce
    termoInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const termo = this.value.trim();

        if (termo.length < 2) {
            dropdown.classList.add('hidden');
            alertaNaoEncontrado.classList.add('hidden');
            return;
        }

        spinner.classList.remove('hidden');
        spinner.style.display = 'flex';

        debounceTimer = setTimeout(() => {
            fetch(`{{ route('financeiro.api.alunos.buscar') }}?q=${encodeURIComponent(termo)}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(alunos => {
                spinner.classList.add('hidden');
                spinner.style.display = 'none';
                listaResultados.innerHTML = '';

                if (alunos.length === 0) {
                    dropdown.classList.add('hidden');
                    alertaNaoEncontrado.classList.remove('hidden');
                } else {
                    alertaNaoEncontrado.classList.add('hidden');
                    alunos.forEach(aluno => {
                        const li = document.createElement('li');
                        li.className = 'px-4 py-3 hover:bg-[#f0f7fa] cursor-pointer flex items-center justify-between border-b border-slate-100 last:border-0 transition';
                        li.innerHTML = `
                            <div>
                                <div class="font-bold text-slate-800">${aluno.nome}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Código: <strong class="text-slate-700">#${aluno.id}</strong> ${aluno.cpf ? '• CPF: ' + aluno.cpf : ''} ${aluno.data_nascimento ? '• Nasc: ' + aluno.data_nascimento : ''}
                                </div>
                            </div>
                            <span class="text-xs font-bold text-[#47728f] bg-white border border-[#47728f]/40 px-2.5 py-1 rounded-sm shadow-2xs">Selecionar →</span>
                        `;
                        li.addEventListener('click', () => carregarDetalhesAluno(aluno.id));
                        listaResultados.appendChild(li);
                    });
                    dropdown.classList.remove('hidden');
                }
            })
            .catch(() => {
                spinner.classList.add('hidden');
                spinner.style.display = 'none';
            });
        }, 300);
    });

    // Fechar dropdown ao clicar fora
    document.addEventListener('click', function (e) {
        if (!termoInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // 2. Carrega Detalhes Completos do Aluno
    function carregarDetalhesAluno(alunoId, matriculaDesejadaId = null) {
        dropdown.classList.add('hidden');
        spinner.classList.remove('hidden');
        spinner.style.display = 'flex';

        fetch(`{{ url('financeiro/api/alunos') }}/${alunoId}/detalhes`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('hidden');
            spinner.style.display = 'none';
            if (data.error) {
                alert(data.error);
                return;
            }

            const aluno = data.aluno;
            matriculasCache = data.matriculas || [];
            responsaveisCache = data.responsaveis || [];

            // Preenche hidden
            inputAlunoId.value = aluno.id;

            // Preenche card visual do aluno
            document.getElementById('aluno_nome_display').textContent = aluno.nome;
            document.getElementById('aluno_cod_display').textContent = '#' + aluno.id;
            document.getElementById('aluno_cpf_display').textContent = aluno.cpf || 'Não informado';
            document.getElementById('aluno_nasc_display').textContent = aluno.data_nascimento || 'Não informada';
            
            const partesNome = aluno.nome.split(' ');
            const iniciais = (partesNome[0][0] + (partesNome.length > 1 ? partesNome[partesNome.length - 1][0] : '')).toUpperCase();
            document.getElementById('aluno_avatar_iniciais').textContent = iniciais;

            // Atualiza Resumo Lateral
            resumoAlunoBox.innerHTML = `
                <div class="font-bold text-slate-800">${aluno.nome}</div>
                <div class="text-[11px] text-slate-500">Matrícula: #${aluno.id} ${aluno.cpf ? '• ' + aluno.cpf : ''}</div>
            `;

            // Popula seletor de matrículas
            seletorMatricula.innerHTML = '';
            if (matriculasCache.length === 0) {
                seletorMatricula.innerHTML = '<option value="">Nenhuma matrícula ativa localizada para este aluno</option>';
                inputMatriculaId.value = '';
                inputEscolaId.value = '';
            } else {
                let selecionadaIdx = 0;
                matriculasCache.forEach((m, idx) => {
                    const opt = document.createElement('option');
                    opt.value = m.cod_matricula;
                    opt.textContent = m.descricao_completa;
                    opt.dataset.escolaId = m.escola_id;
                    opt.dataset.ano = m.ano;
                    if (matriculaDesejadaId && m.cod_matricula == matriculaDesejadaId) {
                        selecionadaIdx = idx;
                    }
                    seletorMatricula.appendChild(opt);
                });

                seletorMatricula.selectedIndex = selecionadaIdx;
                aplicarMatriculaSelecionada();
            }

            // Exibe card do aluno e esconde busca
            cardAlunoSelecionado.classList.remove('hidden');
            document.getElementById('bloco_busca_aluno').classList.add('hidden');

            // Atualiza Stepper - Passo 1 Concluído, Passo 2 Ativo
            stepInd1.className = 'step-item completed';
            stepCircle1.className = 'step-circle done';
            stepCircle1.innerHTML = '✓';
            stepLabel1.textContent = aluno.nome;

            stepInd2.className = 'step-item active';
            stepCircle2.className = 'step-circle current';

            // Renderiza responsáveis
            placeholderSemAluno.classList.add('hidden');
            renderizarTabelaResponsaveis();
            blocoResponsaveis.classList.remove('hidden');

            // Auto-selecionar responsável se pré-definido ou se único disponível
            const respPreId = inputResponsavelId.value;
            if (respPreId && responsaveisCache.length > 0) {
                const respEncontrado = responsaveisCache.find(r => r.responsavel_financeiro_id == respPreId);
                if (respEncontrado) {
                    selecionarOuCriarResponsavel(respEncontrado);
                }
            } else if (responsaveisCache.length === 1 && responsaveisCache[0].cadastro_completo) {
                selecionarOuCriarResponsavel(responsaveisCache[0]);
            }
        })
        .catch(err => {
            spinner.classList.add('hidden');
            spinner.style.display = 'none';
            alert('Erro ao carregar dados do aluno: ' + err.message);
        });
    }

    // Ao alternar a matrícula no seletor
    seletorMatricula.addEventListener('change', aplicarMatriculaSelecionada);

    function aplicarMatriculaSelecionada() {
        const opt = seletorMatricula.selectedOptions[0];
        if (opt && opt.value) {
            inputMatriculaId.value = opt.value;
            if (!opt.dataset.escolaId) {
                console.error('[Financeiro] escola_id ausente na matrícula selecionada', opt);
                inputEscolaId.value = '';
            } else {
                inputEscolaId.value = opt.dataset.escolaId;
            }
            inputAnoLetivo.value = opt.dataset.ano || {{ $anoAtual }};
        }
    }

    // Botão de trocar aluno
    document.getElementById('btn_trocar_aluno').addEventListener('click', function () {
        inputAlunoId.value = '';
        inputMatriculaId.value = '';
        inputResponsavelId.value = '';
        cardAlunoSelecionado.classList.add('hidden');
        blocoResponsaveis.classList.add('hidden');
        placeholderSemAluno.classList.remove('hidden');
        document.getElementById('bloco_busca_aluno').classList.remove('hidden');
        termoInput.value = '';
        termoInput.focus();
        alertaRespSelecionado.classList.add('hidden');
        resumoAlunoBox.innerHTML = '<span class="text-slate-500 italic">Aguardando identificação no Passo 1...</span>';
        resumoRespBox.innerHTML = '<span class="text-slate-500 italic">Aguardando seleção do titular...</span>';

        // Reset Stepper
        stepInd1.className = 'step-item active';
        stepCircle1.className = 'step-circle current';
        stepCircle1.innerHTML = '1';
        stepLabel1.textContent = 'Aluno & Matrícula';

        stepInd2.className = 'step-item';
        stepCircle2.className = 'step-circle pending';
        stepCircle2.innerHTML = '2';
        stepLabel2.textContent = 'Responsável Financeiro';

        stepInd3.className = 'step-item';
        stepCircle3.className = 'step-circle pending';
        stepCircle3.innerHTML = '3';
        stepLabel3.textContent = 'Plano & Parcelas';

        stepInd4.className = 'step-item';
        stepCircle4.className = 'step-circle pending';
        stepCircle4.innerHTML = '4';
    });

    // 3. Renderizar Tabela de Responsáveis Identificados
    function renderizarTabelaResponsaveis() {
        tabelaResponsaveisCorpo.innerHTML = '';

        if (responsaveisCache.length === 0) {
            tabelaResponsaveisCorpo.innerHTML = `
                <tr>
                    <td colspan="6" class="p-6 text-center text-slate-500">
                        Nenhum responsável vinculado localizado no cadastro desta matrícula.
                    </td>
                </tr>
            `;
            return;
        }

        responsaveisCache.forEach((resp, idx) => {
            const tr = document.createElement('tr');
            const ehSelecionado = inputResponsavelId.value == resp.responsavel_financeiro_id && resp.responsavel_financeiro_id;
            tr.className = 'hover:bg-slate-50 transition ' + (ehSelecionado ? 'resp-card-selected' : '');
            tr.id = `linha_resp_${idx}`;

            let statusBadge = '';
            let botaoAcao = '';

            if (ehSelecionado) {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold">✓ Titular Selecionado</span>';
                botaoAcao = '<span class="text-xs font-bold text-emerald-700">✓ Titular Ativo</span>';
            } else if (resp.ja_cadastrado_financeiro && resp.cadastro_completo) {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-50 text-[#47728f] border border-blue-200 text-xs font-semibold">Pronto p/ Cobrança</span>';
                botaoAcao = `
                    <button type="button" class="btn-selecionar-resp px-3.5 py-1.5 btn-ieducar-action btn-ieducar-green text-xs font-semibold" data-idx="${idx}">
                        Selecionar Titular
                    </button>
                `;
            } else if (resp.cadastro_completo) {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium">Disponível</span>';
                botaoAcao = `
                    <button type="button" class="btn-selecionar-resp px-3.5 py-1.5 btn-ieducar-action btn-ieducar-green text-xs font-semibold" data-idx="${idx}">
                        Vincular Titular
                    </button>
                `;
            } else {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-300 text-xs font-medium">⚠️ Dados Incompletos</span>';
                botaoAcao = `
                    <button type="button" class="btn-completar-resp px-3.5 py-1.5 btn-ieducar-action btn-ieducar-outline text-xs text-amber-800 border-amber-300 font-semibold" data-idx="${idx}">
                        Completar Cadastro
                    </button>
                `;
            }

            tr.innerHTML = `
                <td class="py-3 px-4 font-bold text-slate-800">${resp.parentesco}</td>
                <td class="py-3 px-4">
                    <div class="font-bold text-slate-900">${resp.nome}</div>
                    ${resp.email ? '<div class="text-[11px] text-slate-400">' + resp.email + '</div>' : ''}
                </td>
                <td class="py-3 px-4 text-slate-700 font-mono font-medium">${resp.cpf || '<span class="text-amber-600 font-sans text-xs">Não cadastrado</span>'}</td>
                <td class="py-3 px-4 text-slate-600 font-medium">${resp.telefone_celular || '<span class="text-slate-400">--</span>'}</td>
                <td class="py-3 px-4 text-center">${statusBadge}</td>
                <td class="py-3 px-4 text-right whitespace-nowrap">${botaoAcao}</td>
            `;

            tabelaResponsaveisCorpo.appendChild(tr);
        });

        // Eventos dos botões
        document.querySelectorAll('.btn-selecionar-resp').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.idx);
                selecionarOuCriarResponsavel(responsaveisCache[idx]);
            });
        });

        document.querySelectorAll('.btn-completar-resp').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.idx);
                abrirModalComplemento(responsaveisCache[idx]);
            });
        });
    }

    // 4. Selecionar ou Criar Responsável Direto
    function selecionarOuCriarResponsavel(resp) {
        spinner.classList.remove('hidden');
        spinner.style.display = 'flex';

        fetch(`{{ route('financeiro.api.responsaveis.vincular-ou-criar') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                aluno_id: inputAlunoId.value,
                responsavel_financeiro_id: resp.responsavel_financeiro_id,
                pessoa_id: resp.pessoa_id,
                parentesco: resp.parentesco,
                nome: resp.nome,
                cpf_cnpj: resp.cpf_puro || resp.cpf,
                email: resp.email,
                telefone_celular: resp.telefone_celular,
                cep: resp.cep,
                logradouro: resp.logradouro,
                numero: resp.numero,
                complemento: resp.complemento,
                bairro: resp.bairro,
                cidade: resp.cidade,
                estado: resp.estado
            })
        })
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('hidden');
            spinner.style.display = 'none';
            if (data.error) {
                alert(data.error);
                return;
            }

            // Atualiza hidden e status
            inputResponsavelId.value = data.responsavel.id;
            nomeRespSelecionado.textContent = data.responsavel.nome + (data.responsavel.cpf ? ' (' + data.responsavel.cpf + ')' : '');
            alertaRespSelecionado.classList.remove('hidden');

            // Atualiza Resumo Lateral
            resumoRespBox.innerHTML = `
                <div class="font-bold text-slate-800">${data.responsavel.nome}</div>
                <div class="text-[11px] text-slate-500">${data.responsavel.cpf ? 'CPF: ' + data.responsavel.cpf : 'Sem CPF'} • ${resp.parentesco || 'Responsável'}</div>
            `;

            // Atualiza cache local
            const item = responsaveisCache.find(r => (r.pessoa_id && r.pessoa_id == resp.pessoa_id) || r.nome == resp.nome);
            if (item) {
                item.responsavel_financeiro_id = data.responsavel.id;
                item.ja_cadastrado_financeiro = true;
                item.cadastro_completo = true;
            }

            // Atualiza Stepper: Passo 2 Completo, Passo 3 Ativo
            stepInd2.className = 'step-item completed';
            stepCircle2.className = 'step-circle done';
            stepCircle2.innerHTML = '✓';
            stepLabel2.textContent = data.responsavel.nome;

            const formaSel = document.querySelector('input[name="forma_pagamento"]:checked');
            if (formaSel) {
                stepInd3.className = 'step-item completed';
                stepCircle3.className = 'step-circle done';
                stepCircle3.innerHTML = '✓';
                stepLabel3.textContent = formasInfo[formaSel.value] ? formasInfo[formaSel.value].label : 'Forma Escolhida';

                if (selectPlano && selectPlano.value) {
                    stepInd4.className = 'step-item completed';
                    stepCircle4.className = 'step-circle done';
                    stepCircle4.innerHTML = '✓';
                } else {
                    stepInd4.className = 'step-item active';
                    stepCircle4.className = 'step-circle current';
                }
            } else {
                stepInd3.className = 'step-item active';
                stepCircle3.className = 'step-circle current';
            }

            renderizarTabelaResponsaveis();
            atualizarSimulacao();
        })
        .catch(err => {
            spinner.classList.add('hidden');
            spinner.style.display = 'none';
            alert('Erro ao vincular responsável: ' + err.message);
        });
    }

    // 5. Modal Rápido de Complemento
    function dispararMascara(el) {
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function abrirModalComplemento(resp) {
        modalErro.style.display = 'none';
        document.getElementById('modal_pessoa_id').value      = resp.pessoa_id || '';
        document.getElementById('modal_responsavel_id').value = resp.responsavel_financeiro_id || '';
        document.getElementById('modal_parentesco').value    = resp.parentesco || 'Responsável';
        document.getElementById('modal_nome').value          = resp.nome || '';
        document.getElementById('modal_email').value         = resp.email || '';
        document.getElementById('modal_logradouro').value    = resp.logradouro || '';
        document.getElementById('modal_numero').value        = resp.numero || '';
        document.getElementById('modal_bairro').value        = resp.bairro || '';
        document.getElementById('modal_cidade').value        = resp.cidade || '';
        document.getElementById('modal_estado').value        = resp.estado || '';

        modalCpf.value = (resp.cpf_puro || resp.cpf || '').replace(/\D/g, '');
        dispararMascara(modalCpf);

        modalCelular.value = (resp.telefone_celular || '').replace(/\D/g, '');
        dispararMascara(modalCelular);

        const cepDigitos = (resp.cep || '').replace(/\D/g, '');
        modalCep.value = cepDigitos;
        dispararMascara(modalCep);

        modal.style.display = 'flex';
        const primeiroVazio = [modalCpf, modalCelular].find(el => !el.value.trim());
        if (primeiroVazio) setTimeout(() => primeiroVazio.focus(), 80);
    }

    function fecharModal() {
        modal.style.display = 'none';
    }

    document.getElementById('btn_fechar_modal').addEventListener('click', fecharModal);
    document.getElementById('btn_cancelar_modal').addEventListener('click', fecharModal);

    // Máscaras de CPF, Celular e CEP no Modal
    const modalCpf = document.getElementById('modal_cpf');
    modalCpf.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '');
        if (v.length > 11) v = v.slice(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        this.value = v;
    });

    const modalCelular = document.getElementById('modal_celular');
    modalCelular.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '');
        if (v.length > 11) v = v.slice(0, 11);
        if (v.length > 10) {
            v = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
        } else if (v.length > 5) {
            v = v.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
        } else if (v.length > 2) {
            v = v.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
        }
        this.value = v;
    });

    const modalCep = document.getElementById('modal_cep');
    modalCep.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '');
        if (v.length > 8) v = v.slice(0, 8);
        if (v.length > 5) {
            v = v.replace(/^(\d{5})(\d{1,3})$/, '$1-$2');
        }
        this.value = v;

        const limpo = v.replace(/\D/g, '');
        if (limpo.length === 8) {
            fetch(`https://viacep.com.br/ws/${limpo}/json/`)
                .then(r => r.json())
                .then(end => {
                    if (!end.erro) {
                        if (!document.getElementById('modal_logradouro').value) document.getElementById('modal_logradouro').value = end.logradouro;
                        if (!document.getElementById('modal_bairro').value) document.getElementById('modal_bairro').value = end.bairro;
                        if (!document.getElementById('modal_cidade').value) document.getElementById('modal_cidade').value = end.localidade;
                        if (!document.getElementById('modal_estado').value) document.getElementById('modal_estado').value = end.uf;
                    }
                })
                .catch(() => {});
        }
    });

    function validarCPF(cpf) {
        const d = cpf.replace(/\D/g, '');
        if (d.length !== 11 || /^(\d)\1{10}$/.test(d)) return false;
        let s = 0;
        for (let i = 0; i < 9; i++) s += parseInt(d[i]) * (10 - i);
        let r = (s * 10) % 11; if (r === 10 || r === 11) r = 0;
        if (r !== parseInt(d[9])) return false;
        s = 0;
        for (let i = 0; i < 10; i++) s += parseInt(d[i]) * (11 - i);
        r = (s * 10) % 11; if (r === 10 || r === 11) r = 0;
        return r === parseInt(d[10]);
    }

    // Submissão do Modal
    formModal.addEventListener('submit', function (e) {
        e.preventDefault();
        modalErro.style.display = 'none';

        if (modalCpf.value && !validarCPF(modalCpf.value)) {
            modalErro.textContent = 'CPF inválido. Verifique os dígitos informados.';
            modalErro.style.display = 'block';
            modalCpf.focus();
            return;
        }

        const dados = {
            aluno_id: inputAlunoId.value,
            responsavel_financeiro_id: document.getElementById('modal_responsavel_id').value,
            pessoa_id: document.getElementById('modal_pessoa_id').value,
            parentesco: document.getElementById('modal_parentesco').value,
            nome: document.getElementById('modal_nome').value,
            cpf_cnpj: modalCpf.value,
            telefone_celular: modalCelular.value,
            email: document.getElementById('modal_email').value,
            cep: modalCep.value,
            logradouro: document.getElementById('modal_logradouro').value,
            numero: document.getElementById('modal_numero').value,
            bairro: document.getElementById('modal_bairro').value,
            cidade: document.getElementById('modal_cidade').value,
            estado: document.getElementById('modal_estado').value
        };

        const btnSalvar = document.getElementById('btn_salvar_modal');
        btnSalvar.disabled = true;
        btnSalvar.textContent = 'Salvando...';

        fetch(`{{ route('financeiro.api.responsaveis.vincular-ou-criar') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(dados)
        })
        .then(res => res.json())
        .then(data => {
            btnSalvar.disabled = false;
            btnSalvar.textContent = 'Salvar e Vincular ao Contrato';

            if (data.error) {
                modalErro.textContent = data.error;
                modalErro.style.display = 'block';
                return;
            }

            fecharModal();

            // Atualiza hidden e status
            inputResponsavelId.value = data.responsavel.id;
            nomeRespSelecionado.textContent = data.responsavel.nome + (data.responsavel.cpf ? ' (' + data.responsavel.cpf + ')' : '');
            alertaRespSelecionado.classList.remove('hidden');

            // Atualiza Resumo Lateral
            resumoRespBox.innerHTML = `
                <div class="font-bold text-slate-800">${data.responsavel.nome}</div>
                <div class="text-[11px] text-slate-500">${data.responsavel.cpf ? 'CPF: ' + data.responsavel.cpf : 'Sem CPF'} • ${dados.parentesco || 'Responsável'}</div>
            `;

            // Atualiza cache local
            const item = responsaveisCache.find(r => (r.pessoa_id && r.pessoa_id == dados.pessoa_id) || r.nome == dados.nome);
            if (item) {
                item.responsavel_financeiro_id = data.responsavel.id;
                item.cpf = data.responsavel.cpf;
                item.telefone_celular = dados.telefone_celular;
                item.ja_cadastrado_financeiro = true;
                item.cadastro_completo = true;
            }

            // Atualiza Stepper: Passo 2 Concluído, Passo 3 Ativo
            stepInd2.className = 'step-item completed';
            stepCircle2.className = 'step-circle done';
            stepCircle2.innerHTML = '✓';
            stepLabel2.textContent = data.responsavel.nome;

            stepInd3.className = 'step-item active';
            stepCircle3.className = 'step-circle current';

            renderizarTabelaResponsaveis();
            atualizarSimulacao();
        })
        .catch(err => {
            btnSalvar.disabled = false;
            btnSalvar.textContent = 'Salvar e Vincular ao Contrato';
            modalErro.textContent = 'Erro ao processar: ' + err.message;
            modalErro.style.display = 'block';
        });
    });

    // Validação antes do submit do form do contrato
    document.getElementById('formcadastro').addEventListener('submit', function (e) {
        if (!inputAlunoId.value) {
            e.preventDefault();
            alert('Por favor, busque e selecione um aluno no sistema.');
            termoInput.focus();
            return false;
        }

        if (!inputMatriculaId.value) {
            e.preventDefault();
            alert('Nenhuma matrícula ativa selecionada para este aluno.');
            return false;
        }

        const btnSubmit = document.getElementById('btn_submit_contrato');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `
            <svg class="animate-spin" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Emitindo Contrato...
        `;
    });

    // Toggle visual das opções de pagamento
    const paymentCards = document.querySelectorAll('.payment-radio-card');
    const blocoCartaoPresencial = document.getElementById('bloco_cartao_presencial_detalhes');
    const blocoCartaoCredito = document.getElementById('bloco_cartao_credito_detalhes');
    const blocoDiaVencimento = document.getElementById('bloco_dia_vencimento');
    const labelDiaVencimento = document.getElementById('label_dia_vencimento');
    const atalhosDiaVencimento = document.getElementById('atalhos_dia_vencimento');
    const hintBoletoVencimento = document.getElementById('hint_boleto_vencimento');
    const blocoDesconto = document.getElementById('bloco_desconto');

    const formasInfo = {
        'dinheiro': { label: 'Dinheiro (À Vista)', badge: 'Nasce Quitado' },
        'cartao_presencial': { label: 'Cartão Presencial (POS)', badge: 'Nasce Quitado' },
        'cartao_credito': { label: 'Cartão Online (Link Asaas)', badge: 'Aguardando Webhook' },
        'pix': { label: 'PIX Instantâneo (24h)', badge: 'Confirmação em 3s' },
        'boleto': { label: 'Boleto Bancário (1x)', badge: 'Compensação D+1' },
        'carne': { label: 'Carnê Escolar (Asaas)', badge: 'Contrato Ativo' }
    };

    function atualizarOpcoesPagamento() {
        paymentCards.forEach(card => card.classList.remove('selected'));

        if (blocoCartaoPresencial) blocoCartaoPresencial.classList.add('hidden');
        if (blocoCartaoCredito) blocoCartaoCredito.classList.add('hidden');

        const selecionado = document.querySelector('input[name="forma_pagamento"]:checked');
        if (selecionado) {
            const card = selecionado.closest('.payment-radio-card');
            if (card) {
                card.classList.add('selected');
            }

            if (selecionado.value === 'cartao_presencial' && blocoCartaoPresencial) {
                blocoCartaoPresencial.classList.remove('hidden');
            } else if (selecionado.value === 'cartao_credito' && blocoCartaoCredito) {
                blocoCartaoCredito.classList.remove('hidden');
            }

            if (formasInfo[selecionado.value]) {
                resumoFormaLabel.textContent = formasInfo[selecionado.value].label;
                resumoFormaBadge.textContent = formasInfo[selecionado.value].badge;
            }

            // Visibilidade da Quantidade de Prestações:
            // Exibido para: "cartão online", "carne" ou "cartão presencial"
            const aceitaParcelamento = ['cartao_credito', 'carne', 'cartao_presencial'].includes(selecionado.value);
            if (blocoQtdParcelas) {
                if (aceitaParcelamento) {
                    blocoQtdParcelas.classList.remove('hidden');
                } else {
                    blocoQtdParcelas.classList.add('hidden');
                    if (selectQtdParcelas) {
                        selectQtdParcelas.value = '1';
                    }
                }
            }

            // Visibilidade do Campo Dia de Vencimento Mensal:
            // Só é exibido para: "cartão online", "carne" ou "boleto avista" (vencimento 2 dias posterior ao atual)
            const exigeVencimento = ['cartao_credito', 'carne', 'boleto'].includes(selecionado.value);

            if (blocoDiaVencimento) {
                if (exigeVencimento) {
                    blocoDiaVencimento.classList.remove('hidden');
                    if (blocoDesconto) {
                        blocoDesconto.classList.remove('sm:col-span-2');
                    }

                    if (selecionado.value === 'boleto') {
                        // Boleto à vista: 2 dias posterior ao atual para vencimento
                        const dataBoleto = new Date();
                        dataBoleto.setDate(dataBoleto.getDate() + 2);
                        const diaBoleto = dataBoleto.getDate();
                        const mesBoleto = String(dataBoleto.getMonth() + 1).padStart(2, '0');
                        const anoBoleto = dataBoleto.getFullYear();

                        if (labelDiaVencimento) {
                            labelDiaVencimento.innerHTML = 'Dia de Vencimento do Boleto <span class="text-rose-500">*</span>';
                        }
                        if (hintBoletoVencimento) {
                            hintBoletoVencimento.innerHTML = `* Vencimento do boleto calculado para 2 dias após a data atual (${String(diaBoleto).padStart(2, '0')}/${mesBoleto}/${anoBoleto}).`;
                            hintBoletoVencimento.classList.remove('hidden');
                        }
                        if (atalhosDiaVencimento) {
                            atalhosDiaVencimento.classList.add('hidden');
                        }
                        inputVencimento.value = diaBoleto;
                    } else {
                        // Cartão Online ou Carnê
                        if (labelDiaVencimento) {
                            labelDiaVencimento.innerHTML = 'Dia de Vencimento Mensal <span class="text-rose-500">*</span>';
                        }
                        if (hintBoletoVencimento) {
                            hintBoletoVencimento.classList.add('hidden');
                        }
                        if (atalhosDiaVencimento) {
                            atalhosDiaVencimento.classList.remove('hidden');
                        }
                        if (!inputVencimento.value || inputVencimento.value > 31) {
                            inputVencimento.value = 10;
                        }
                    }
                } else {
                    // Dinheiro, Cartão Presencial, Pix: pagamento no ato, não exibe
                    blocoDiaVencimento.classList.add('hidden');
                    if (blocoDesconto) {
                        blocoDesconto.classList.add('sm:col-span-2');
                    }
                    if (hintBoletoVencimento) {
                        hintBoletoVencimento.classList.add('hidden');
                    }
                    if (!inputVencimento.value) {
                        inputVencimento.value = 10;
                    }
                }
            }

            // Stepper: Passo 3 (Forma de Pagamento)
            if (inputAlunoId.value && inputResponsavelId.value) {
                stepInd3.className = 'step-item completed';
                stepCircle3.className = 'step-circle done';
                stepCircle3.innerHTML = '✓';
                stepLabel3.textContent = formasInfo[selecionado.value] ? formasInfo[selecionado.value].label : 'Forma Escolhida';

                if (selectPlano && selectPlano.value) {
                    stepInd4.className = 'step-item completed';
                    stepCircle4.className = 'step-circle done';
                    stepCircle4.innerHTML = '✓';
                } else {
                    stepInd4.className = 'step-item active';
                    stepCircle4.className = 'step-circle current';
                    stepCircle4.textContent = '4';
                }
            } else {
                stepLabel3.textContent = formasInfo[selecionado.value] ? formasInfo[selecionado.value].label : 'Forma de Pagamento';
            }
        } else {
            // Nenhuma opção selecionada inicialmente
            if (blocoDiaVencimento) {
                blocoDiaVencimento.classList.add('hidden');
                if (blocoDesconto) {
                    blocoDesconto.classList.add('sm:col-span-2');
                }
            }
            if (blocoQtdParcelas) {
                blocoQtdParcelas.classList.add('hidden');
            }
        }

        if (typeof atualizarSimulacao === 'function') {
            atualizarSimulacao();
        }
    }

    paymentCards.forEach(card => {
        card.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                atualizarOpcoesPagamento();
            }
        });
    });

    document.querySelectorAll('input[name="forma_pagamento"]').forEach(radio => {
        radio.addEventListener('change', atualizarOpcoesPagamento);
    });

    atualizarOpcoesPagamento();

    // Pré-carregamento automático do Aluno e Matrícula (via query params ?aluno_id=&matricula_id= ou old())
    @php
        $initAlunoId = old('aluno_id', $preAlunoId ?? null);
        $initMatriculaId = old('matricula_id', $preMatriculaId ?? null);
    @endphp
    @if(!empty($initAlunoId))
        carregarDetalhesAluno({{ (int) $initAlunoId }}, {{ !empty($initMatriculaId) ? (int) $initMatriculaId : 'null' }});
    @endif

    // Inicializa simulação se já tiver plano selecionado
    if (selectPlano.value) {
        atualizarSimulacao();
    }
});
</script>
@endpush
@endsection
