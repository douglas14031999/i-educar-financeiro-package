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
                Liquidações via Asaas, Pix e Secretaria
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
                Calculada sobre a receita faturada
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
                        <th width="100" style="text-align: center;">Vencimento</th>
                        <th>Aluno</th>
                        <th>Responsável Financeiro</th>
                        <th>Turma / Série</th>
                        <th width="80" style="text-align: center;">Parcela</th>
                        <th width="120" style="text-align: right;">Valor Nominal</th>
                        <th width="100" style="text-align: center;">Status</th>
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
                            <td align="right" style="font-weight: 700; color: #1e293b;">
                                R$ {{ number_format($titulo->valor_nominal, 2, ',', '.') }}
                            </td>
                            <td align="center">
                                @if($titulo->status === 'pago')
                                    <span class="badge-status badge-pago">Pago</span>
                                @elseif($titulo->status === 'vencido')
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
                                <i class="fa fa-info-circle fa-2x" style="color: #47728f; margin-bottom: 6px;"></i>
                                <div style="font-size: 13px; font-weight: 600; color: #334155;">Nenhuma mensalidade lançada para exibição no momento.</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                    Cadastre responsáveis financeiros e configure os planos de cobrança para emitir contratos e parcelas escolares.
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
                <i class="fa fa-bell-o" style="color: #47728f;"></i> Régua de Cobrança Automática (Asaas)
            </div>
            <div class="financeiro-sub-content">
                <p>
                    O módulo monitora diariamente os vencimentos e dispara lembretes automáticos com links diretos de Boleto e Pix para os responsáveis por E-mail e WhatsApp.
                </p>
                <div style="margin-top: 12px;">
                    <a href="{{ route('financeiro.configuracoes.gateway') }}" class="btn-ieducar-outline">
                        <i class="fa fa-sliders"></i> Ajustar Gatilhos e Chaves de API &raquo;
                    </a>
                </div>
            </div>
        </div>

        <!-- Relatórios Oficiais e Certidões -->
        <div class="financeiro-sub-box">
            <div class="financeiro-sub-header">
                <i class="fa fa-file-text-o" style="color: #47728f;"></i> Relatórios Oficiais e Demonstrativos
            </div>
            <div class="financeiro-sub-content">
                <p>
                    Emissão de documentos fiscais e comprovantes em conformidade com as exigências da legislação e da secretaria escolar:
                </p>
                <ul class="financeiro-links-list">
                    <li>
                        <i class="fa fa-file-pdf-o text-red"></i>
                        <a href="{{ route('financeiro.relatorios.inadimplencia') }}">
                            Relatório de Inadimplência e Títulos em Atraso
                        </a>
                    </li>
                    <li>
                        <i class="fa fa-calculator text-blue"></i>
                        <a href="{{ route('financeiro.relatorios.informe-irpf') }}">
                            Demonstrativo Anual de Pagamentos para IRPF
                        </a>
                    </li>
                    <li>
                        <i class="fa fa-certificate text-green"></i>
                        <a href="{{ route('financeiro.relatorios.quitacao-anual') }}">
                            Declaração de Quitação Anual de Débitos (Lei Federal 12.007/2009)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Estilos Dedicados no Padrão Oficial do i-Educar -->
<style>
    .financeiro-dashboard-wrapper {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: #334155;
    }

    /* Cabeçalho */
    .financeiro-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e2e8f0;
    }
    .financeiro-header-title h2 {
        color: #1b4c6b;
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.2px;
    }
    .financeiro-header-desc {
        color: #64748b;
        font-size: 12.5px;
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
        padding: 7px 14px;
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 4px;
        text-decoration: none !important;
        transition: all 0.15s ease-in-out;
        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    }
    .btn-ieducar-green {
        background-color: #28a745;
        color: #ffffff !important;
        border: 1px solid #218838;
    }
    .btn-ieducar-green:hover {
        background-color: #218838;
    }
    .btn-ieducar-blue {
        background-color: #47728f;
        color: #ffffff !important;
        border: 1px solid #3d627b;
    }
    .btn-ieducar-blue:hover {
        background-color: #3b5f77;
    }
    .btn-ieducar-outline {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        font-size: 12px;
        font-weight: 600;
        color: #1b4c6b !important;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        text-decoration: none !important;
        transition: all 0.15s ease-in-out;
    }
    .btn-ieducar-outline:hover {
        background-color: #f1f5f9;
        border-color: #94a3b8;
    }

    /* Grid de KPIs */
    .financeiro-kpis-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 22px;
    }
    .financeiro-kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 14px 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        position: relative;
    }
    .financeiro-kpi-card.kpi-green { border-top: 3px solid #10b981; }
    .financeiro-kpi-card.kpi-blue  { border-top: 3px solid #0284c7; }
    .financeiro-kpi-card.kpi-red   { border-top: 3px solid #ef4444; }
    .financeiro-kpi-card.kpi-amber { border-top: 3px solid #f59e0b; }

    .kpi-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
    }
    .kpi-card-title {
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.3px;
    }
    .kpi-icon {
        font-size: 14px;
        color: #94a3b8;
    }
    .kpi-card-value {
        font-size: 21px;
        font-weight: 700;
        letter-spacing: -0.5px;
        margin-bottom: 4px;
    }
    .kpi-card-footer {
        font-size: 11.5px;
        color: #64748b;
    }

    .text-green { color: #059669; }
    .text-blue  { color: #0284c7; }
    .text-red   { color: #dc2626; }
    .text-amber { color: #d97706; }

    /* Caixa da Tabela */
    .financeiro-table-box {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        margin-bottom: 22px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .financeiro-box-header {
        background: #47728f;
        color: #ffffff;
        padding: 10px 16px;
        font-size: 13.5px;
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
        font-size: 12.5px;
    }
    .financeiro-table thead tr {
        background-color: #f1f5f9;
        border-bottom: 2px solid #cbd5e1;
    }
    .financeiro-table th {
        padding: 9px 12px;
        font-size: 11.5px;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }
    .financeiro-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #e2e8f0;
        color: #1e293b;
    }
    .financeiro-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .financeiro-empty-state {
        text-align: center;
        padding: 30px 15px !important;
    }

    /* Badges de Status */
    .badge-status {
        display: inline-block;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 12px;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }
    .badge-pago {
        background-color: #dcfce7;
        color: #15803d;
    }
    .badge-pendente {
        background-color: #fef9c3;
        color: #854d0e;
    }
    .badge-vencido {
        background-color: #fee2e2;
        color: #991b1b;
    }
    .badge-cancelado {
        background-color: #f1f5f9;
        color: #64748b;
    }

    /* Grid Inferior */
    .financeiro-bottom-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .financeiro-sub-box {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .financeiro-sub-header {
        background: #eef4f8;
        border-bottom: 1px solid #cbd5e1;
        padding: 9px 14px;
        font-size: 13px;
        font-weight: 700;
        color: #1b4c6b;
    }
    .financeiro-sub-content {
        padding: 14px 16px;
        font-size: 12.5px;
        color: #475569;
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
        color: #0284c7;
        text-decoration: underline;
        font-weight: 500;
    }
    .financeiro-links-list li a:hover {
        color: #0369a1;
    }
</style>
@endsection
