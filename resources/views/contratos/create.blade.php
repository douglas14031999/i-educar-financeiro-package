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
    </style>
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="contrato-create-container">
        
        {{-- Cabeçalho Padrão i-Educar com Breadcrumb no Topo e sem Ícones em Caixas --}}
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

        {{-- Formulário Principal --}}
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            {{-- Topo do Card Petróleo #47728f --}}
            <div class="bg-[#47728f] px-5 py-3.5 text-white flex items-center justify-between">
                <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                    Dados da Matrícula e Parâmetros Financeiros
                </h2>
                <span class="text-xs text-slate-200">
                    * Campos obrigatórios
                </span>
            </div>

            <form id="formcadastro" method="post" action="{{ route('financeiro.contratos.store') }}" class="p-6 space-y-6">
                @csrf

                {{-- Campos Ocultos para Submissão do Contrato --}}
                <input type="hidden" name="aluno_id" id="aluno_id" value="{{ old('aluno_id') }}" required>
                <input type="hidden" name="matricula_id" id="matricula_id" value="{{ old('matricula_id') }}" required>
                <input type="hidden" name="escola_id" id="escola_id" value="{{ old('escola_id') }}">
                <input type="hidden" name="ano_letivo" id="ano_letivo" value="{{ old('ano_letivo', $anoAtual) }}" required>
                <input type="hidden" name="responsavel_financeiro_id" id="responsavel_financeiro_id" value="{{ old('responsavel_financeiro_id') }}">

                {{-- SEÇÃO 1: BUSCA E IDENTIFICAÇÃO DO ALUNO (i-Educar) --}}
                <div class="space-y-4">
                    <div class="border-b border-slate-200 pb-2 flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <span>1.</span> Busca e Identificação do Aluno (Base i-Educar)
                        </h3>
                        <span class="text-xs text-slate-500">Busca por Nome Completo ou CPF</span>
                    </div>

                    {{-- Campo de Busca com Autocomplete --}}
                    <div class="relative" id="bloco_busca_aluno">
                        <label for="termo_busca_aluno" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Localizar Aluno no Banco de Dados <span class="text-red-500">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <input type="text" 
                                   id="termo_busca_aluno" 
                                   placeholder="Digite o nome ou CPF do aluno para buscar automaticamente..." 
                                   autocomplete="off"
                                   class="w-full pl-10 pr-10 py-2.5 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition shadow-xs">
                            
                            {{-- Ícone de Lupa à Esquerda --}}
                            <div class="absolute left-3 text-slate-400 pointer-events-none">
                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                            </div>

                            {{-- Spinner de Carregamento --}}
                            <div id="busca_spinner" class="absolute right-3 hidden text-[#47728f]">
                                <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>

                        {{-- Dropdown de Resultados da Busca --}}
                        <div id="dropdown_resultados_aluno" class="aluno-search-dropdown absolute z-30 inset-x-0 mt-1 bg-white border border-slate-200 shadow-lg hidden">
                            <ul id="lista_resultados_aluno" class="divide-y divide-slate-100 text-sm"></ul>
                        </div>
                    </div>

                    {{-- Alerta Caso o Aluno Não Seja Encontrado --}}
                    <div id="alerta_aluno_nao_encontrado" class="hidden p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-none flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xl">⚠️</span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-amber-800">Aluno não encontrado no banco do i-Educar</p>
                                <p class="text-xs text-amber-700 mt-0.5">Nenhum registro ativo foi localizado com o termo informado. Você pode cadastrá-lo como pessoa física agora.</p>
                            </div>
                        </div>
                        <a href="/intranet/atendidos_cad.php" target="_blank" rel="noopener noreferrer" class="px-3.5 py-1.5 btn-ieducar-action btn-ieducar-blue text-xs whitespace-nowrap" style="text-decoration: none;">
                            + Cadastrar Pessoa Física no i-Educar ↗
                        </a>
                    </div>

                    {{-- Card de Resumo do Aluno Selecionado --}}
                    <div id="card_aluno_selecionado" class="hidden bg-slate-50 border border-slate-200 p-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <div class="w-10 h-10 bg-[#47728f] text-white font-bold flex items-center justify-center text-sm shadow-xs shrink-0">
                                    <span id="aluno_avatar_iniciais">AL</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 id="aluno_nome_display" class="text-sm font-bold text-slate-900">NOME DO ALUNO</h4>
                                        <span class="text-[11px] px-2 py-0.5 bg-slate-200 text-slate-700 font-medium">
                                            Código: <strong id="aluno_cod_display">#0000</strong>
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-600 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                                        <span>CPF: <strong id="aluno_cpf_display">000.000.000-00</strong></span>
                                        <span>Nascimento: <strong id="aluno_nasc_display">--/--/----</strong></span>
                                    </div>
                                </div>
                            </div>

                            <button type="button" id="btn_trocar_aluno" class="text-xs text-slate-500 hover:text-red-600 font-medium underline flex items-center gap-1 self-start md:self-center">
                                ✕ Trocar Aluno
                            </button>
                        </div>

                        {{-- Seletor de Matrícula Acadêmica (Opção A) --}}
                        <div class="mt-4 pt-3 border-t border-slate-200/80">
                            <label for="seletor_matricula_ativa" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Matrícula Acadêmica Vinculada <span class="text-red-500">*</span>
                            </label>
                            <select id="seletor_matricula_ativa" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs focus:outline-none focus:border-[#47728f]">
                                {{-- Populado via JavaScript --}}
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1" id="matricula_detalhes_helper">
                                Seleciona automaticamente o curso regular principal do ano corrente.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- SEÇÃO 2: RESPONSÁVEL FINANCEIRO IDENTIFICADO --}}
                <div id="bloco_responsaveis_secao" class="space-y-4 hidden">
                    <div class="border-b border-slate-200 pb-2 flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <span>2.</span> Seleção do Responsável Financeiro
                        </h3>
                        <span class="text-xs text-slate-500">Mãe, Pai ou Responsável Legal no i-Educar</span>
                    </div>

                    {{-- Feedback de quem está selecionado --}}
                    <div id="alerta_responsavel_selecionado" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between hidden">
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-600 font-bold">✓</span>
                            <span>Responsável Selecionado para este Contrato: <strong id="nome_responsavel_selecionado">--</strong></span>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">Titular Definido</span>
                    </div>

                    {{-- Tabela / Lista de Responsáveis Disponíveis no i-Educar --}}
                    <div class="border border-slate-200 overflow-x-auto">
                        <table class="w-full text-left text-xs divide-y divide-slate-200">
                            <thead class="bg-slate-100 text-slate-700 font-semibold uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th class="py-2.5 px-3">Vínculo / Parentesco</th>
                                    <th class="py-2.5 px-3">Nome Completo</th>
                                    <th class="py-2.5 px-3">CPF</th>
                                    <th class="py-2.5 px-3">Telefone</th>
                                    <th class="py-2.5 px-3">Status Financeiro</th>
                                    <th class="py-2.5 px-3 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="tabela_responsaveis_corpo" class="divide-y divide-slate-100 bg-white">
                                {{-- Preenchido via AJAX com base no aluno selecionado --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SEÇÃO 3: PLANO DE PAGAMENTO E ANUIDADE --}}
                <div class="space-y-4">
                    <div class="border-b border-slate-200 pb-2 flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <span>3.</span> Plano de Cobrança e Condições
                        </h3>
                        <span class="text-xs text-slate-500">Tabela de Preços e Parcelamento</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="plano_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Plano de Pagamento / Anuidade <span class="text-red-500">*</span>
                            </label>
                            <select name="plano_id" id="plano_id" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition">
                                <option value="">Selecione o plano configurado...</option>
                                @foreach($planos as $plano)
                                    <option value="{{ $plano->id }}" {{ old('plano_id') == $plano->id ? 'selected' : '' }}>
                                        {{ $plano->nome }} — R$ {{ number_format($plano->valor_total, 2, ',', '.') }} ({{ $plano->qtd_parcelas }}x)
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Define valor total, quantidade de títulos e regra de vencimento</p>
                        </div>

                        <div>
                            <label for="dia_vencimento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Dia de Vencimento Mensal <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="dia_vencimento" id="dia_vencimento" min="1" max="31" value="{{ old('dia_vencimento', 10) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition">
                            <p class="text-[11px] text-slate-400 mt-1">Dia do mês em que cada parcela vencerá (ex: 05, 10, 15)</p>
                        </div>

                        <div>
                            <label for="valor_total_descontos" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Desconto Total Concedido (R$)
                            </label>
                            <input type="number" name="valor_total_descontos" id="valor_total_descontos" step="0.01" min="0" value="{{ old('valor_total_descontos', '0.00') }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-[#47728f] focus:ring-1 focus:ring-[#47728f] transition">
                            <p class="text-[11px] text-slate-400 mt-1">Bolsa escolar, convênio ou abatimento direto no contrato global</p>
                        </div>
                    </div>
                </div>

                {{-- AÇÕES DO FORMULÁRIO --}}
                <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-end gap-3">
                    <a href="{{ route('financeiro.contratos.index') }}" class="w-full sm:w-auto text-center px-4 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                        Cancelar
                    </a>
                    <button type="submit" id="btn_submit_contrato" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 btn-ieducar-action btn-ieducar-green">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Emitir Contrato e Gerar Parcelas
                    </button>
                </div>

            </form>
        </div>

    </main>
</div>

{{-- MODAL RÁPIDO DE COMPLEMENTO DE DADOS DO RESPONSÁVEL (Opção A) --}}
<div id="modal_complemento_responsavel" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    {{-- FIX: max-h + overflow-y-auto para viewport pequeno não cortar o cabeçalho --}}
    <div class="bg-white border border-slate-200 shadow-xl max-w-xl w-full flex flex-col animate-in fade-in zoom-in-95 duration-150" style="max-height: 92vh;">
        {{-- Cabeçalho do Modal --}}
        <div class="bg-[#47728f] px-5 py-3.5 text-white flex items-center justify-between">
            <h3 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                Completar Cadastro do Responsável Financeiro
            </h3>
            <button type="button" id="btn_fechar_modal" class="text-white/80 hover:text-white text-lg font-bold leading-none">
                ✕
            </button>
        </div>

        {{-- FIX: overflow-y-auto no body do modal para scroll interno --}}
        <form id="form_modal_responsavel" class="p-5 space-y-4 overflow-y-auto flex-1">
            <input type="hidden" id="modal_pessoa_id" name="pessoa_id">
            <input type="hidden" id="modal_responsavel_id" name="responsavel_financeiro_id">
            <input type="hidden" id="modal_parentesco" name="parentesco">

            <div class="p-3 bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                ℹ️ <strong>Cadastro rápido:</strong> O CPF e o endereço são obrigatórios para emissão de cobranças registradas no Asaas e emissão de recibos.
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Nome Completo <span class="text-red-500">*</span></label>
                    <input type="text" id="modal_nome" name="nome" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs focus:outline-none focus:border-[#47728f]">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">CPF <span class="text-red-500">*</span></label>
                    <input type="text" id="modal_cpf" name="cpf_cnpj" required placeholder="000.000.000-00" maxlength="14" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs focus:outline-none focus:border-[#47728f]">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Telefone Celular <span class="text-red-500">*</span></label>
                    <input type="text" id="modal_celular" name="telefone_celular" required placeholder="(00) 00000-0000" maxlength="15" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs focus:outline-none focus:border-[#47728f]">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">E-mail para Cobranças</label>
                    <input type="email" id="modal_email" name="email" placeholder="responsavel@email.com" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs focus:outline-none focus:border-[#47728f]">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">CEP</label>
                    <input type="text" id="modal_cep" name="cep" placeholder="00000-000" maxlength="9" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs focus:outline-none focus:border-[#47728f]">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Bairro</label>
                    <input type="text" id="modal_bairro" name="bairro" placeholder="Bairro" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs focus:outline-none focus:border-[#47728f]">
                </div>

                <div class="sm:col-span-2 grid grid-cols-3 gap-2">
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Logradouro / Rua</label>
                        <input type="text" id="modal_logradouro" name="logradouro" placeholder="Rua, Av..." class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs focus:outline-none focus:border-[#47728f]">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Número</label>
                        <input type="text" id="modal_numero" name="numero" placeholder="123" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs focus:outline-none focus:border-[#47728f]">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Cidade</label>
                    <input type="text" id="modal_cidade" name="cidade" placeholder="Cidade" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs focus:outline-none focus:border-[#47728f]">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Estado (UF)</label>
                    <input type="text" id="modal_estado" name="estado" placeholder="UF" maxlength="2" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-xs uppercase focus:outline-none focus:border-[#47728f]">
                </div>
            </div>

            <div id="modal_erro_msg" class="text-xs text-red-600 hidden font-medium"></div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2.5">
                <button type="button" id="btn_cancelar_modal" class="px-3.5 py-2 btn-ieducar-action btn-ieducar-outline text-xs">
                    Cancelar
                </button>
                <button type="submit" id="btn_salvar_modal" class="px-4 py-2 btn-ieducar-action btn-ieducar-green text-xs">
                    Salvar e Selecionar para o Contrato
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
    const blocoResponsaveis = document.getElementById('bloco_responsaveis_secao');
    const tabelaResponsaveisCorpo = document.getElementById('tabela_responsaveis_corpo');
    const alertaRespSelecionado = document.getElementById('alerta_responsavel_selecionado');
    const nomeRespSelecionado = document.getElementById('nome_responsavel_selecionado');

    // Inputs hidden do form principal
    const inputAlunoId = document.getElementById('aluno_id');
    const inputMatriculaId = document.getElementById('matricula_id');
    const inputEscolaId = document.getElementById('escola_id');
    const inputAnoLetivo = document.getElementById('ano_letivo');
    const inputResponsavelId = document.getElementById('responsavel_financeiro_id');

    // Modal
    const modal = document.getElementById('modal_complemento_responsavel');
    const formModal = document.getElementById('form_modal_responsavel');
    const modalErro = document.getElementById('modal_erro_msg');

    let debounceTimer = null;
    let responsaveisCache = [];
    let matriculasCache = [];

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
                        li.className = 'px-4 py-2.5 hover:bg-slate-50 cursor-pointer flex items-center justify-between border-b border-slate-100 last:border-0';
                        li.innerHTML = `
                            <div>
                                <div class="font-bold text-slate-800">${aluno.nome}</div>
                                <div class="text-xs text-slate-500">
                                    Código: #${aluno.id} ${aluno.cpf ? '• CPF: ' + aluno.cpf : ''} ${aluno.data_nascimento ? '• Nasc: ' + aluno.data_nascimento : ''}
                                </div>
                            </div>
                            <span class="text-xs text-[#006699] font-semibold hover:underline">Selecionar →</span>
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
                    if (idx === 0) opt.selected = true; // Seleciona automaticamente o curso regular principal
                    seletorMatricula.appendChild(opt);
                });

                aplicarMatriculaSelecionada();
            }

            // Exibe card do aluno e esconde busca
            cardAlunoSelecionado.classList.remove('hidden');
            document.getElementById('bloco_busca_aluno').classList.add('hidden');

            // Renderiza responsáveis
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
            // FIX A03: sem fallback para escola_id=1 — deve vir sempre da matrícula
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
        document.getElementById('bloco_busca_aluno').classList.remove('hidden');
        termoInput.value = '';
        termoInput.focus();
        alertaRespSelecionado.classList.add('hidden');
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
            tr.className = 'hover:bg-slate-50 transition ' + (inputResponsavelId.value == resp.responsavel_financeiro_id && resp.responsavel_financeiro_id ? 'resp-card-selected' : '');
            tr.id = `linha_resp_${idx}`;

            let statusBadge = '';
            let botaoAcao = '';

            const ehSelecionado = inputResponsavelId.value == resp.responsavel_financeiro_id && resp.responsavel_financeiro_id;

            if (ehSelecionado) {
                statusBadge = '<span class="inline-flex items-center px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[11px] font-bold">✓ Titular Selecionado</span>';
                botaoAcao = '<span class="text-xs font-semibold text-emerald-600">✓ Selecionado</span>';
            } else if (resp.ja_cadastrado_financeiro && resp.cadastro_completo) {
                statusBadge = '<span class="inline-flex items-center px-2 py-0.5 bg-sky-100 text-sky-800 text-[11px] font-medium">Cadastrado no Financeiro</span>';
                botaoAcao = `
                    <button type="button" class="btn-selecionar-resp px-3 py-1 btn-ieducar-action btn-ieducar-green text-xs" data-idx="${idx}">
                        Selecionar Responsável
                    </button>
                `;
            } else if (resp.cadastro_completo) {
                statusBadge = '<span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-700 text-[11px] font-medium">Disponível no i-Educar</span>';
                botaoAcao = `
                    <button type="button" class="btn-selecionar-resp px-3 py-1 btn-ieducar-action btn-ieducar-green text-xs" data-idx="${idx}">
                        Selecionar e Vincular
                    </button>
                `;
            } else {
                statusBadge = '<span class="inline-flex items-center px-2 py-0.5 bg-amber-100 text-amber-800 text-[11px] font-medium">⚠️ Cadastro Incompleto</span>';
                botaoAcao = `
                    <button type="button" class="btn-completar-resp px-3 py-1 btn-ieducar-action btn-ieducar-outline text-xs text-amber-700 border-amber-300" data-idx="${idx}">
                        Completar e Selecionar
                    </button>
                `;
            }

            tr.innerHTML = `
                <td class="py-2.5 px-3 font-semibold text-slate-800">${resp.parentesco}</td>
                <td class="py-2.5 px-3">
                    <div class="font-medium text-slate-900">${resp.nome}</div>
                    ${resp.email ? '<div class="text-[11px] text-slate-400">' + resp.email + '</div>' : ''}
                </td>
                <td class="py-2.5 px-3 text-slate-700 font-mono">${resp.cpf || '<span class="text-amber-600">Não informado</span>'}</td>
                <td class="py-2.5 px-3 text-slate-600">${resp.telefone_celular || '<span class="text-slate-400">--</span>'}</td>
                <td class="py-2.5 px-3">${statusBadge}</td>
                <td class="py-2.5 px-3 text-right">${botaoAcao}</td>
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

            renderizarTabelaResponsaveis();
        })
        .catch(err => {
            spinner.classList.add('hidden');
            alert('Erro ao vincular responsável: ' + err.message);
        });
    }

    // 5. Modal Rápido de Complemento
    // FIX: dispara mask via dispatchEvent para CPF/celular/CEP pré-preenchidos
    function dispararMascara(el) {
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function abrirModalComplemento(resp) {
        modalErro.classList.add('hidden');
        document.getElementById('modal_pessoa_id').value    = resp.pessoa_id || '';
        document.getElementById('modal_responsavel_id').value = resp.responsavel_financeiro_id || '';
        document.getElementById('modal_parentesco').value  = resp.parentesco || 'Responsável';
        document.getElementById('modal_nome').value        = resp.nome || '';
        document.getElementById('modal_email').value       = resp.email || '';
        document.getElementById('modal_logradouro').value  = resp.logradouro || '';
        document.getElementById('modal_numero').value      = resp.numero || '';
        document.getElementById('modal_bairro').value      = resp.bairro || '';
        document.getElementById('modal_cidade').value      = resp.cidade || '';
        document.getElementById('modal_estado').value      = resp.estado || '';

        // FIX: seta dígitos puros e dispara máscara para formatar corretamente
        modalCpf.value = (resp.cpf_puro || resp.cpf || '').replace(/\D/g, '');
        dispararMascara(modalCpf);

        modalCelular.value = (resp.telefone_celular || '').replace(/\D/g, '');
        dispararMascara(modalCelular);

        // FIX: seta CEP e dispara lookup ViaCEP mesmo ao re-abrir
        const cepDigitos = (resp.cep || '').replace(/\D/g, '');
        modalCep.value = cepDigitos;
        dispararMascara(modalCep); // formata e dispara ViaCEP se 8 dígitos

        modal.classList.remove('hidden');
        // Foca no primeiro campo vazio obrigatório
        const primeiroVazio = [modalCpf, modalCelular].find(el => !el.value.trim());
        if (primeiroVazio) setTimeout(() => primeiroVazio.focus(), 80);
    }

    function fecharModal() {
        modal.classList.add('hidden');
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

        // Auto busca ViaCEP
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

    // FIX: valida CPF (11 dígitos, não pode ser sequência uniforme)
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
        modalErro.classList.add('hidden');

        // FIX: validação de CPF antes do POST
        if (modalCpf.value && !validarCPF(modalCpf.value)) {
            modalErro.textContent = 'CPF inválido. Verifique os dígitos informados.';
            modalErro.classList.remove('hidden');
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
            btnSalvar.textContent = 'Salvar e Selecionar para o Contrato';

            if (data.error) {
                modalErro.textContent = data.error;
                modalErro.classList.remove('hidden');
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

            renderizarTabelaResponsaveis();
        })
        .catch(err => {
            btnSalvar.disabled = false;
            btnSalvar.textContent = 'Salvar e Selecionar para o Contrato';
            modalErro.textContent = 'Erro ao processar: ' + err.message;
            modalErro.classList.remove('hidden');
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
    });

    // Caso volte de erro com old('aluno_id')
    @if(old('aluno_id'))
        carregarDetalhesAluno({{ old('aluno_id') }});
    @endif
});
</script>
@endpush
@endsection
