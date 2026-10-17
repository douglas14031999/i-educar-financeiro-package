@extends('financeiro::layouts.app')

@section('title', 'Mensalidades e Títulos')
@section('breadcrumb', 'Mensalidades e Títulos')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="titulos-container">
        
        @php
            $breadcrumbs = [['label' => 'Títulos', 'url' => route('financeiro.titulos.index')]];
            if ($responsavelFiltro) {
                $breadcrumbs[] = ['label' => 'Responsável: ' . $responsavelFiltro->nome];
            } elseif ($contratoFiltro) {
                $breadcrumbs[] = ['label' => 'Contrato #' . $contratoFiltro->numero_contrato];
            }
            $actionsHtml = '<div class="flex flex-wrap items-center gap-2">';

            // Botão Baixa de Balcão (sempre presente)
            $actionsHtml .= '
                <a href="' . route('financeiro.baixas.create') . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Baixar pagamento em dinheiro, cartão ou cheque na secretaria">
                    <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Baixa de Balcão (Caixa)
                </a>
                <a href="' . route('financeiro.renegociacao.index') . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;" title="Renegociar parcelas em atraso e firmar termo de confissão de dívida">
                    <svg aria-hidden="true" class="w-4 h-4 text-amber-600" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Renegociar Dívidas
                </a>';

            if ($contratoFiltro) {
                $forma = $contratoFiltro->forma_pagamento ?? '';
                $primeiroTituloContrato = $contratoFiltro->titulos->first();
                $cobrancaContrato = $primeiroTituloContrato?->cobrancaGateway;

                // Opções conforme a forma de pagamento do contrato filtrado
                if ($forma === 'carne') {
                    $actionsHtml .= '
                        <a href="' . route('financeiro.contratos.imprimir-carne', $contratoFiltro->id) . '" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Imprimir carnê oficial com todas as lâminas escolares">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Imprimir Carnê
                        </a>
                        <a href="' . route('financeiro.contratos.checkout', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;" title="Ver resumo do carnê e cobranças">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Ver Checkout / Pagamento
                        </a>';
                } elseif ($forma === 'pix') {
                    $actionsHtml .= '
                        <a href="' . route('financeiro.contratos.checkout', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Ver QR Code Pix e Chave Copia e Cola">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Ver Checkout / Pix
                        </a>';
                } elseif ($forma === 'boleto') {
                    if ($cobrancaContrato?->link_boleto_pdf) {
                        $actionsHtml .= '
                            <a href="' . $cobrancaContrato->link_boleto_pdf . '" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Baixar Boleto Bancário (PDF)">
                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                </svg>
                                Baixar Boleto (PDF)
                            </a>';
                    }
                    $actionsHtml .= '
                        <a href="' . route('financeiro.contratos.checkout', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;" title="Ver Boleto Bancário e Linha Digitável">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Ver Checkout / Boleto
                        </a>';
                } elseif ($forma === 'cartao_credito') {
                    $actionsHtml .= '
                        <a href="' . route('financeiro.contratos.checkout', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Acessar link para pagamento com Cartão">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Ver Checkout / Cartão
                        </a>';
                } elseif (in_array($forma, ['cartao_presencial', 'dinheiro'])) {
                    $actionsHtml .= '
                        <a href="' . route('financeiro.contratos.checkout', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-green" style="text-decoration: none;" title="Ver detalhes do pagamento e comprovante">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Comprovante / Detalhes
                        </a>';
                }

                // Botão para voltar / ver o contrato
                $actionsHtml .= '
                    <a href="' . route('financeiro.contratos.show', $contratoFiltro->id) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;" title="Ver detalhes do contrato #' . $contratoFiltro->numero_contrato . '">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Ver Contrato
                    </a>';
            } else {
                // Visualização geral sem filtro de contrato: opção de novo contrato
                $actionsHtml .= '
                    <a href="' . route('financeiro.contratos.create', array_filter(['responsavel_id' => $responsavelId])) . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;" title="Matricular e gerar parcelas">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Novo Contrato
                    </a>';
            }

            $actionsHtml .= '</div>';
        @endphp

        @include('financeiro::partials.page-header', [
            'title' => 'Títulos e Mensalidades Escolares',
            'subtitle' => $responsavelFiltro 
                ? 'Exibindo títulos do responsável ' . $responsavelFiltro->nome . ' (CPF: ' . $responsavelFiltro->cpf_cnpj_formatado . ')'
                : ($contratoFiltro 
                    ? 'Exibindo títulos do Contrato #' . $contratoFiltro->numero_contrato . ' (Ano Letivo: ' . $contratoFiltro->ano_letivo . ')'
                    : 'Gerenciamento operacional de cobranças, vencimentos, liquidações e taxas escolares'),
            'breadcrumbs' => $breadcrumbs,
            'actions' => $actionsHtml
        ])

        <!-- Barra de Filtros e Busca -->
        <section class="bg-white shadow-xs border border-slate-200 p-4" data-purpose="filter-section">
            <form id="form-filtros-titulos" method="get" action="{{ route('financeiro.titulos.index') }}" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px;">
                @if($responsavelId)
                    <input type="hidden" name="responsavel_id" value="{{ $responsavelId }}">
                @endif
                @if($contratoId)
                    <input type="hidden" name="contrato_id" value="{{ $contratoId }}">
                @endif
                @if($alunoId)
                    <input type="hidden" name="aluno_id" value="{{ $alunoId }}">
                @endif

                <div class="flex-1 relative" style="min-width: 260px;">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Buscar:
                    </label>
                    <div class="relative">
                        <input type="text" id="filtro-busca" name="busca" value="{{ $busca }}" placeholder="Buscar por Aluno, Responsável, CPF ou Contrato..." class="w-full pl-3 pr-9 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition" autocomplete="off">
                        <!-- Spinner sutil durante digitação / requisição AJAX -->
                        <div id="filtro-spinner-busca" class="absolute right-2.5 top-1/2 -translate-y-1/2 hidden text-[#47728f]">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div style="min-width: 250px;">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Status:
                    </label>
                    <select id="filtro-status" name="status" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                        <option value="">Todos os Ativos (Abertos, Atrasados e Pagos)</option>
                        <option value="aberto" {{ $status === 'aberto' ? 'selected' : '' }}>Abertos / A Vencer</option>
                        <option value="vencido" {{ $status === 'vencido' ? 'selected' : '' }}>Em Atraso (Inadimplentes)</option>
                        <option value="pago" {{ $status === 'pago' ? 'selected' : '' }}>Pagos / Liquidados</option>
                        <option value="parcial" {{ $status === 'parcial' ? 'selected' : '' }}>Parciais</option>
                        <option value="renegociado" {{ $status === 'renegociado' ? 'selected' : '' }}>Histórico de Renegociados</option>
                        <option value="todos" {{ $status === 'todos' ? 'selected' : '' }}>Todos (Inclusive Renegociados)</option>
                        <option value="cancelado" {{ $status === 'cancelado' ? 'selected' : '' }}>Cancelados</option>
                    </select>
                </div>

                <div style="width: 110px;">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                        Ano Letivo:
                    </label>
                    <input type="number" id="filtro-ano" name="ano" value="{{ $ano }}" placeholder="{{ date('Y') }}" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Filtrar
                    </button>
                    <a href="{{ route('financeiro.titulos.index') }}" id="btn-limpar-filtros" class="px-3 py-2 btn-ieducar-action btn-ieducar-outline {{ (!$busca && !$status && !$ano && !$responsavelId && !$contratoId && !$alunoId) ? 'hidden' : '' }}" style="text-decoration: none;">
                        Limpar Filtros
                    </a>
                </div>
            </form>

            @if($responsavelFiltro || $contratoFiltro || $status || $ano || $alunoId || $busca)
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">Filtros ativos:</span>
                    @if($responsavelFiltro)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-[#47728f] border border-blue-200 text-xs font-medium">
                            Responsável: {{ $responsavelFiltro->nome }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('responsavel_id')) }}" class="text-[#47728f] hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($contratoFiltro)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-[#47728f] border border-blue-200 text-xs font-medium">
                            Contrato #{{ $contratoFiltro->numero_contrato }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('contrato_id')) }}" class="text-[#47728f] hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($alunoId)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-[#47728f] border border-blue-200 text-xs font-medium">
                            Aluno: {{ \iEducar\Financeiro\Support\AlunoLookup::nomeComCpf($alunoId) }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('aluno_id')) }}" class="text-[#47728f] hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($ano)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium">
                            Ano: {{ $ano }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('ano')) }}" class="text-slate-500 hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($status)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium">
                            Status: {{ ucfirst($status) }}
                            <a href="{{ route('financeiro.titulos.index', request()->except('status')) }}" class="text-slate-500 hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                    @if($busca)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 text-xs font-medium">
                            Busca: "{{ $busca }}"
                            <a href="{{ route('financeiro.titulos.index', request()->except('busca')) }}" class="text-slate-500 hover:text-red-600 font-bold ml-1" style="text-decoration: none;" title="Remover filtro">×</a>
                        </span>
                    @endif
                </div>
            @endif
        </section>

        <!-- Tabela de Listagem -->
        <section id="container-tabela-titulos" class="bg-white shadow-xs border border-slate-200 overflow-hidden relative" data-purpose="table-container">
            <!-- Conteúdo Dinâmico (Tabela + Paginação) -->
            <div id="tabela-conteudo-dinamico">
                @include('financeiro::titulos.partials.tabela-container')
            </div>
        </section>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Referências do Formulário e Filtros
    const formFiltros = document.getElementById('form-filtros-titulos');
    const inputBusca = document.getElementById('filtro-busca');
    const selectStatus = document.getElementById('filtro-status');
    const inputAno = document.getElementById('filtro-ano');
    const btnLimpar = document.getElementById('btn-limpar-filtros');
    const spinnerBusca = document.getElementById('filtro-spinner-busca');
    const tabelaDinamica = document.getElementById('tabela-conteudo-dinamico');
    const containerTabela = document.getElementById('container-tabela-titulos');

    let abortController = null;
    let debounceTimer = null;

    // --- REQUISIÇÃO AJAX REATIVA ---
    function aplicarFiltroAjax(urlDestino) {
        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        let urlObj;
        if (urlDestino) {
            urlObj = new URL(urlDestino, window.location.origin);
        } else {
            urlObj = new URL("{{ route('financeiro.titulos.index') }}", window.location.origin);
            const formData = new FormData(formFiltros);
            for (const [key, value] of formData.entries()) {
                if (value !== '') {
                    urlObj.searchParams.set(key, value);
                }
            }
        }

        // Adiciona header/param de requisição AJAX
        urlObj.searchParams.set('ajax', '1');

        // Exibe spinner discreto no campo de busca
        if (spinnerBusca) spinnerBusca.classList.remove('hidden');

        fetch(urlObj.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Partial': '1',
                'Accept': 'text/html, application/xhtml+xml'
            },
            signal: abortController.signal
        })
        .then(response => {
            if (!response.ok) throw new Error('Erro na resposta do servidor (' + response.status + ')');
            return response.text();
        })
        .then(html => {
            tabelaDinamica.innerHTML = html;

            // Atualiza URL no navegador para refletir filtros atuais sem reload
            urlObj.searchParams.delete('ajax');
            window.history.replaceState({}, '', urlObj.toString());

            // Alterna visibilidade do botão Limpar Filtros
            const temFiltroAtivo = (inputBusca && inputBusca.value.trim() !== '') || 
                                   (selectStatus && selectStatus.value !== '') || 
                                   (inputAno && inputAno.value.trim() !== '');
            if (btnLimpar) {
                if (temFiltroAtivo) {
                    btnLimpar.classList.remove('hidden');
                } else {
                    btnLimpar.classList.add('hidden');
                }
            }
        })
        .catch(err => {
            if (err.name !== 'AbortError') {
                console.error('Erro ao filtrar títulos:', err);
            }
        })
        .finally(() => {
            if (spinnerBusca) spinnerBusca.classList.add('hidden');
        });
    }

    // Interceptar Paginação via AJAX
    if (containerTabela) {
        containerTabela.addEventListener('click', function (e) {
            const linkPaginacao = e.target.closest('.pagination a');
            if (linkPaginacao && linkPaginacao.href) {
                e.preventDefault();
                aplicarFiltroAjax(linkPaginacao.href);
            }
        });
    }

    // Filtro instantâneo com debounce ao digitar na busca
    if (inputBusca) {
        inputBusca.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                aplicarFiltroAjax();
            }, 280);
        });
    }

    // Filtro instantâneo em selects e outros inputs
    if (selectStatus) {
        selectStatus.addEventListener('change', () => aplicarFiltroAjax());
    }
    if (inputAno) {
        inputAno.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                aplicarFiltroAjax();
            }, 350);
        });
    }

    // Evita reload tradicional ao submeter formulário
    if (formFiltros) {
        formFiltros.addEventListener('submit', function (e) {
            e.preventDefault();
            clearTimeout(debounceTimer);
            aplicarFiltroAjax();
        });
    }

    // Botão Limpar Filtros com AJAX
    if (btnLimpar) {
        btnLimpar.addEventListener('click', function (e) {
            e.preventDefault();
            if (inputBusca) inputBusca.value = '';
            if (selectStatus) selectStatus.value = '';
            if (inputAno) inputAno.value = '';
            aplicarFiltroAjax("{{ route('financeiro.titulos.index') }}");
        });
    }
});
</script>
@endsection
