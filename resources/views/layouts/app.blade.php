@php
    if (!isset($menu)) {
        $user = auth()->user();
        $menuList = collect();
        if ($user && class_exists(\App\Services\MenuCacheService::class)) {
            try {
                $menuList = app(\App\Services\MenuCacheService::class)->getMenuByUser($user);
            } catch (\Throwable $e) {}
        }
        if (empty($menuList) && class_exists(\App\Menu::class)) {
            try {
                $menuList = $user ? \App\Menu::user($user) : \App\Menu::roots();
            } catch (\Throwable $e) {
                $menuList = \App\Menu::roots();
            }
        }
        $menu = $menuList ?? collect();
    }

    if (!isset($root)) {
        if (class_exists(\App\Menu::class)) {
            $rootMenu = \App\Menu::where('process', \iEducar\Financeiro\Constants\ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ)->first()
                ?: \App\Menu::where('title', 'Financeiro')->whereNull('parent_id')->first();
            $root = $rootMenu ? $rootMenu->getKey() : null;
        } else {
            $root = null;
        }
    }

    if (!isset($breadcrumb)) {
        $breadcrumb = null;
        if (class_exists(\iEducar\Support\Navigation\Breadcrumb::class)) {
            try {
                $breadcrumb = app(\iEducar\Support\Navigation\Breadcrumb::class)->currentPage('Módulo Financeiro');
            } catch (\Throwable $e) {}
        }
    }

    if (!isset($title)) {
        $title = 'Módulo Financeiro';
    }
@endphp
@extends(View::exists('layout.default') ? 'layout.default' : 'financeiro::layouts.standalone')

@section('content')
    <div id="financeiro-module" class="open-sans" style="padding: 15px 20px;">
        <!-- Barra de Navegação Rápida do Módulo Financeiro -->
        <nav class="financeiro-subnav-container">
            <div class="financeiro-subnav-scroll">
                <a href="{{ route('financeiro.dashboard') }}" class="financeiro-subnav-item {{ request()->routeIs('financeiro.dashboard') ? 'active' : '' }}">
                    <i class="fa fa-dashboard"></i> Painel Geral
                </a>
                <a href="{{ route('financeiro.responsaveis.index') }}" class="financeiro-subnav-item {{ request()->routeIs('financeiro.responsaveis*') ? 'active' : '' }}">
                    <i class="fa fa-users"></i> Responsáveis
                </a>
                <a href="{{ route('financeiro.planos.index') }}" class="financeiro-subnav-item {{ request()->routeIs('financeiro.planos*') ? 'active' : '' }}">
                    <i class="fa fa-tags"></i> Planos
                </a>
                <a href="{{ route('financeiro.contratos.index') }}" class="financeiro-subnav-item {{ request()->routeIs('financeiro.contratos*') ? 'active' : '' }}">
                    <i class="fa fa-file-text-o"></i> Contratos
                </a>
                <a href="{{ route('financeiro.titulos.index') }}" class="financeiro-subnav-item {{ request()->routeIs('financeiro.titulos*') ? 'active' : '' }}">
                    <i class="fa fa-barcode"></i> Mensalidades & Boletos
                </a>
                <a href="{{ route('financeiro.baixas.create') }}" class="financeiro-subnav-item {{ request()->routeIs('financeiro.baixas*') ? 'active' : '' }}">
                    <i class="fa fa-money"></i> Baixa Caixa
                </a>
                <a href="{{ route('financeiro.renegociacao.index') }}" class="financeiro-subnav-item {{ request()->routeIs('financeiro.renegociacao*') ? 'active' : '' }}">
                    <i class="fa fa-handshake-o"></i> Renegociação
                </a>
                <a href="{{ route('financeiro.contas-pagar.index') }}" class="financeiro-subnav-item {{ request()->routeIs('financeiro.contas-pagar*') ? 'active' : '' }}">
                    <i class="fa fa-credit-card"></i> Contas a Pagar
                </a>
                <a href="{{ route('financeiro.relatorios.index') }}" class="financeiro-subnav-item {{ request()->routeIs('financeiro.relatorios*') ? 'active' : '' }}">
                    <i class="fa fa-bar-chart"></i> Relatórios
                </a>
                <a href="{{ route('financeiro.configuracoes.gateway') }}" class="financeiro-subnav-item {{ request()->routeIs('financeiro.configuracoes*') ? 'active' : '' }}">
                    <i class="fa fa-cogs"></i> Asaas
                </a>
            </div>
        </nav>

        <style>
            .financeiro-subnav-container {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 6px 10px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            .financeiro-subnav-scroll {
                display: flex;
                gap: 6px;
                overflow-x: auto;
                align-items: center;
                scrollbar-width: thin;
            }
            .financeiro-subnav-item {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 7px 12px;
                font-size: 13px;
                font-weight: 600;
                color: #475569;
                text-decoration: none !important;
                border-radius: 5px;
                white-space: nowrap;
                transition: all 0.15s ease-in-out;
            }
            .financeiro-subnav-item:hover {
                background: #e2e8f0;
                color: #0f172a;
            }
            .financeiro-subnav-item.active {
                background: #0284c7;
                color: #ffffff !important;
                box-shadow: 0 1px 2px rgba(2, 132, 199, 0.3);
            }
            .financeiro-subnav-item i {
                font-size: 13px;
            }
        </style>

        @include('financeiro::partials.alerts')

        @yield('financeiro-content')
    </div>
@endsection
