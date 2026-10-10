@extends('financeiro::layouts.app')

@section('title', 'Painel Geral Financeiro')
@section('breadcrumb', 'Painel Geral')

@section('financeiro-content')
<div class="financeiro-dashboard-wrapper">
    <!-- Cabeçalho Oficial do Módulo -->
    <div class="financeiro-header">
        <div class="financeiro-header-title">
            <h2>
                <i class="fa fa-tachometer" style="color: #47728f;"></i> Painel Geral de Gestão Financeira
            </h2>
            <span class="financeiro-header-desc">
                Indicadores operacionais, fluxo de recebimentos e controle de cobrança escolar
            </span>
        </div>
        <div class="financeiro-header-actions">
            <a href="{{ route('financeiro.baixas.create') }}" class="btn-ieducar-action btn-ieducar-green">
                <i class="fa fa-plus-circle"></i> Nova Baixa de Balcão
            </a>
            <a href="{{ route('financeiro.titulos.index') }}" class="btn-ieducar-action btn-ieducar-blue">
                <i class="fa fa-list"></i> Ver Todas as Mensalidades
            </a>
        </div>
    </div>

    <!-- 1. Cartões de Resumo Financeiro (KPIs no Padrão do i-Educar) -->
    <div class="financeiro-kpis-grid">
        <!-- Receita Recebida -->
        <div class="financeiro-kpi-card kpi-green">
            <div class="kpi-card-header">
                <span class="kpi-card-title">Receita Recebida no Mês</span>
                <i class="fa fa-check-circle-o kpi-icon"></i>
            </div>
            <div class="kpi-card-value text-green">
                R$ {{ number_format($kpis['recebido_mes'] ?? 0, 2, ',', '.') }}
            </div>
            <div class="kpi-card-footer">
                Liquidações via Asaas, Pix e Caixa
            </div>
        </div>

        <!-- A Receber no Mês -->
        <div class="financeiro-kpi-card kpi-blue">
            <div class="kpi-card-header">
                <span class="kpi-card-title">A Receber no Mês Atual</span>
                <i class="fa fa-calendar-check-o kpi-icon"></i>
            </div>
            <div class="kpi-card-value text-blue">
                R$ {{ number_format($kpis['a_receber_mes'] ?? 0, 2, ',', '.') }}
            </div>
            <div class="kpi-card-footer">
                Parcelas vincendas neste período
            </div>
        </div>

        <!-- Total em Atraso (Inadimplência) -->
        <div class="financeiro-kpi-card kpi-red">
            <div class="kpi-card-header">
                <span class="kpi-card-title">Total em Atraso (Inadimplência)</span>
                <i class="fa fa-exclamation-triangle kpi-icon"></i>
            </div>
            <div class="kpi-card-value text-red">
                R$ {{ number_format($kpis['total_inadimplente'] ?? 0, 2, ',', '.') }}
            </div>
            <div class="kpi-card-footer">
                <b>{{ $kpis['titulos_vencidos_count'] ?? 0 }}</b> parcelas aguardando acerto
            </div>
        </div>

        <!-- Taxa de Inadimplência -->
        <div class="financeiro-kpi-card kpi-amber">
            <div class="kpi-card-header">
                <span class="kpi-card-title">Taxa de Inadimplência</span>
                <i class="fa fa-pie-chart kpi-icon"></i>
            </div>
            <div class="kpi-card-value text-amber">
                {{ number_format($kpis['taxa_inadimplencia'] ?? 0, 1, ',', '.') }}%
            </div>
            <div class="kpi-card-footer">
                Em relação à receita faturada
            </div>
        </div>
    </div>

    <!-- 2. Tabela de Últimas Movimentações (Estilo Nativo i-Educar) -->
    <div class="financeiro-table-box">
        <div class="financeiro-box-header">
            <span>
                <i class="fa fa-clock-o"></i> Próximos Vencimentos e Cobranças Recentes
            </span>
            <a href="{{ route('financeiro.titulos.index') }}" class="financeiro-header-link">
                Histórico completo &raquo;
            </a>
        </div>
        <div class="financeiro-table-responsive">
            <table class="financeiro-table">
                <thead>
                    <tr>
                        <th width="110" style="text-align: center;">Vencimento</th>
                        <th>Aluno</th>
                        <th>Responsável Financeiro</th>
                        <th>Turma / Série</th>
                        <th width="80" style="text-align: center;">Parcela</th>
                        <th width="120" style="text-align: right;">Valor</th>
                        <th width="110" style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($titulosRecentes ?? [] as $titulo)
                        <tr>
                            <td align="center">
                                <strong>{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</strong>
                            </td>
                            <td>
                                <b>{{ $titulo->aluno->nome ?? 'Aluno ' . $titulo->aluno_id }}</b>
                            </td>
                            <td>{{ $titulo->responsavel->nome ?? 'Não informado' }}</td>
                            <td>{{ $titulo->turma_nome ?? '-' }}</td>
                            <td align="center">{{ $titulo->numero_parcela }}/{{ $titulo->total_parcelas }}</td>
                            <td align="right" style="font-weight: 700; color: #1b4c6b;">
                                R$ {{ number_format($titulo->valor_saldo ?? $titulo->valor_nominal, 2, ',', '.') }}
                            </td>
                            <td align="center">
                                @if($titulo->status === 'pago')
                                    <span class="badge-status badge-pago">Pago</span>
                                @elseif($titulo->status === 'vencido' || $titulo->isVencido())
                                    <span class="badge-status badge-vencido">Vencido</span>
                                @elseif($titulo->status === 'cancelado')
                                    <span class="badge-status badge-cancelado">Cancelado</span>
                                @else
                                    <span class="badge-status badge-pendente">Pendente</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="financeiro-empty-state">
                                <i class="fa fa-folder-open-o" style="font-size: 32px; color: #8ba4b5; margin-bottom: 8px; display: inline-block;"></i>
                                <div style="font-size: 14px; font-weight: 600; color: #47728f;">Não há informação para ser apresentada no momento.</div>
                                <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;">
                                    Cadastre responsáveis financeiros e configure os planos de pagamento para gerar contratos e parcelas.
                                </div>
                                <div style="margin-top: 14px; display: flex; justify-content: center; gap: 10px;">
                                    <a href="{{ route('financeiro.planos.create') }}" class="btn-ieducar-action btn-ieducar-outline">
                                        <i class="fa fa-plus"></i> Novo Plano de Pagamento
                                    </a>
                                    <a href="{{ route('financeiro.responsaveis.create') }}" class="btn-ieducar-action btn-ieducar-outline">
                                        <i class="fa fa-user-plus"></i> Cadastrar Responsável
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Painel Inferior de Atalhos Rápidos e Conformidade Legal -->
    <div class="financeiro-bottom-grid">
        <!-- Régua de Cobrança e Gateway Asaas -->
        <div class="financeiro-sub-box">
            <div class="financeiro-sub-header">
                <i class="fa fa-bell-o" style="color: #47728f;"></i> Régua de Cobrança Automática
            </div>
            <div class="financeiro-sub-content">
                <p>
                    Disparo diário de lembretes e notificações de vencimento com links diretos de Boleto e Pix para os responsáveis por E-mail e WhatsApp.
                </p>
                <div style="margin-top: 14px;">
                    <a href="{{ route('financeiro.configuracoes.gateway') }}" class="btn-ieducar-action btn-ieducar-outline">
                        <i class="fa fa-cog"></i> Configurações do Gateway Asaas e Chaves de Integração &raquo;
                    </a>
                </div>
            </div>
        </div>

        <!-- Relatórios Oficiais e Certidões -->
        <div class="financeiro-sub-box">
            <div class="financeiro-sub-header">
                <i class="fa fa-file-text-o" style="color: #47728f;"></i> Relatórios Gerenciais
            </div>
            <div class="financeiro-sub-content">
                <p>
                    Emissão de relatórios e certidões oficiais:
                </p>
                <ul class="financeiro-links-list">
                    <li>
                        <i class="fa fa-circle" style="font-size: 6px; color: #47728f;"></i>
                        <a href="{{ route('financeiro.relatorios.inadimplencia') }}">
                            Relatório de Inadimplência
                        </a>
                    </li>
                    <li>
                        <i class="fa fa-circle" style="font-size: 6px; color: #47728f;"></i>
                        <a href="{{ route('financeiro.relatorios.informe-irpf') }}">
                            Demonstrativo de Pagamentos para IRPF
                        </a>
                    </li>
                    <li>
                        <i class="fa fa-circle" style="font-size: 6px; color: #47728f;"></i>
                        <a href="{{ route('financeiro.relatorios.quitacao-anual') }}">
                            Declaração de Quitação Anual de Débitos (Lei 12.007/2009)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Estilos 100% Alinhados à Identidade Visual do i-Educar -->
<style>
    .financeiro-dashboard-wrapper {
        font-family: "Open Sans", Arial, sans-serif;
        color: #47728f;
    }

    /* Cabeçalho */
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
    }

    /* Botões Padrão i-Educar */
    .btn-ieducar-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 16px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 3px;
        text-decoration: none !important;
        cursor: pointer;
        font-family: "Open Sans", Arial, sans-serif;
        transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
    }
    .btn-ieducar-green {
        background-color: #0ac336;
        color: #ffffff !important;
        border: 1px solid #09b031;
    }
    .btn-ieducar-green:hover {
        background-color: #09b031;
        color: #ffffff !important;
    }
    .btn-ieducar-blue {
        background-color: #47728f;
        color: #ffffff !important;
        border: 1px solid #3d627b;
    }
    .btn-ieducar-blue:hover {
        background-color: #3b5f77;
        color: #ffffff !important;
    }
    .btn-ieducar-outline {
        background-color: #ffffff;
        color: #47728f !important;
        border: 1px solid #cddce6;
    }
    .btn-ieducar-outline:hover {
        background-color: #f5f9fd;
        border-color: #47728f;
        color: #1b4c6b !important;
    }

    /* Grid de KPIs */
    .financeiro-kpis-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }
    .financeiro-kpi-card {
        background: #ffffff;
        border: 1px solid #cddce6;
        border-radius: 3px;
        padding: 14px 16px;
        position: relative;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .financeiro-kpi-card.kpi-green { border-top: 3px solid #0ac336; }
    .financeiro-kpi-card.kpi-blue  { border-top: 3px solid #47728f; }
    .financeiro-kpi-card.kpi-red   { border-top: 3px solid #d9534f; }
    .financeiro-kpi-card.kpi-amber { border-top: 3px solid #f0ad4e; }

    .kpi-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
    }
    .kpi-card-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #47728f;
        letter-spacing: 0.3px;
    }
    .kpi-icon {
        font-size: 14px;
        color: #8ba4b5;
    }
    .kpi-card-value {
        font-size: 21px;
        font-weight: 700;
        margin-bottom: 4px;
        color: #1b4c6b;
    }
    .kpi-card-footer {
        font-size: 12px;
        color: #64748b;
    }

    .text-green { color: #0ac336 !important; }
    .text-blue  { color: #47728f !important; }
    .text-red   { color: #d9534f !important; }
    .text-amber { color: #f0ad4e !important; }

    /* Caixa da Tabela */
    .financeiro-table-box {
        background: #ffffff;
        border: 1px solid #cddce6;
        border-radius: 3px;
        margin-bottom: 20px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .financeiro-box-header {
        background: #47728f;
        color: #ffffff;
        padding: 9px 15px;
        font-size: 14px;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .financeiro-header-link {
        color: #e0f2fe !important;
        font-size: 12px;
        font-weight: 500;
        text-decoration: underline !important;
    }
    .financeiro-header-link:hover {
        color: #ffffff !important;
    }

    .financeiro-table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    .financeiro-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
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
    }
    .financeiro-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #eef4f8;
        color: #47728f;
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

    .financeiro-empty-state {
        text-align: center;
        padding: 30px 15px !important;
        background-color: #fafbfc;
    }

    /* Badges de Status Padrão i-Educar */
    .badge-status {
        display: inline-block;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 3px;
        text-transform: uppercase;
    }
    .badge-pago {
        background-color: #e0f9d8;
        color: #157100;
        border: 1px solid #c0eec0;
    }
    .badge-pendente {
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

    /* Grid Inferior */
    .financeiro-bottom-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .financeiro-sub-box {
        background: #ffffff;
        border: 1px solid #cddce6;
        border-radius: 3px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .financeiro-sub-header {
        background: #ccdce6;
        padding: 9px 15px;
        font-size: 13.5px;
        font-weight: 700;
        color: #47728f;
    }
    .financeiro-sub-content {
        padding: 14px 16px;
        font-size: 13px;
        color: #555555;
        line-height: 1.5;
    }
    .financeiro-sub-content p {
        margin: 0;
    }
    .financeiro-links-list {
        list-style: none;
        padding: 0;
        margin: 10px 0 0 0;
    }
    .financeiro-links-list li {
        margin-bottom: 7px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .financeiro-links-list li a {
        color: #47728f;
        text-decoration: underline;
        font-weight: 600;
    }
    .financeiro-links-list li a:hover {
        color: #1b4c6b;
    }
</style>
@endsection
