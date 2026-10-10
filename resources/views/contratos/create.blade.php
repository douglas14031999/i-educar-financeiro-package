@extends('financeiro::layouts.app')

@section('title', 'Novo Contrato Financeiro')
@section('breadcrumb', 'Cadastrar Contrato')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="contrato-create-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Novo Contrato Financeiro Escolar
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Vincule a matrícula do aluno a um plano de pagamento e gere a grade de títulos e mensalidades
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('financeiro.contratos.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar para Contratos
                </a>
            </div>
        </header>

        <!-- Formulário de Cadastro em Card Moderno -->
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <!-- Topo do Card com Gradiente Petróleo -->
            <div class="bg-[#47728f] px-5 py-3.5 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Dados da Matrícula e Parâmetros Financeiros
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    * Campos obrigatórios
                </span>
            </div>

            <form id="formcadastro" method="post" action="{{ route('financeiro.contratos.store') }}" class="p-6 space-y-6">
                @csrf

                <!-- Seção 1: Identificação Escolar -->
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        1. Identificação da Matrícula e Aluno
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="matricula_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Código Matrícula (i-Educar) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="matricula_id" id="matricula_id" value="{{ old('matricula_id') }}" required placeholder="Ex: 10420" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">ID numérico da matrícula no sistema acadêmico</p>
                        </div>

                        <div>
                            <label for="aluno_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Código do Aluno (i-Educar) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="aluno_id" id="aluno_id" value="{{ old('aluno_id') }}" required placeholder="Ex: 508" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">ID da pessoa/aluno correspondente</p>
                        </div>

                        <div>
                            <label for="ano_letivo" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Ano Letivo <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="ano_letivo" id="ano_letivo" value="{{ old('ano_letivo', $anoAtual) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">Ano de exercício das cobranças</p>
                        </div>
                    </div>
                </div>

                <!-- Seção 2: Plano e Responsável -->
                <div class="pt-2">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        2. Plano de Pagamento e Responsável Financeiro
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="plano_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Plano de Cobrança / Anuidade <span class="text-red-500">*</span>
                            </label>
                            <select name="plano_id" id="plano_id" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                <option value="">Selecione o plano configurado...</option>
                                @foreach($planos as $plano)
                                    <option value="{{ $plano->id }}" {{ old('plano_id') == $plano->id ? 'selected' : '' }}>
                                        {{ $plano->nome }} — R$ {{ number_format($plano->valor_total, 2, ',', '.') }} ({{ $plano->qtd_parcelas }}x)
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Define valor integral, número de parcelas e regras de vencimento</p>
                        </div>

                        <div>
                            <label for="responsavel_financeiro_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Responsável Financeiro Principal
                            </label>
                            <select name="responsavel_financeiro_id" id="responsavel_financeiro_id" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                <option value="">Utilizar responsáveis vinculados ao aluno (com rateio automático)</option>
                                @foreach($responsaveis as $resp)
                                    <option value="{{ $resp->id }}" {{ old('responsavel_financeiro_id') == $resp->id ? 'selected' : '' }}>
                                        {{ $resp->nome }} (CPF: {{ $resp->cpf_cnpj_formatado }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Se houver rateio percentual configurado (ex: 50%/50%), os boletos serão divididos proporcionalmente</p>
                        </div>
                    </div>
                </div>

                <!-- Seção 3: Condições de Vencimento e Descontos -->
                <div class="pt-2">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        3. Vencimento e Negociação Comercial
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="dia_vencimento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Dia de Vencimento Mensal <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="dia_vencimento" id="dia_vencimento" min="1" max="31" value="{{ old('dia_vencimento', 10) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">Dia do mês em que cada mensalidade vencerá (ex: 05, 10, 15)</p>
                        </div>

                        <div>
                            <label for="valor_total_descontos" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Desconto Total Concedido (R$)
                            </label>
                            <input type="number" name="valor_total_descontos" id="valor_total_descontos" step="0.01" min="0" value="{{ old('valor_total_descontos', '0.00') }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">Bolsa, convênio ou abatimento negociado no valor global do contrato</p>
                        </div>
                    </div>
                </div>

                <!-- Ações do Formulário -->
                <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-end gap-3">
                    <a href="{{ route('financeiro.contratos.index') }}" class="w-full sm:w-auto text-center px-4 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                        Cancelar
                    </a>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 btn-ieducar-action btn-ieducar-green">
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
@endsection
