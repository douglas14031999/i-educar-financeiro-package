@extends('financeiro::layouts.app')

@section('title', 'Novo Contrato Financeiro')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
    <style>
        .aluno-search-dropdown {
            max-height: 280px;
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
            border-color: #0ac336 !important;
            background-color: #f0fdf4 !important;
        }
        .btn-dia-preset.active {
            background-color: #47728f !important;
            color: #ffffff !important;
            border-color: #355972 !important;
        }
    </style>
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="contrato-create-container">
        
        {{-- Cabeçalho Padrão i-Educar com Breadcrumb no Topo --}}
        @include('financeiro::partials.page-header', [
            'title' => 'Novo Contrato Financeiro Escolar',
            'subtitle' => 'Busque o aluno no cadastro do i-Educar, vincule o responsável e gere a grade de títulos e mensalidades',
            'breadcrumbs' => [
                ['label' => 'Contratos', 'url' => route('financeiro.contratos.index')],
                ['label' => 'Novo Contrato']
            ],
            'actions' => '
                <a href="' . route('financeiro.contratos.index') . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar para Contratos
                </a>
            '
        ])

        {{-- Formulário Principal em Card Design System --}}
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            {{-- Topo do Card Petróleo #47728f --}}
            <div class="bg-[#47728f] px-5 py-3.5 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Emissão de Contrato Financeiro e Grade de Parcelas
                    </h2>
                </div>
                <span class="text-xs text-slate-200 flex items-center gap-1">
                    <span class="text-amber-300 font-bold">*</span> Campos obrigatórios
                </span>
            </div>

            {{-- Stepper Visual com Fluxo Operacional --}}
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    {{-- Etapa 1 --}}
                    <div id="step_indicator_1" class="flex items-center gap-3 p-2.5 rounded bg-white border border-[#47728f]/60 shadow-2xs transition">
                        <div id="step_circle_1" class="w-7 h-7 rounded-full bg-[#47728f] text-white flex items-center justify-center font-bold text-xs shrink-0">
                            1
                        </div>
                        <div class="min-w-0">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Etapa 1</span>
                            <span id="step_label_1" class="block text-xs font-semibold text-slate-900 truncate">Aluno & Matrícula</span>
                        </div>
                    </div>

                    {{-- Etapa 2 --}}
                    <div id="step_indicator_2" class="flex items-center gap-3 p-2.5 rounded bg-white border border-slate-200 shadow-2xs opacity-75 transition">
                        <div id="step_circle_2" class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                            2
                        </div>
                        <div class="min-w-0">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Etapa 2</span>
                            <span id="step_label_2" class="block text-xs font-semibold text-slate-600 truncate">Responsável Financeiro</span>
                        </div>
                    </div>

                    {{-- Etapa 3 --}}
                    <div id="step_indicator_3" class="flex items-center gap-3 p-2.5 rounded bg-white border border-slate-200 shadow-2xs opacity-75 transition">
                        <div id="step_circle_3" class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                            3
                        </div>
                        <div class="min-w-0">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Etapa 3</span>
                            <span id="step_label_3" class="block text-xs font-semibold text-slate-600 truncate">Condições & Simulação</span>
                        </div>
                    </div>
                </div>
            </div>

            <form id="formcadastro" method="post" action="{{ route('financeiro.contratos.store') }}" class="p-5 md:p-6 space-y-6">
                @csrf

                {{-- Campos Ocultos para Submissão do Contrato --}}
                <input type="hidden" name="aluno_id" id="aluno_id" value="{{ old('aluno_id') }}" required>
                <input type="hidden" name="matricula_id" id="matricula_id" value="{{ old('matricula_id') }}" required>
                <input type="hidden" name="escola_id" id="escola_id" value="{{ old('escola_id') }}">
                <input type="hidden" name="ano_letivo" id="ano_letivo" value="{{ old('ano_letivo', $anoAtual) }}" required>
                <input type="hidden" name="responsavel_financeiro_id" id="responsavel_financeiro_id" value="{{ old('responsavel_financeiro_id') }}">

                {{-- ========================================================================= --}}
                {{-- SEÇÃO 1: BUSCA E IDENTIFICAÇÃO DO ALUNO (i-Educar)                         --}}
                {{-- ========================================================================= --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-[#47728f] text-white text-xs font-bold shadow-2xs">1</span>
                            <div>
                                <h3 class="text-xs md:text-sm font-bold text-slate-900 uppercase tracking-wider" style="margin: 0;">
                                    Busca e Identificação do Aluno (Base i-Educar)
                                </h3>
                                <p class="text-xs text-slate-500" style="margin: 2px 0 0 0;">
                                    Localize o aluno matriculado por nome completo, código ou CPF
                                </p>
                            </div>
                        </div>
                        <span class="hidden sm:inline-flex items-center gap-1 text-xs text-slate-500 font-medium">
                            <svg class="w-3.5 h-3.5 text-[#47728f]" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Integração Acadêmica
                        </span>
                    </div>

                    {{-- Campo de Busca com Autocomplete --}}
                    <div class="relative" id="bloco_busca_aluno">
                        <label for="termo_busca_aluno" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Localizar Aluno no Banco de Dados <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative flex items-center">
                            {{-- Ícone de Lupa à Esquerda --}}
                            <div class="absolute left-3.5 text-slate-400 pointer-events-none">
                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                            </div>

                            <input type="text" 
                                   id="termo_busca_aluno" 
                                   placeholder="Digite o nome ou CPF do aluno para buscar automaticamente..." 
                                   autocomplete="off"
                                   class="w-full pl-10 pr-10 py-2.5 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition shadow-2xs">

                            {{-- Spinner de Carregamento --}}
                            <div id="busca_spinner" class="absolute right-3.5 hidden text-[#47728f]">
                                <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-500 mt-1.5">
                            <span>Dica: Digite ao menos 2 caracteres do primeiro nome, sobrenome ou números do CPF.</span>
                            <span class="text-slate-400">Busca em tempo real</span>
                        </div>

                        {{-- Dropdown de Resultados da Busca --}}
                        <div id="dropdown_resultados_aluno" class="aluno-search-dropdown absolute z-30 inset-x-0 mt-1 bg-white border border-slate-200 shadow-lg hidden">
                            <ul id="lista_resultados_aluno" class="divide-y divide-slate-100 text-sm"></ul>
                        </div>
                    </div>

                    {{-- Alerta Caso o Aluno Não Seja Encontrado --}}
                    <div id="alerta_aluno_nao_encontrado" class="hidden p-4 bg-amber-50 border border-amber-200 text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xl">⚠️</span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-amber-800 m-0">Aluno não encontrado no banco do i-Educar</p>
                                <p class="text-xs text-amber-700 mt-0.5 m-0">Nenhum registro ativo foi localizado com o termo informado. Você pode cadastrá-lo como pessoa física agora.</p>
                            </div>
                        </div>
                        <a href="/intranet/atendidos_cad.php" target="_blank" rel="noopener noreferrer" class="px-3.5 py-1.5 btn-ieducar-action btn-ieducar-blue text-xs whitespace-nowrap" style="text-decoration: none;">
                            + Cadastrar Pessoa Física no i-Educar ↗
                        </a>
                    </div>

                    {{-- Card de Resumo do Aluno Selecionado --}}
                    <div id="card_aluno_selecionado" class="hidden bg-slate-50 border border-slate-200 p-4 relative overflow-hidden">
                        <div class="absolute top-0 inset-x-0 h-1 bg-[#47728f]"></div>
                        
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <div class="w-12 h-12 bg-[#47728f] text-white font-bold flex items-center justify-center text-base shadow-2xs shrink-0">
                                    <span id="aluno_avatar_iniciais">AL</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 id="aluno_nome_display" class="text-base font-bold text-slate-900 m-0">NOME DO ALUNO</h4>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-800">
                                            Código: <strong id="aluno_cod_display" class="ml-1">#0000</strong>
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            ✓ Aluno Localizado
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-600 mt-1.5 flex flex-wrap gap-x-5 gap-y-1">
                                        <span>CPF: <strong id="aluno_cpf_display" class="text-slate-800 font-mono">000.000.000-00</strong></span>
                                        <span>Data de Nascimento: <strong id="aluno_nasc_display" class="text-slate-800">--/--/----</strong></span>
                                    </div>
                                </div>
                            </div>

                            <button type="button" id="btn_trocar_aluno" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-outline text-xs self-start md:self-center">
                                <svg class="w-3.5 h-3.5 text-slate-500" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                ✕ Trocar Aluno
                            </button>
                        </div>

                        {{-- Seletor de Matrícula Acadêmica --}}
                        <div class="mt-4 pt-3.5 border-t border-slate-200">
                            <label for="seletor_matricula_ativa" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Matrícula Acadêmica Vinculada <span class="text-rose-500">*</span>
                            </label>
                            <select id="seletor_matricula_ativa" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs md:text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition shadow-2xs">
                                {{-- Populado via JavaScript --}}
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1.5 flex items-center gap-1" id="matricula_detalhes_helper">
                                <svg class="w-3.5 h-3.5 text-[#47728f]" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Seleciona automaticamente o curso regular principal da escola no ano letivo ativo.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ========================================================================= --}}
                {{-- SEÇÃO 2: RESPONSÁVEL FINANCEIRO IDENTIFICADO                               --}}
                {{-- ========================================================================= --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <div class="flex items-center gap-2.5">
                            <span id="passo2_badge" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-xs font-bold shadow-2xs">2</span>
                            <div>
                                <h3 class="text-xs md:text-sm font-bold text-slate-900 uppercase tracking-wider" style="margin: 0;">
                                    Seleção do Responsável Financeiro
                                </h3>
                                <p class="text-xs text-slate-500" style="margin: 2px 0 0 0;">
                                    Mãe, pai ou responsável legal cadastrado que assinará e receberá os títulos
                                </p>
                            </div>
                        </div>
                        <span class="text-xs text-slate-500">Titular da Cobrança</span>
                    </div>

                    {{-- Placeholder Informacional (quando nenhum aluno foi selecionado) --}}
                    <div id="placeholder_sem_aluno" class="p-5 bg-slate-50 border border-dashed border-slate-300 text-center text-slate-500 flex flex-col items-center justify-center gap-1.5">
                        <svg class="w-7 h-7 text-slate-400" width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <p class="text-xs font-semibold text-slate-700 m-0">Aguardando identificação do aluno na Etapa 1</p>
                        <p class="text-[11px] text-slate-500 m-0 max-w-md">Ao selecionar o aluno, os responsáveis vinculados (mãe, pai ou responsável legal) serão listados aqui para seleção em um clique.</p>
                    </div>

                    {{-- Bloco de Responsáveis (Exibido após carregar o aluno) --}}
                    <div id="bloco_responsaveis_secao" class="space-y-4 hidden">
                        {{-- Feedback de quem está selecionado --}}
                        <div id="alerta_responsavel_selecionado" class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 hidden">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                                <div>
                                    <span class="text-emerald-900 font-bold">Responsável Titular Selecionado:</span>
                                    <span id="nome_responsavel_selecionado" class="font-semibold text-emerald-800 ml-1">--</span>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-300">
                                Titular Definido
                            </span>
                        </div>

                        {{-- Tabela / Lista de Responsáveis Disponíveis no i-Educar --}}
                        <div class="bg-white border border-slate-200 overflow-hidden shadow-2xs">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs divide-y divide-slate-200 m-0">
                                    <thead class="bg-slate-100/90 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                                        <tr>
                                            <th class="py-3 px-4">Vínculo / Parentesco</th>
                                            <th class="py-3 px-4">Nome Completo</th>
                                            <th class="py-3 px-4">CPF</th>
                                            <th class="py-3 px-4">Telefone</th>
                                            <th class="py-3 px-4 text-center">Status Financeiro</th>
                                            <th class="py-3 px-4 text-right">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabela_responsaveis_corpo" class="divide-y divide-slate-100 bg-white">
                                        {{-- Preenchido via AJAX com base no aluno selecionado --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========================================================================= --}}
                {{-- SEÇÃO 3: PLANO DE COBRANÇA, CONDIÇÕES E SIMULAÇÃO FINANCEIRA               --}}
                {{-- ========================================================================= --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <div class="flex items-center gap-2.5">
                            <span id="passo3_badge" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-xs font-bold shadow-2xs">3</span>
                            <div>
                                <h3 class="text-xs md:text-sm font-bold text-slate-900 uppercase tracking-wider" style="margin: 0;">
                                    Plano de Cobrança e Condições Comerciais
                                </h3>
                                <p class="text-xs text-slate-500" style="margin: 2px 0 0 0;">
                                    Tabela de anuidade, vencimento das parcelas e cálculo estimado da contratação
                                </p>
                            </div>
                        </div>
                        <span class="text-xs text-slate-500">Parâmetros Financeiros</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        {{-- Coluna 1: Campos do Formulário (7 Colunas) --}}
                        <div class="lg:col-span-7 space-y-4">
                            <div>
                                <label for="plano_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Plano de Pagamento / Anuidade <span class="text-rose-500">*</span>
                                </label>
                                <select name="plano_id" id="plano_id" required class="w-full px-3.5 py-2.5 bg-white border border-slate-300 text-slate-800 text-xs md:text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition shadow-2xs">
                                    <option value="">Selecione o plano configurado...</option>
                                    @foreach($planos as $plano)
                                        <option value="{{ $plano->id }}" 
                                                data-valor="{{ $plano->valor_total }}" 
                                                data-parcelas="{{ $plano->qtd_parcelas }}" 
                                                data-nome="{{ $plano->nome }}"
                                                {{ old('plano_id') == $plano->id ? 'selected' : '' }}>
                                            {{ $plano->nome }} — R$ {{ number_format($plano->valor_total, 2, ',', '.') }} ({{ $plano->qtd_parcelas }}x)
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-slate-500 mt-1.5">
                                    Define o valor total da anuidade, a quantidade de títulos e as regras de cobrança.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="dia_vencimento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Dia de Vencimento Mensal <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="number" name="dia_vencimento" id="dia_vencimento" min="1" max="31" value="{{ old('dia_vencimento', 10) }}" required class="w-full px-3.5 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition shadow-2xs">
                                    
                                    {{-- Botões de Atalho para o Dia de Vencimento --}}
                                    <div class="flex items-center gap-1.5 mt-2">
                                        <span class="text-[11px] text-slate-400">Atalhos:</span>
                                        <button type="button" class="btn-dia-preset px-2 py-0.5 text-[11px] bg-slate-100 hover:bg-[#47728f] hover:text-white border border-slate-200 transition font-medium text-slate-700" data-dia="5">Dia 05</button>
                                        <button type="button" class="btn-dia-preset px-2 py-0.5 text-[11px] bg-slate-100 hover:bg-[#47728f] hover:text-white border border-slate-200 transition font-medium text-slate-700 active" data-dia="10">Dia 10</button>
                                        <button type="button" class="btn-dia-preset px-2 py-0.5 text-[11px] bg-slate-100 hover:bg-[#47728f] hover:text-white border border-slate-200 transition font-medium text-slate-700" data-dia="15">Dia 15</button>
                                        <button type="button" class="btn-dia-preset px-2 py-0.5 text-[11px] bg-slate-100 hover:bg-[#47728f] hover:text-white border border-slate-200 transition font-medium text-slate-700" data-dia="20">Dia 20</button>
                                    </div>
                                </div>

                                <div>
                                    <label for="valor_total_descontos" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Desconto Total Concedido (R$)
                                    </label>
                                    <div class="relative flex items-center">
                                        <span class="absolute left-3 text-xs font-bold text-slate-400 pointer-events-none">R$</span>
                                        <input type="number" name="valor_total_descontos" id="valor_total_descontos" step="0.01" min="0" value="{{ old('valor_total_descontos', '0.00') }}" class="w-full pl-9 pr-3.5 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition shadow-2xs">
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1.5">
                                        Bolsa escolar, convênio ou abatimento direto no contrato global.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Coluna 2: Card de Simulação e Resumo Financeiro (5 Colunas) --}}
                        <div class="lg:col-span-5">
                            <div class="bg-slate-50 border border-slate-200 shadow-xs relative overflow-hidden" id="card_resumo_simulacao">
                                <div class="absolute top-0 inset-x-0 h-1 bg-[#47728f]"></div>
                                
                                <div class="p-3.5 border-b border-slate-200/80 flex items-center justify-between bg-slate-100/60">
                                    <div class="flex items-center gap-2 text-slate-800">
                                        <svg class="w-4 h-4 text-[#47728f]" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Simulação do Contrato</span>
                                    </div>
                                    <span class="text-[10px] font-semibold uppercase px-2 py-0.5 bg-slate-200 text-slate-600">Tempo Real</span>
                                </div>

                                {{-- Corpo da Simulação --}}
                                <div class="p-4 space-y-3">
                                    <div id="simulacao_vazia" class="py-6 text-center text-slate-400">
                                        <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                        </svg>
                                        <p class="text-xs text-slate-500 m-0">Selecione o plano de pagamento para simular os valores e a grade de parcelas.</p>
                                    </div>

                                    <div id="simulacao_conteudo" class="space-y-2.5 hidden">
                                        <div class="flex items-center justify-between text-xs text-slate-600">
                                            <span>Valor Bruto do Plano:</span>
                                            <span id="sim_valor_bruto" class="font-semibold text-slate-800">R$ 0,00</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs text-slate-600">
                                            <span>Desconto Concedido:</span>
                                            <span id="sim_valor_desconto" class="font-semibold text-rose-600">- R$ 0,00</span>
                                        </div>
                                        <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                                            <span class="text-xs font-bold uppercase text-slate-700">Valor Líquido Total:</span>
                                            <span id="sim_valor_liquido" class="text-base font-bold text-slate-900">R$ 0,00</span>
                                        </div>
                                        <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-none text-center">
                                            <span class="block text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Grade de Parcelamento Prevista</span>
                                            <span id="sim_grade_parcelas" class="block text-sm font-extrabold text-emerald-700 mt-0.5">--x de R$ --</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 flex items-center justify-between pt-1">
                                            <span>Vencimento mensal:</span>
                                            <span id="sim_dia_vencimento" class="font-semibold text-slate-700">Todo dia 10</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-2.5 bg-slate-100/70 border-t border-slate-200 text-[11px] text-slate-500 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                    </svg>
                                    <span>Conformidade com a escola e ano letivo ativo.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========================================================================= --}}
                {{-- AÇÕES DO FORMULÁRIO (RODAPÉ)                                              --}}
                {{-- ========================================================================= --}}
                <div class="pt-5 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="text-rose-500 font-bold">*</span>
                        <span>Campos de preenchimento obrigatório para a emissão do contrato.</span>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                        <a href="{{ route('financeiro.contratos.index') }}" class="w-full sm:w-auto text-center px-4 py-2.5 btn-ieducar-action btn-ieducar-outline text-xs md:text-sm" style="text-decoration: none;">
                            Cancelar
                        </a>
                        <button type="submit" id="btn_submit_contrato" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 btn-ieducar-action btn-ieducar-green text-xs md:text-sm font-semibold shadow-xs">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            <span>Emitir Contrato e Gerar Parcelas</span>
                        </button>
                    </div>
                </div>

            </form>
        </div>

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

            {{-- Botões --}}
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
    const stepCircle1 = document.getElementById('step_circle_1');
    const stepLabel1 = document.getElementById('step_label_1');
    const stepInd1 = document.getElementById('step_indicator_1');
    const stepCircle2 = document.getElementById('step_circle_2');
    const stepLabel2 = document.getElementById('step_label_2');
    const stepInd2 = document.getElementById('step_indicator_2');
    const stepCircle3 = document.getElementById('step_circle_3');
    const stepLabel3 = document.getElementById('step_label_3');
    const stepInd3 = document.getElementById('step_indicator_3');
    const passo2Badge = document.getElementById('passo2_badge');
    const passo3Badge = document.getElementById('passo3_badge');

    // Inputs hidden do form principal
    const inputAlunoId = document.getElementById('aluno_id');
    const inputMatriculaId = document.getElementById('matricula_id');
    const inputEscolaId = document.getElementById('escola_id');
    const inputAnoLetivo = document.getElementById('ano_letivo');
    const inputResponsavelId = document.getElementById('responsavel_financeiro_id');

    // Simulação Financeira
    const selectPlano = document.getElementById('plano_id');
    const inputVencimento = document.getElementById('dia_vencimento');
    const inputDesconto = document.getElementById('valor_total_descontos');
    const simVazia = document.getElementById('simulacao_vazia');
    const simConteudo = document.getElementById('simulacao_conteudo');
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
            simVazia.classList.remove('hidden');
            simConteudo.classList.add('hidden');
            return;
        }

        const valorBruto = parseFloat(opt.dataset.valor || 0);
        const parcelas = parseInt(opt.dataset.parcelas || 1);
        const desconto = Math.max(0, parseFloat(inputDesconto.value || 0));
        const valorLiquido = Math.max(0, valorBruto - desconto);
        const valorParcela = parcelas > 0 ? (valorLiquido / parcelas) : valorLiquido;
        const dia = parseInt(inputVencimento.value || 10);

        simValorBruto.textContent = formatMoney(valorBruto);
        simValorDesconto.textContent = (desconto > 0 ? '- ' : '') + formatMoney(desconto);
        simValorLiquido.textContent = formatMoney(valorLiquido);
        simGradeParcelas.textContent = `${parcelas}x de ${formatMoney(valorParcela)}`;
        simDiaVencimento.textContent = `Todo dia ${dia < 10 ? '0' + dia : dia} de cada mês`;

        simVazia.classList.add('hidden');
        simConteudo.classList.remove('hidden');
    }

    selectPlano.addEventListener('change', atualizarSimulacao);
    inputVencimento.addEventListener('input', atualizarSimulacao);
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

        debounceTimer = setTimeout(() => {
            fetch(`{{ route('financeiro.api.alunos.buscar') }}?q=${encodeURIComponent(termo)}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(alunos => {
                spinner.classList.add('hidden');
                listaResultados.innerHTML = '';

                if (alunos.length === 0) {
                    dropdown.classList.add('hidden');
                    alertaNaoEncontrado.classList.remove('hidden');
                } else {
                    alertaNaoEncontrado.classList.add('hidden');
                    alunos.forEach(aluno => {
                        const li = document.createElement('li');
                        li.className = 'px-4 py-2.5 hover:bg-slate-50 cursor-pointer flex items-center justify-between border-b border-slate-100 last:border-0 transition';
                        li.innerHTML = `
                            <div>
                                <div class="font-bold text-slate-800">${aluno.nome}</div>
                                <div class="text-xs text-slate-500">
                                    Código: #${aluno.id} ${aluno.cpf ? '• CPF: ' + aluno.cpf : ''} ${aluno.data_nascimento ? '• Nasc: ' + aluno.data_nascimento : ''}
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-[#47728f] hover:underline flex items-center gap-1">Selecionar →</span>
                        `;
                        li.addEventListener('click', () => carregarDetalhesAluno(aluno.id));
                        listaResultados.appendChild(li);
                    });
                    dropdown.classList.remove('hidden');
                }
            })
            .catch(() => {
                spinner.classList.add('hidden');
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
    function carregarDetalhesAluno(alunoId) {
        dropdown.classList.add('hidden');
        spinner.classList.remove('hidden');

        fetch(`{{ url('financeiro/api/alunos') }}/${alunoId}/detalhes`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('hidden');
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

            // Popula seletor de matrículas (Opção A)
            seletorMatricula.innerHTML = '';
            if (matriculasCache.length === 0) {
                seletorMatricula.innerHTML = '<option value="">Nenhuma matrícula ativa localizada para este aluno</option>';
                inputMatriculaId.value = '';
                inputEscolaId.value = '';
            } else {
                matriculasCache.forEach((m, idx) => {
                    const opt = document.createElement('option');
                    opt.value = m.cod_matricula;
                    opt.textContent = m.descricao_completa;
                    opt.dataset.escolaId = m.escola_id;
                    opt.dataset.ano = m.ano;
                    if (idx === 0) opt.selected = true;
                    seletorMatricula.appendChild(opt);
                });

                aplicarMatriculaSelecionada();
            }

            // Exibe card do aluno e esconde busca
            cardAlunoSelecionado.classList.remove('hidden');
            document.getElementById('bloco_busca_aluno').classList.add('hidden');

            // Atualiza stepper - Etapa 1 completa, Etapa 2 ativa
            stepCircle1.innerHTML = '✓';
            stepCircle1.classList.remove('bg-[#47728f]');
            stepCircle1.classList.add('bg-emerald-600');
            stepLabel1.textContent = aluno.nome;

            stepCircle2.classList.remove('bg-slate-200', 'text-slate-700');
            stepCircle2.classList.add('bg-[#47728f]', 'text-white');
            stepInd2.classList.remove('opacity-75');
            stepInd2.classList.add('border-[#47728f]/60');
            passo2Badge.classList.remove('bg-slate-200', 'text-slate-700');
            passo2Badge.classList.add('bg-[#47728f]', 'text-white');

            // Renderiza responsáveis
            placeholderSemAluno.classList.add('hidden');
            renderizarTabelaResponsaveis();
            blocoResponsaveis.classList.remove('hidden');
        })
        .catch(err => {
            spinner.classList.add('hidden');
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

        // Reset Stepper
        stepCircle1.innerHTML = '1';
        stepCircle1.classList.add('bg-[#47728f]');
        stepCircle1.classList.remove('bg-emerald-600');
        stepLabel1.textContent = 'Aluno & Matrícula';

        stepCircle2.classList.add('bg-slate-200', 'text-slate-700');
        stepCircle2.classList.remove('bg-[#47728f]', 'text-white', 'bg-emerald-600');
        stepInd2.classList.add('opacity-75');
        stepInd2.classList.remove('border-[#47728f]/60');
        passo2Badge.classList.add('bg-slate-200', 'text-slate-700');
        passo2Badge.classList.remove('bg-[#47728f]', 'text-white', 'bg-emerald-600');
        stepLabel2.textContent = 'Responsável Financeiro';
    });

    // 3. Renderizar Tabela de Responsáveis Identificados
    function renderizarTabelaResponsaveis() {
        tabelaResponsaveisCorpo.innerHTML = '';

        if (responsaveisCache.length === 0) {
            tabelaResponsaveisCorpo.innerHTML = `
                <tr>
                    <td colspan="6" class="p-4 text-center text-slate-500">
                        Nenhum responsável identificado no cadastro desta matrícula.
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
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold">✓ Titular Selecionado</span>';
                botaoAcao = '<span class="text-xs font-bold text-emerald-600">✓ Selecionado</span>';
            } else if (resp.ja_cadastrado_financeiro && resp.cadastro_completo) {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 text-xs font-medium">Cadastrado no Financeiro</span>';
                botaoAcao = `
                    <button type="button" class="btn-selecionar-resp px-3 py-1 btn-ieducar-action btn-ieducar-green text-xs" data-idx="${idx}">
                        Selecionar Responsável
                    </button>
                `;
            } else if (resp.cadastro_completo) {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium">Disponível no i-Educar</span>';
                botaoAcao = `
                    <button type="button" class="btn-selecionar-resp px-3 py-1 btn-ieducar-action btn-ieducar-green text-xs" data-idx="${idx}">
                        Selecionar e Vincular
                    </button>
                `;
            } else {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200/70 text-xs font-medium">⚠️ Cadastro Incompleto</span>';
                botaoAcao = `
                    <button type="button" class="btn-completar-resp px-3 py-1 btn-ieducar-action btn-ieducar-outline text-xs text-amber-700 border-amber-300" data-idx="${idx}">
                        Completar Dados
                    </button>
                `;
            }

            tr.innerHTML = `
                <td class="py-3 px-4 font-semibold text-slate-800">${resp.parentesco}</td>
                <td class="py-3 px-4">
                    <div class="font-medium text-slate-900">${resp.nome}</div>
                    ${resp.email ? '<div class="text-[11px] text-slate-400">' + resp.email + '</div>' : ''}
                </td>
                <td class="py-3 px-4 text-slate-700 font-mono">${resp.cpf || '<span class="text-amber-600 font-sans">Não informado</span>'}</td>
                <td class="py-3 px-4 text-slate-600">${resp.telefone_celular || '<span class="text-slate-400">--</span>'}</td>
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
            if (data.error) {
                alert(data.error);
                return;
            }

            // Atualiza hidden e status
            inputResponsavelId.value = data.responsavel.id;
            nomeRespSelecionado.textContent = data.responsavel.nome + (data.responsavel.cpf ? ' (' + data.responsavel.cpf + ')' : '');
            alertaRespSelecionado.classList.remove('hidden');

            // Atualiza cache local
            const item = responsaveisCache.find(r => (r.pessoa_id && r.pessoa_id == resp.pessoa_id) || r.nome == resp.nome);
            if (item) {
                item.responsavel_financeiro_id = data.responsavel.id;
                item.ja_cadastrado_financeiro = true;
                item.cadastro_completo = true;
            }

            // Atualiza Stepper Etapa 2
            stepCircle2.innerHTML = '✓';
            stepCircle2.classList.remove('bg-[#47728f]');
            stepCircle2.classList.add('bg-emerald-600');
            stepLabel2.textContent = data.responsavel.nome;

            // Etapa 3 Ativa
            stepCircle3.classList.remove('bg-slate-200', 'text-slate-700');
            stepCircle3.classList.add('bg-[#47728f]', 'text-white');
            stepInd3.classList.remove('opacity-75');
            stepInd3.classList.add('border-[#47728f]/60');
            passo3Badge.classList.remove('bg-slate-200', 'text-slate-700');
            passo3Badge.classList.add('bg-[#47728f]', 'text-white');

            renderizarTabelaResponsaveis();
        })
        .catch(err => {
            spinner.classList.add('hidden');
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

            // Atualiza cache local
            const item = responsaveisCache.find(r => (r.pessoa_id && r.pessoa_id == dados.pessoa_id) || r.nome == dados.nome);
            if (item) {
                item.responsavel_financeiro_id = data.responsavel.id;
                item.cpf = data.responsavel.cpf;
                item.telefone_celular = dados.telefone_celular;
                item.ja_cadastrado_financeiro = true;
                item.cadastro_completo = true;
            }

            // Atualiza Stepper
            stepCircle2.innerHTML = '✓';
            stepCircle2.classList.remove('bg-[#47728f]');
            stepCircle2.classList.add('bg-emerald-600');
            stepLabel2.textContent = data.responsavel.nome;

            stepCircle3.classList.remove('bg-slate-200', 'text-slate-700');
            stepCircle3.classList.add('bg-[#47728f]', 'text-white');
            stepInd3.classList.remove('opacity-75');
            stepInd3.classList.add('border-[#47728f]/60');
            passo3Badge.classList.remove('bg-slate-200', 'text-slate-700');
            passo3Badge.classList.add('bg-[#47728f]', 'text-white');

            renderizarTabelaResponsaveis();
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
            <svg class="animate-spin w-4 h-4 mr-1 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Emitindo Contrato...
        `;
    });

    // Caso volte de erro com old('aluno_id')
    @if(old('aluno_id'))
        carregarDetalhesAluno({{ old('aluno_id') }});
    @endif

    // Inicializa simulação se já tiver plano selecionado
    if (selectPlano.value) {
        atualizarSimulacao();
    }
});
</script>
@endpush
@endsection
