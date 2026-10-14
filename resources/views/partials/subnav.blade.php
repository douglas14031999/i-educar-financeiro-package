@php
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    $pathInfo = request()->getPathInfo();

    $isCadastrosActive = request()->routeIs('financeiro.responsaveis*') || request()->routeIs('financeiro.planos*') || request()->routeIs('financeiro.contratos*');
    $isMovimentacoesActive = request()->routeIs('financeiro.titulos*') || request()->routeIs('financeiro.baixas*') || request()->routeIs('financeiro.renegociacao*');
    $isLancamentosActive = request()->routeIs('financeiro.contas-pagar*');
    $isRelatoriosActive = request()->routeIs('financeiro.dashboard*') || request()->routeIs('financeiro.relatorios*');
    $isConfiguracoesActive = request()->routeIs('financeiro.configuracoes*') || request()->routeIs('financeiro.notificacoes*');
@endphp

<!-- Submenu Superior Oficial Padrão Nativo i-Educar -->
<div class="ieducar-menu-container">
    <ul class="ieducar-menu clearfix">
        <!-- 1. Cadastros -->
        <li class="{{ $isCadastrosActive ? 'ieducar-sidebar-menu-active' : '' }}">
            <a class="{{ $isCadastrosActive ? 'ieducar-sidebar-menu-active' : '' }}" href="javascript:void(0);">Cadastros</a>
            <ul class="ieducar-sub-menu clearfix">
                <li>
                    <a class="{{ request()->routeIs('financeiro.responsaveis*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.responsaveis.index') }}">
                        Responsáveis Financeiros
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.planos*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.planos.index') }}">
                        Planos de Mensalidade
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.contratos*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.contratos.index') }}">
                        Contratos por Matrícula
                    </a>
                </li>
            </ul>
        </li>

        <!-- 2. Movimentações -->
        <li class="{{ $isMovimentacoesActive ? 'ieducar-sidebar-menu-active' : '' }}">
            <a class="{{ $isMovimentacoesActive ? 'ieducar-sidebar-menu-active' : '' }}" href="javascript:void(0);">Movimentações</a>
            <ul class="ieducar-sub-menu clearfix">
                <li>
                    <a class="{{ request()->routeIs('financeiro.titulos*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.titulos.index') }}">
                        Mensalidades e Títulos
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.baixas*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.baixas.create') }}">
                        Baixa de Balcão (Caixa)
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.renegociacao*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.renegociacao.index') }}">
                        Renegociação de Dívidas
                    </a>
                </li>
            </ul>
        </li>

        <!-- 3. Lançamentos -->
        <li class="{{ $isLancamentosActive ? 'ieducar-sidebar-menu-active' : '' }}">
            <a class="{{ $isLancamentosActive ? 'ieducar-sidebar-menu-active' : '' }}" href="javascript:void(0);">Lançamentos</a>
            <ul class="ieducar-sub-menu clearfix">
                <li>
                    <a class="{{ request()->routeIs('financeiro.contas-pagar*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.contas-pagar.index') }}">
                        Contas a Pagar (Despesas)
                    </a>
                </li>
            </ul>
        </li>

        <!-- 4. Relatórios -->
        <li class="{{ $isRelatoriosActive ? 'ieducar-sidebar-menu-active' : '' }}">
            <a class="{{ $isRelatoriosActive ? 'ieducar-sidebar-menu-active' : '' }}" href="javascript:void(0);">Relatórios</a>
            <ul class="ieducar-sub-menu clearfix">
                <li>
                    <a class="{{ request()->routeIs('financeiro.dashboard*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.dashboard') }}">
                        Painel Geral
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.relatorios.inadimplencia') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.relatorios.inadimplencia') }}">
                        Inadimplência &amp; Aging
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.relatorios.fluxo-caixa') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.relatorios.fluxo-caixa') }}">
                        Fluxo de Caixa
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.relatorios.dre') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.relatorios.dre') }}">
                        DRE Escolar
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.relatorios.informe-irpf') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.relatorios.informe-irpf') }}">
                        Informe de Rendimentos IRPF
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.relatorios.quitacao-anual') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.relatorios.quitacao-anual') }}">
                        Declaração de Quitação Anual
                    </a>
                </li>
            </ul>
        </li>

        <!-- 5. Documentos -->
        <li class="{{ request()->routeIs('financeiro.titulos.imprimir-carne*') ? 'ieducar-sidebar-menu-active' : '' }}">
            <a class="{{ request()->routeIs('financeiro.titulos.imprimir-carne*') ? 'ieducar-sidebar-menu-active' : '' }}" href="javascript:void(0);">Documentos</a>
            <ul class="ieducar-sub-menu clearfix">
                <li>
                    <a href="{{ route('financeiro.titulos.index') }}?acao=imprimir_carne">
                        Emissão de Carnês Escolares
                    </a>
                </li>
                <li>
                    <a href="{{ route('financeiro.relatorios.quitacao-anual') }}">
                        Declaração de Quitação
                    </a>
                </li>
                <li>
                    <a href="{{ route('financeiro.relatorios.informe-irpf') }}">
                        Comprovante de Pagamentos (IRPF)
                    </a>
                </li>
            </ul>
        </li>

        <!-- 6. Ferramentas -->
        <li class="{{ $isConfiguracoesActive ? 'ieducar-sidebar-menu-active' : '' }}">
            <a class="{{ $isConfiguracoesActive ? 'ieducar-sidebar-menu-active' : '' }}" href="javascript:void(0);">Ferramentas</a>
            <ul class="ieducar-sub-menu clearfix">
                <li>
                    <a class="{{ request()->routeIs('financeiro.configuracoes.gateway*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.configuracoes.gateway') }}">
                        Gateway Asaas (API / Webhooks)
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.configuracoes.regua*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.configuracoes.regua') }}">
                        Régua de Notificações
                    </a>
                </li>
                <li>
                    <a class="{{ request()->routeIs('financeiro.notificacoes*') ? 'ieducar-sidebar-menu-active' : '' }}" href="{{ route('financeiro.notificacoes.index') }}">
                        Histórico de Envios
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</div>
