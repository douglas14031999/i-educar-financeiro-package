@extends('financeiro::layouts.app')

@section('title', 'Nova Conta a Pagar')
@section('breadcrumb', 'Contas a Pagar > Novo')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="contas-pagar-create-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Cadastrar Nova Conta a Pagar
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Registro de despesas operacionais, fornecedores e obrigações escolares
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('financeiro.contas-pagar.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium border border-slate-300 shadow-2xs transition" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar para Despesas
                </a>
            </div>
        </header>

        <!-- Card de Formulário -->
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <div class="bg-gradient-to-r from-[#214b5f] to-[#1a3d4e] px-5 py-3.5 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Dados da Despesa / Obrigação
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    * Campos obrigatórios
                </span>
            </div>

            <form id="formcadastro" method="post" action="{{ route('financeiro.contas-pagar.store') }}" class="p-6 space-y-6">
                @csrf

                <!-- Seção 1: Identificação da Despesa -->
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        1. Dados da Conta e Valores
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label for="descricao" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Descrição da Despesa <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="descricao" id="descricao" value="{{ old('descricao') }}" placeholder="Ex: Energia Elétrica - Prédio Ensino Fundamental" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="valor" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Valor da Conta (R$) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" name="valor" id="valor" value="{{ old('valor') }}" placeholder="0,00" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono font-bold focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="fornecedor_nome" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Fornecedor / Favorecido
                            </label>
                            <input type="text" name="fornecedor_nome" id="fornecedor_nome" value="{{ old('fornecedor_nome') }}" placeholder="Ex: Companhia de Energia, Papelaria..." class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="data_competencia" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Data Competência <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="data_competencia" id="data_competencia" value="{{ old('data_competencia', date('Y-m-01')) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="data_vencimento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Data de Vencimento <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="data_vencimento" id="data_vencimento" value="{{ old('data_vencimento', date('Y-m-d')) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>
                    </div>
                </div>

                <!-- Seção 2: Classificação Contábil -->
                <div class="pt-2">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        2. Classificação Contábil e Documentação
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="plano_conta_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Plano de Contas / Categoria <span class="text-red-500">*</span>
                            </label>
                            <select name="plano_conta_id" id="plano_conta_id" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                <option value="">-- Selecione a Categoria --</option>
                                @foreach($planosContas as $pc)
                                    <option value="{{ $pc->id }}" {{ old('plano_conta_id') == $pc->id ? 'selected' : '' }}>
                                        {{ $pc->codigo }} - {{ $pc->descricao }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="centro_custo_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Centro de Custo
                            </label>
                            <select name="centro_custo_id" id="centro_custo_id" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                <option value="">-- Geral / Não Definido --</option>
                                @foreach($centrosCusto as $cc)
                                    <option value="{{ $cc->id }}" {{ old('centro_custo_id') == $cc->id ? 'selected' : '' }}>
                                        {{ $cc->codigo }} - {{ $cc->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="documento_fiscal" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Documento Fiscal (NF / Recibo)
                            </label>
                            <input type="text" name="documento_fiscal" id="documento_fiscal" value="{{ old('documento_fiscal') }}" placeholder="Ex: NF-e 45291" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>
                    </div>
                </div>

                <!-- Seção 3: Observações -->
                <div class="pt-2">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        3. Informações Complementares
                    </h3>
                    <div>
                        <label for="observacoes" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Observações Gerais
                        </label>
                        <textarea name="observacoes" id="observacoes" rows="3" placeholder="Detalhes sobre a compra, contrato de prestação de serviços ou condições..." class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">{{ old('observacoes') }}</textarea>
                    </div>
                </div>

                <!-- Ações do Formulário -->
                <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-end gap-3">
                    <a href="{{ route('financeiro.contas-pagar.index') }}" class="w-full sm:w-auto text-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium border border-slate-300 transition" style="text-decoration: none;">
                        Cancelar
                    </a>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm hover:shadow transition duration-150">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Salvar Conta a Pagar
                    </button>
                </div>

            </form>
        </div>

    </main>
</div>
@endsection
