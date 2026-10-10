@extends('financeiro::layouts.app')

@section('title', 'Novo Responsável Financeiro')
@section('breadcrumb', 'Cadastrar Responsável')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="responsaveis-create-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Novo Responsável Financeiro
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Cadastre o responsável legal para emissão de cobranças, contratos e boletos escolares
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('financeiro.responsaveis.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium border border-slate-300 shadow-2xs transition" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar para Responsáveis
                </a>
            </div>
        </header>

        <!-- Card de Formulário -->
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3.5 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Ficha de Cadastro do Responsável
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    * Campos obrigatórios
                </span>
            </div>

            <form id="formcadastro" method="post" action="{{ route('financeiro.responsaveis.store') }}" class="p-6 space-y-6">
                @csrf

                <!-- Seção 1: Dados Pessoais -->
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        1. Dados Pessoais e Identificação
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label for="nome" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Nome Completo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nome" id="nome" value="{{ old('nome') }}" required placeholder="Ex: Maria Aparecida da Silva" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="cpf_cnpj" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                CPF ou CNPJ <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="cpf_cnpj" id="cpf_cnpj" value="{{ old('cpf_cnpj') }}" required placeholder="000.000.000-00" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="rg_ie" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                RG / Inscrição Estadual
                            </label>
                            <input type="text" name="rg_ie" id="rg_ie" value="{{ old('rg_ie') }}" placeholder="Ex: 12.345.678-9" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                E-mail Principal <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="responsavel@email.com" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">Utilizado para envio de boletos e notificações automáticas</p>
                        </div>

                        <div>
                            <label for="telefone_celular" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Celular / WhatsApp <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="telefone_celular" id="telefone_celular" value="{{ old('telefone_celular') }}" required placeholder="(00) 00000-0000" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">Contato prioritário para régua de cobrança</p>
                        </div>
                    </div>
                </div>

                <!-- Seção 2: Endereço -->
                <div class="pt-2">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        2. Endereço e Localização
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="cep" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                CEP <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="cep" id="cep" value="{{ old('cep') }}" required placeholder="00000-000" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div class="md:col-span-2">
                            <label for="logradouro" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Logradouro / Rua <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="logradouro" id="logradouro" value="{{ old('logradouro') }}" required placeholder="Ex: Rua das Flores" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="numero" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Número <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="numero" id="numero" value="{{ old('numero') }}" required placeholder="123" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="complemento" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Complemento
                            </label>
                            <input type="text" name="complemento" id="complemento" value="{{ old('complemento') }}" placeholder="Apto 101, Bloco B" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="bairro" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Bairro <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="bairro" id="bairro" value="{{ old('bairro') }}" required placeholder="Centro" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="cidade" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Cidade <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="cidade" id="cidade" value="{{ old('cidade') }}" required placeholder="Nome da cidade" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="estado" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                UF / Estado <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="estado" id="estado" value="{{ old('estado') }}" required placeholder="AL" maxlength="2" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm uppercase font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
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
                        <textarea name="observacoes" id="observacoes" rows="3" placeholder="Informações adicionais, histórico ou observações sobre o responsável..." class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">{{ old('observacoes') }}</textarea>
                    </div>
                </div>

                <!-- Ações do Formulário -->
                <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-end gap-3">
                    <a href="{{ route('financeiro.responsaveis.index') }}" class="w-full sm:w-auto text-center px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium border border-slate-300 transition" style="text-decoration: none;">
                        Cancelar
                    </a>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm hover:shadow transition duration-150">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Salvar Responsável
                    </button>
                </div>

            </form>
        </div>

    </main>
</div>
@endsection
