<style>
    /* ========================================================
       DESIGN SYSTEM OFICIAL - MÓDULO FINANCEIRO I-EDUCAR
       ======================================================== */
    
    /* Blindagem da barra superior nativa i-Educar */
    .ieducar-menu-container {
        background: #e9eff7 !important;
        border-bottom: 1px solid #cddce6 !important;
    }
    .ieducar-menu {
        background: #e9eff7 !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }
    .ieducar-menu > li > a {
        font-size: 13px !important;
        font-weight: 600 !important;
        padding: 10px 16px !important;
        color: #1b4c6b !important;
        text-decoration: none !important;
    }
    .ieducar-menu > li:hover {
        background: #cddce6 !important;
    }
    .ieducar-sub-menu {
        background: #ffffff !important;
        border: 1px solid #cddce6 !important;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1) !important;
        border-radius: 0 0 4px 4px !important;
        z-index: 9999 !important;
    }
    .ieducar-sub-menu li a {
        font-size: 13px !important;
        padding: 8px 16px !important;
        color: #334155 !important;
        text-decoration: none !important;
    }
    .ieducar-sub-menu li a:hover {
        background: #f1f5f9 !important;
        color: #1b4c6b !important;
    }

    #financeiro-module {
        font-family: "Open Sans", Arial, sans-serif !important;
        color: #47728f;
    }

    /* Cabeçalho de Tela */
    .financeiro-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid #cddce6;
    }
    .financeiro-header-title h2 {
        color: #1b4c6b;
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        font-family: "Open Sans", Arial, sans-serif;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .financeiro-header-desc {
        color: #64748b;
        font-size: 13px;
        margin-top: 3px;
        display: block;
    }
    .financeiro-header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    /* Botões Padrão i-Educar */
    .btn-ieducar-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 7px 16px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 3px;
        text-decoration: none !important;
        cursor: pointer;
        font-family: "Open Sans", Arial, sans-serif;
        transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, color 0.15s ease-in-out;
        border: 1px solid transparent;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        line-height: 1.4;
        box-sizing: border-box;
    }
    .btn-ieducar-green {
        background-color: #0ac336;
        color: #ffffff !important;
        border-color: #09b031;
    }
    .btn-ieducar-green:hover {
        background-color: #09b031;
        color: #ffffff !important;
    }
    .btn-ieducar-blue {
        background-color: #47728f;
        color: #ffffff !important;
        border-color: #3d627b;
    }
    .btn-ieducar-blue:hover {
        background-color: #3b5f77;
        color: #ffffff !important;
    }
    .btn-ieducar-outline {
        background-color: #ffffff;
        color: #47728f !important;
        border-color: #cddce6;
    }
    .btn-ieducar-outline:hover {
        background-color: #f5f9fd;
        border-color: #47728f;
        color: #1b4c6b !important;
    }
    .btn-ieducar-danger {
        background-color: #d9534f;
        color: #ffffff !important;
        border-color: #d43f3a;
    }
    .btn-ieducar-danger:hover {
        background-color: #c9302c;
        color: #ffffff !important;
    }

    /* Barra de Filtros e Busca */
    .financeiro-filter-box {
        background-color: #ffffff;
        border: 1px solid #cddce6;
        padding: 12px 16px;
        margin-bottom: 18px;
        border-radius: 3px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .financeiro-filter-form {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }
    .filter-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .filter-label {
        font-size: 13px;
        font-weight: 600;
        color: #47728f;
        margin: 0;
        white-space: nowrap;
    }
    .ieducar-input,
    .ieducar-select {
        border-radius: 3px;
        background-color: #ffffff;
        border: 1px solid #cddce6;
        padding: 6px 10px;
        font-size: 13px;
        color: #47728f;
        font-family: "Open Sans", Arial, sans-serif;
        height: 33px;
        box-sizing: border-box;
        transition: border-color 0.15s ease-in-out;
    }
    .ieducar-input:focus,
    .ieducar-select:focus {
        border-color: #47728f;
        outline: none;
        box-shadow: 0 0 0 2px rgba(71, 114, 143, 0.15);
    }

    /* Caixa de Tabela */
    .financeiro-table-box {
        background: #ffffff;
        border: 1px solid #cddce6;
        border-radius: 3px;
        margin-bottom: 20px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        width: 100%;
    }
    .financeiro-box-header {
        background: #47728f;
        color: #ffffff;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: "Open Sans", Arial, sans-serif;
    }
    .box-header-info {
        font-size: 12px;
        font-weight: normal;
        opacity: 0.9;
    }
    .financeiro-table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    .financeiro-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        font-family: "Open Sans", Arial, sans-serif;
    }
    .financeiro-table thead tr {
        background-color: #ccdce6;
    }
    .financeiro-table th {
        padding: 9px 12px;
        font-size: 12px;
        font-weight: 700;
        color: #47728f;
        text-transform: uppercase;
        border: none;
        letter-spacing: 0.2px;
    }
    .financeiro-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #eef4f8;
        color: #47728f;
        vertical-align: middle;
    }
    .financeiro-table tbody tr:nth-child(odd) {
        background-color: #f5f9fd;
    }
    .financeiro-table tbody tr:nth-child(even) {
        background-color: #ffffff;
    }
    .financeiro-table tbody tr:hover {
        background-color: #eaf2f8;
    }

    .col-valor {
        font-weight: 700;
        color: #1b4c6b !important;
    }

    /* Ações Compactas na Tabela */
    .table-actions-group {
        display: inline-flex;
        gap: 5px;
        align-items: center;
        justify-content: center;
    }
    .btn-table-action {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        font-size: 11.5px;
        font-weight: 600;
        border-radius: 3px;
        text-decoration: none !important;
        cursor: pointer;
        border: 1px solid transparent;
        line-height: 1.2;
        transition: all 0.15s ease-in-out;
    }
    .btn-action-blue {
        background-color: #e0f2fe;
        color: #0369a1 !important;
        border-color: #bae6fd;
    }
    .btn-action-blue:hover {
        background-color: #0369a1;
        color: #ffffff !important;
    }
    .btn-action-green {
        background-color: #dcfce7;
        color: #15803d !important;
        border-color: #bbf7d0;
    }
    .btn-action-green:hover {
        background-color: #15803d;
        color: #ffffff !important;
    }
    .btn-action-amber {
        background-color: #fef3c7;
        color: #b45309 !important;
        border-color: #fde68a;
    }
    .btn-action-amber:hover {
        background-color: #b45309;
        color: #ffffff !important;
    }
    .btn-action-default {
        background-color: #f1f5f9;
        color: #475569 !important;
        border-color: #cbd5e1;
    }
    .btn-action-default:hover {
        background-color: #475569;
        color: #ffffff !important;
    }
    .btn-action-danger {
        background-color: #fee2e2;
        color: #b91c1c !important;
        border-color: #fecaca;
    }
    .btn-action-danger:hover {
        background-color: #b91c1c;
        color: #ffffff !important;
    }

    /* Badges de Status */
    .badge-status {
        display: inline-block;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 3px;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }
    .badge-pago,
    .badge-ativo,
    .badge-confirmado {
        background-color: #e0f9d8;
        color: #157100;
        border: 1px solid #c0eec0;
    }
    .badge-pendente,
    .badge-aberto {
        background-color: #fff7e2;
        color: #854d0e;
        border: 1px solid #fde68a;
    }
    .badge-vencido {
        background-color: #ffeeee;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .badge-cancelado {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #cbd5e1;
    }
    .badge-parcial {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .badge-quitado {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }

    /* Empty State */
    .financeiro-empty-state {
        text-align: center;
        padding: 32px 16px !important;
        background-color: #fafbfc;
    }
    .empty-icon {
        font-size: 32px;
        color: #8ba4b5;
        margin-bottom: 8px;
        display: inline-block;
    }
    .empty-title {
        font-size: 14px;
        font-weight: 600;
        color: #47728f;
    }
    .empty-desc {
        font-size: 12.5px;
        color: #64748b;
        margin-top: 4px;
    }

    /* Paginação Customizada i-Educar */
    .financeiro-pagination-wrapper {
        margin-top: 18px;
        display: flex;
        justify-content: center;
    }
    .financeiro-pagination-wrapper ul.pagination {
        display: inline-flex;
        padding-left: 0;
        list-style: none;
        border-radius: 3px;
        margin: 0;
        gap: 3px;
    }
    .financeiro-pagination-wrapper .page-item .page-link {
        color: #47728f;
        background-color: #ffffff;
        border: 1px solid #cddce6;
        padding: 6px 12px;
        font-size: 13px;
        text-decoration: none;
        border-radius: 3px;
    }
    .financeiro-pagination-wrapper .page-item.active .page-link {
        background-color: #47728f;
        border-color: #47728f;
        color: #ffffff;
    }
    .financeiro-pagination-wrapper .page-item.disabled .page-link {
        color: #94a3b8;
        background-color: #f8fafc;
        border-color: #e2e8f0;
    }

    /* ========================================================
       SUBMENU OFICIAL NATIVO I-EDUCAR (CORES PADRÃO DA VPS)
       ======================================================== */
    .ieducar-menu-container,
    .ieducar-menu {
        background: #e9eff7 !important;
        list-style: none !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100%;
        position: relative;
        z-index: 1000;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    .ieducar-menu {
        display: flex;
        flex-wrap: wrap;
    }
    .ieducar-menu > li {
        position: relative;
        list-style: none !important;
        margin: 0 !important;
        padding: 0 !important;
        float: left;
    }
    .ieducar-menu > li > a {
        display: block;
        padding: 10px 16px;
        color: #47728f !important;
        font-family: "Open Sans", sans-serif;
        font-size: 15px;
        font-weight: 500;
        text-decoration: none !important;
        line-height: 1.4;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .ieducar-menu > li:hover,
    .ieducar-menu > li > a:hover {
        background: #cddce6 !important;
        color: #47728f !important;
        text-decoration: none !important;
    }
    .ieducar-menu > li.ieducar-sidebar-menu-active,
    .ieducar-menu > li > a.ieducar-sidebar-menu-active {
        background: #cddce6 !important;
        color: #1b4c6b !important;
        font-weight: 600;
    }
    .ieducar-menu > li:hover > .ieducar-sub-menu,
    .ieducar-menu > li:focus-within > .ieducar-sub-menu {
        display: block !important;
    }
    .ieducar-sub-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        min-width: 220px;
        background: #FFF !important;
        border: 1px solid #f3f3f3 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
        z-index: 1100 !important;
        list-style: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .ieducar-sub-menu > li {
        list-style: none !important;
        margin: 0 !important;
        padding: 0 !important;
        border-bottom: 1px solid #f3f3f3 !important;
    }
    .ieducar-sub-menu > li:last-child {
        border-bottom: none !important;
    }
    .ieducar-sub-menu > li > a {
        display: block !important;
        padding: 7px 14px !important;
        color: #646b71 !important;
        font-size: 13.5px !important;
        font-family: "Open Sans", sans-serif !important;
        text-decoration: none !important;
        text-align: left !important;
        background: #FFF !important;
        line-height: 1.4 !important;
        white-space: nowrap;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .ieducar-sub-menu > li:hover,
    .ieducar-sub-menu > li > a:hover {
        background: #cddce6 !important;
        color: #1b4c6b !important;
    }
    .ieducar-sub-menu > li > a.ieducar-sidebar-menu-active {
        background: #cddce6 !important;
        color: #1b4c6b !important;
        font-weight: 600 !important;
    }
</style>
