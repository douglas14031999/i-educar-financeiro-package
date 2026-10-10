<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Módulo Financeiro') - i-Educar</title>

    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: "Open Sans", sans-serif;
            background-color: #f0f4f7;
            color: #333333;
            font-size: 14px;
        }

        /* Top Header i-Educar */
        .ieducar-header {
            background-color: #ffffff;
            border-bottom: 2px solid #ccdce6;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ieducar-brand {
            font-size: 18px;
            font-weight: 700;
            color: #47728f;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ieducar-brand span {
            background-color: #47728f;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        /* Navigation Bar i-Educar */
        .ieducar-topmenu {
            background-color: #47728f;
            padding: 0 20px;
            display: flex;
            align-items: center;
            overflow-x: auto;
        }
        .ieducar-topmenu a {
            color: #ffffff;
            text-decoration: none;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .ieducar-topmenu a:hover,
        .ieducar-topmenu a.active {
            background-color: #375970;
            border-bottom-color: #89b3d0;
        }

        /* Main Container */
        .ieducar-container {
            max-width: 1280px;
            margin: 20px auto;
            padding: 0 15px;
        }

        /* Breadcrumb */
        .ieducar-breadcrumb {
            margin-bottom: 15px;
            font-size: 12px;
            color: #777777;
        }
        .ieducar-breadcrumb a {
            color: #47728f;
            text-decoration: none;
        }

        /* Standard Table i-Educar */
        .table-default,
        .tablecadastro,
        .tablelistagem {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            border: 1px solid #cddce6;
            margin-bottom: 20px;
        }
        .table-default thead tr,
        .formdktd {
            background-color: #ccdce6 !important;
            color: #47728f !important;
            font-weight: 700 !important;
            text-align: left;
            padding: 10px 12px;
            font-size: 14px;
        }
        .table-default th,
        .table-default td {
            padding: 10px 12px;
            border-bottom: 1px solid #cddce6;
        }
        .table-default tbody tr:nth-child(odd),
        .formmdtd {
            background-color: #f5f9fd;
        }
        .table-default tbody tr:nth-child(even),
        .formlttd {
            background-color: #ffffff;
        }
        .table-default a {
            color: #47728f;
            font-weight: 600;
            text-decoration: none;
        }
        .table-default a:hover {
            text-decoration: underline;
        }

        /* Form Controls */
        input.form-input,
        select.form-input,
        textarea.form-input {
            border-radius: 3px;
            background-color: #ffffff;
            border: solid 1px #cddce6;
            padding: 8px 10px;
            font-size: 14px;
            color: #47728f;
            box-sizing: border-box;
            width: 100%;
            max-width: 450px;
            font-family: "Open Sans", sans-serif;
        }
        input.form-input:focus,
        select.form-input:focus {
            outline: none;
            border-color: #47728f;
        }
        label.form {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            color: #47728f;
        }
        .campo_obrigatorio {
            color: #cc0000;
            font-weight: bold;
        }

        /* Buttons i-Educar */
        .btn,
        .geral-botao {
            background-color: #ffffff;
            font-size: 13px;
            color: #47728f;
            padding: 8px 18px;
            border-radius: 3px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #cddce6;
            text-decoration: none;
            display: inline-block;
            font-family: "Open Sans", sans-serif;
            transition: all 0.2s;
        }
        .btn:hover,
        .geral-botao:hover {
            background-color: #f5f9fd;
            border-color: #47728f;
        }
        .btn-primary,
        .botaosalvar {
            background-color: #47728f !important;
            color: #ffffff !important;
            border-color: #375970 !important;
        }
        .btn-primary:hover,
        .botaosalvar:hover {
            background-color: #375970 !important;
        }
        .btn-green,
        .btn-success {
            background-color: #438944 !important;
            color: #ffffff !important;
            border-color: #366f37 !important;
        }
        .btn-green:hover {
            background-color: #366f37 !important;
        }
        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-aberto { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-pago { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-vencido { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .badge-parcial { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .kpi-card {
            background-color: #ffffff;
            border: 1px solid #cddce6;
            border-left: 4px solid #47728f;
            padding: 15px;
            border-radius: 3px;
        }
        .kpi-title {
            font-size: 12px;
            color: #777777;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .kpi-value {
            font-size: 22px;
            font-weight: 700;
            color: #47728f;
        }
        .kpi-desc {
            font-size: 11px;
            color: #888888;
            margin-top: 5px;
        }

        /* Alerts */
        .alert {
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: 3px;
            font-size: 13px;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
    @stack('styles')
</head>
<body>

    <header class="ieducar-header">
        <div class="ieducar-brand">
            <i class="fa fa-graduation-cap"></i> i-Educar <span>Módulo Financeiro</span>
        </div>
        <div>
            <span style="color: #47728f; font-weight: 600;">Escola Privada / Comunitária</span>
        </div>
    </header>

    <nav class="ieducar-topmenu">
        <a href="{{ route('financeiro.dashboard') }}" class="{{ request()->routeIs('financeiro.dashboard') ? 'active' : '' }}">
            <i class="fa fa-tachometer"></i> Painel Geral
        </a>
        <a href="{{ route('financeiro.responsaveis.index') }}" class="{{ request()->is('*responsaveis*') ? 'active' : '' }}">
            <i class="fa fa-users"></i> Responsáveis
        </a>
        <a href="{{ route('financeiro.planos.index') }}" class="{{ request()->is('*planos*') ? 'active' : '' }}">
            <i class="fa fa-list-alt"></i> Planos & Preços
        </a>
        <a href="{{ route('financeiro.contratos.index') }}" class="{{ request()->is('*contratos*') ? 'active' : '' }}">
            <i class="fa fa-file-text-o"></i> Contratos
        </a>
        <a href="{{ route('financeiro.titulos.index') }}" class="{{ request()->is('*titulos*') ? 'active' : '' }}">
            <i class="fa fa-barcode"></i> Mensalidades & Títulos
        </a>
        <a href="{{ route('financeiro.baixas.create') }}" class="{{ request()->is('*baixas*') ? 'active' : '' }}">
            <i class="fa fa-money"></i> Baixa de Balcão
        </a>
        <a href="{{ route('financeiro.renegociacao.index') }}" class="{{ request()->is('*renegociacao*') ? 'active' : '' }}">
            <i class="fa fa-handshake-o"></i> Acordos
        </a>
        <a href="{{ route('financeiro.contas-pagar.index') }}" class="{{ request()->is('*contas-pagar*') ? 'active' : '' }}">
            <i class="fa fa-calculator"></i> Contas a Pagar
        </a>
        <a href="{{ route('financeiro.relatorios.index') }}" class="{{ request()->is('*relatorios*') ? 'active' : '' }}">
            <i class="fa fa-bar-chart"></i> Relatórios
        </a>
        <a href="{{ route('financeiro.configuracoes.gateway') }}" class="{{ request()->is('*configuracoes*') ? 'active' : '' }}">
            <i class="fa fa-cogs"></i> Asaas / Configurações
        </a>
    </nav>

    <div class="ieducar-container">
        @include('financeiro::partials.alerts')

        <div class="ieducar-breadcrumb">
            <a href="{{ url('/') }}">Início</a> &raquo;
            <a href="{{ route('financeiro.dashboard') }}">Financeiro</a> &raquo;
            <span>@yield('breadcrumb', 'Visão Geral')</span>
        </div>

        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
