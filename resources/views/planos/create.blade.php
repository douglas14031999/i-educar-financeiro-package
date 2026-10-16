@extends('financeiro::layouts.app')

@section('title', 'Novo Plano de Cobrança')
@section('breadcrumb', 'Cadastrar Plano de Cobrança')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="planos-create-container">
        
        @include('financeiro::partials.page-header', [
            'title' => 'Novo Plano de Cobrança Escolar',
            'subtitle' => 'Cadastre regras de anuidade, parcelas mensais e parâmetros de vencimento para as matrículas',
            'breadcrumbs' => [
                ['label' => 'Planos de Cobrança', 'url' => route('financeiro.planos.index')],
                ['label' => 'Novo Plano']
            ],
            'actions' => '
                <a href="' . route('financeiro.planos.index') . '" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar para Planos
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
                        Configuração da Anuidade e Parcelas
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    * Campos obrigatórios
                </span>
            </div>

            <form id="formcadastro" method="post" action="{{ route('financeiro.planos.store') }}" class="p-6 space-y-6">
                @csrf

                <!-- Seção 1: Identificação do Plano -->
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        1. Dados Gerais do Plano
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <!-- 1. ESCOLA VINCULADA (Vem primeiro) -->
                        <div class="md:col-span-8">
                            <label for="escola_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Escola Vinculada <span class="text-red-500">*</span>
                            </label>
                            @if(isset($escolas) && count($escolas) > 0)
                                <select name="escola_id" id="escola_id" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                    @foreach($escolas as $esc)
                                        @php
                                            $escId = is_object($esc) ? ($esc->id ?? $esc->cod_escola ?? 1) : (is_array($esc) ? ($esc['id'] ?? $esc['cod_escola'] ?? 1) : $loop->iteration);
                                            $escNome = is_object($esc) ? ($esc->nome ?? 'Escola ' . $escId) : (is_array($esc) ? ($esc['nome'] ?? 'Escola ' . $escId) : $esc);
                                        @endphp
                                        <option value="{{ $escId }}" {{ old('escola_id', $plano->escola_id ?? 1) == $escId ? 'selected' : '' }}>{{ $escNome }} (ID: {{ $escId }})</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="number" name="escola_id" id="escola_id" value="{{ old('escola_id', 1) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            @endif
                        </div>

                        <!-- 2. ANO LETIVO -->
                        <div class="md:col-span-4">
                            <label for="ano_letivo" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Ano Letivo <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="ano_letivo" id="ano_letivo" value="{{ old('ano_letivo', date('Y')) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        </div>

                        <!-- 3. NOME DO PLANO (DROPDOWN DE CURSOS VINCULADOS À ESCOLA E ANO LETIVO) -->
                        <div class="md:col-span-12">
                            <label for="curso_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1 flex items-center justify-between">
                                <span>Nome do Plano / Curso Vinculado <span class="text-red-500">*</span></span>
                                <span id="loading_cursos" class="text-[11px] text-[#47728f] font-normal" style="display: none;">
                                    <svg class="animate-spin inline w-3 h-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    Carregando cursos...
                                </span>
                            </label>
                            
                            <select name="curso_id" id="curso_id" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                                <option value="">-- Selecione o Curso para o Plano --</option>
                                @if(isset($cursosIniciais) && count($cursosIniciais) > 0)
                                    @foreach($cursosIniciais as $cur)
                                        @php
                                            $curId = is_object($cur) ? $cur->id : $cur['id'];
                                            $curNome = is_object($cur) ? $cur->nome : $cur['nome'];
                                        @endphp
                                        <option value="{{ $curId }}" data-nome="{{ $curNome }}" {{ old('curso_id') == $curId ? 'selected' : '' }}>
                                            {{ $curNome }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>

                            <!-- Campo oculto com o nome do plano (sincronizado automaticamente) -->
                            <input type="hidden" name="nome" id="nome_plano_hidden" value="{{ old('nome') }}">

                            <p class="text-[11px] text-slate-400 mt-1">
                                Cursos vinculados à escola selecionada para o ano letivo. O nome do plano adotará o nome do curso escolhido.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Seção 2: Valores e Condições -->
                <div class="pt-2">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 pb-1 border-b border-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        2. Condições Comerciais e Valores da Anuidade
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="valor_total" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Valor Total Anual (R$) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="valor_total" id="valor_total" step="0.01" min="1" value="{{ old('valor_total') }}" placeholder="Ex: 9600.00" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">Valor integral da anuidade escolar deste plano.</p>
                        </div>

                        <div>
                            <label for="dia_vencimento_padrao" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Dia do Vencimento Padrão Sugerido <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="dia_vencimento_padrao" id="dia_vencimento_padrao" min="1" max="31" value="{{ old('dia_vencimento_padrao', 10) }}" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <p class="text-[11px] text-slate-400 mt-1">Dia do mês sugerido para os boletos/carnês (ex: 10).</p>
                        </div>

                        <div class="md:col-span-2 p-3 bg-blue-50 border border-blue-200 text-xs text-[#47728f] rounded-sm flex items-center gap-2 font-medium">
                            <svg class="w-4 h-4 shrink-0" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            <span>A quantidade de parcelas (de 1x até 12x) e o mês de início são escolhidos pelo responsável no momento da emissão de cada contrato.</span>
                        </div>
                    </div>

                    <!-- Taxa de Matrícula Avulsa -->
                    <div class="mt-4 p-4 bg-slate-50 border border-slate-200">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-800">
                            <input type="checkbox" name="gerar_matricula_avulsa" value="1" {{ old('gerar_matricula_avulsa') ? 'checked' : '' }} class="w-4 h-4 text-brand-petroleum rounded border-slate-300 focus:ring-brand-petroleum">
                            Gerar Parcela 00 de Taxa de Matrícula (à parte da anuidade regular)
                        </label>
                        <div class="mt-3 max-w-xs">
                            <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">
                                Valor da Taxa de Matrícula (R$)
                            </label>
                            <input type="number" name="valor_matricula" step="0.01" min="0" value="{{ old('valor_matricula') }}" placeholder="Ex: 500.00" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
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
                        <textarea name="descricao" id="descricao" rows="3" placeholder="Informações adicionais sobre o plano, público-alvo ou condições específicas..." class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">{{ old('descricao') }}</textarea>
                    </div>
                </div>

                <!-- Ações do Formulário -->
                <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-end gap-3">
                    <a href="{{ route('financeiro.planos.index') }}" class="w-full sm:w-auto text-center px-4 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                        Cancelar
                    </a>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 btn-ieducar-action btn-ieducar-green">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Gerar Plano e Itens
                    </button>
                </div>

            </form>
        </div>

    </main>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var escolaSelect = document.getElementById('escola_id');
    var anoLetivoInput = document.getElementById('ano_letivo');
    var cursoSelect = document.getElementById('curso_id');
    var nomePlanoHidden = document.getElementById('nome_plano_hidden');
    var loadingSpan = document.getElementById('loading_cursos');

    function sincronizarNomePlano() {
        if (!cursoSelect || !nomePlanoHidden) return;
        var opt = cursoSelect.options[cursoSelect.selectedIndex];
        if (opt && opt.value) {
            nomePlanoHidden.value = opt.getAttribute('data-nome') || opt.text.trim();
        } else {
            nomePlanoHidden.value = '';
        }
    }

    if (cursoSelect) {
        cursoSelect.addEventListener('change', sincronizarNomePlano);
        sincronizarNomePlano();
    }

    var debounceTimer = null;

    function carregarCursos() {
        if (!escolaSelect || !cursoSelect) return;
        var escolaId = escolaSelect.value;
        var anoLetivo = anoLetivoInput ? anoLetivoInput.value : new Date().getFullYear();

        if (!escolaId) {
            cursoSelect.innerHTML = '<option value="">-- Selecione uma escola primeiro --</option>';
            sincronizarNomePlano();
            return;
        }

        if (loadingSpan) loadingSpan.style.display = 'inline';

        var url = '{{ route("financeiro.api.escolas.cursos", ["escola_id" => ":escola_id"]) }}'
            .replace(':escola_id', encodeURIComponent(escolaId)) + '?ano_letivo=' + encodeURIComponent(anoLetivo);

        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function(res) {
                if (!res.ok) throw new Error('Erro HTTP ' + res.status);
                return res.json();
            })
            .then(function(cursos) {
                cursoSelect.innerHTML = '';
                if (!cursos || cursos.length === 0) {
                    var opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = '-- Nenhum curso vinculado a esta escola neste ano --';
                    cursoSelect.appendChild(opt);
                } else {
                    var optDefault = document.createElement('option');
                    optDefault.value = '';
                    optDefault.textContent = '-- Selecione o Curso para o Plano (' + cursos.length + ' cursos encontrados) --';
                    cursoSelect.appendChild(optDefault);

                    cursos.forEach(function(cur) {
                        var opt = document.createElement('option');
                        opt.value = cur.id;
                        opt.setAttribute('data-nome', cur.nome);
                        opt.textContent = cur.nome;
                        cursoSelect.appendChild(opt);
                    });

                    if (cursos.length === 1) {
                        cursoSelect.selectedIndex = 1;
                    }
                }
                sincronizarNomePlano();
            })
            .catch(function(err) {
                console.error('[Financeiro] Erro ao carregar cursos:', err);
            })
            .finally(function() {
                if (loadingSpan) loadingSpan.style.display = 'none';
            });
    }

    if (escolaSelect) {
        escolaSelect.addEventListener('change', carregarCursos);
    }

    if (anoLetivoInput) {
        anoLetivoInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(carregarCursos, 400);
        });
    }
});
</script>
@endpush
