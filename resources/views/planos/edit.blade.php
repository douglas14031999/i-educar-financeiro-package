@extends('financeiro::layouts.app')

@section('title', 'Editar Plano de Cobrança')
@section('breadcrumb', 'Editar Plano de Cobrança')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="planos-edit-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Editar Plano de Cobrança Escolar',
            'subtitle' => 'Atualize as condições comerciais, anuidade e regras do plano #' . $plano->id,
            'breadcrumbs' => [
                ['label' => 'Planos de Cobrança', 'url' => route('financeiro.planos.index')],
                ['label' => $plano->nome, 'url' => route('financeiro.planos.show', $plano->id)],
                ['label' => 'Editar']
            ],
            'actions' => '
                <a href="' . route('financeiro.planos.show', $plano->id) . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar para Detalhes
                </a>
            '
        ])

        <!-- Card de Formulário -->
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3.5 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Alteração da Anuidade e Parcelas
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    * Campos obrigatórios
                </span>
            </div>

            <form id="formcadastro" method="post" action="{{ route('financeiro.planos.update', $plano->id) }}" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                @php
                    $itemMatricula = $plano->itens->firstWhere('tipo_item', 'matricula');
                    $temMatricula = !is_null($itemMatricula);
                    $valorMatricula = $itemMatricula ? $itemMatricula->valor : 0.00;
                    $valorMensalidades = $plano->valor_total - $valorMatricula;
                    $diaPadrao = $plano->itens->firstWhere('tipo_item', 'mensalidade')?->dia_vencimento_padrao ?? 10;
                @endphp

                <!-- Seção 1: Identificação do Plano -->
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        1. Dados Gerais do Plano
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label for="nome" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Nome do Plano <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nome" id="nome" value="{{ old('nome', $plano->nome) }}" required placeholder="Ex: Ensino Fundamental I - Anual 2026 em 12x" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="ano_letivo" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Ano Letivo <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="ano_letivo" id="ano_letivo" value="{{ old('ano_letivo', $plano->ano_letivo) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <div>
                            <label for="escola_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Escola <span class="text-red-500">*</span>
                            </label>
                            @if(isset($escolas) && $escolas->isNotEmpty())
                                <select name="escola_id" id="escola_id" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                    @foreach($escolas as $esc)
                                        <option value="{{ $esc->id }}" {{ old('escola_id', $plano->escola_id) == $esc->id ? 'selected' : '' }}>{{ $esc->nome }} (ID: {{ $esc->id }})</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="number" name="escola_id" id="escola_id" value="{{ old('escola_id', $plano->escola_id ?? 1) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Seção 2: Valores e Parcelas -->
                <div class="pt-2">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        2. Condições Comerciais e Parcelamento
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="valor_total" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Valor Total Anual (R$) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="valor_total" id="valor_total" step="0.01" min="1" value="{{ old('valor_total', $valorMensalidades) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">Valor integral da anuidade que será dividido nas parcelas</p>
                        </div>

                        <div>
                            <label for="qtd_parcelas" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Quantidade de Parcelas <span class="text-red-500">*</span>
                            </label>
                            <select name="qtd_parcelas" id="qtd_parcelas" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                <option value="12" {{ old('qtd_parcelas', $plano->qtd_parcelas) == 12 ? 'selected' : '' }}>12 parcelas (Padrão anual)</option>
                                <option value="11" {{ old('qtd_parcelas', $plano->qtd_parcelas) == 11 ? 'selected' : '' }}>11 parcelas</option>
                                <option value="10" {{ old('qtd_parcelas', $plano->qtd_parcelas) == 10 ? 'selected' : '' }}>10 parcelas</option>
                                <option value="6" {{ old('qtd_parcelas', $plano->qtd_parcelas) == 6 ? 'selected' : '' }}>6 parcelas (Semestral)</option>
                                <option value="1" {{ old('qtd_parcelas', $plano->qtd_parcelas) == 1 ? 'selected' : '' }}>1 parcela (À vista)</option>
                            </select>
                        </div>

                        <div>
                            <label for="dia_vencimento_padrao" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Dia do Vencimento Padrão <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="dia_vencimento_padrao" id="dia_vencimento_padrao" min="1" max="31" value="{{ old('dia_vencimento_padrao', $diaPadrao) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">Dia do mês sugerido para os boletos/Pix (ex: 10)</p>
                        </div>
                    </div>

                    <!-- Taxa de Matrícula Avulsa -->
                    <div class="mt-4 p-4 bg-slate-50 border border-slate-200">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-800">
                            <input type="checkbox" name="gerar_matricula_avulsa" value="1" {{ old('gerar_matricula_avulsa', $temMatricula) ? 'checked' : '' }} class="w-4 h-4 text-brand-petroleum rounded border-slate-300 focus:ring-brand-petroleum">
                            Gerar Parcela 00 de Taxa de Matrícula (à parte da anuidade regular)
                        </label>
                        <div class="mt-3 max-w-xs">
                            <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">
                                Valor da Taxa de Matrícula (R$)
                            </label>
                            <input type="number" name="valor_matricula" step="0.01" min="0" value="{{ old('valor_matricula', $valorMatricula) }}" placeholder="Ex: 500.00" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>
                    </div>
                </div>

                <!-- Seção 3: Descrição -->
                <div class="pt-2">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        3. Informações Complementares
                    </h3>
                    <div>
                        <label for="descricao" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Descrição / Observações Internas
                        </label>
                        <textarea name="descricao" id="descricao" rows="3" placeholder="Informações adicionais sobre o plano, público-alvo ou condições específicas..." class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">{{ old('descricao', $plano->descricao) }}</textarea>
                    </div>
                </div>

                <!-- Ações do Formulário -->
                <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-end gap-3">
                    <a href="{{ route('financeiro.planos.show', $plano->id) }}" class="w-full sm:w-auto text-center px-4 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                        Cancelar
                    </a>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 btn-ieducar-action btn-ieducar-green">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Salvar Alterações
                    </button>
                </div>

            </form>
        </div>

    </main>
</div>
@endsection
