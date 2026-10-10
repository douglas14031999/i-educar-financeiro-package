@extends('financeiro::layouts.app')

@section('title', 'Painel Geral Financeiro')
@section('breadcrumb', 'Painel Geral')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: #47728f; margin: 0; font-size: 20px; font-weight: 700;">
            <i class="fa fa-tachometer"></i> Painel Geral de Gestão Financeira
        </h2>
        <div>
            <a href="{{ route('financeiro.baixas.create') }}" class="btn btn-green">
                <i class="fa fa-plus"></i> Nova Baixa de Balcão
            </a>
            <a href="{{ route('financeiro.titulos.index') }}" class="btn btn-primary" style="margin-left: 8px;">
                <i class="fa fa-barcode"></i> Ver Todas as Mensalidades
            </a>
        </div>
    </div>

    <!-- Indicadores Financeiros Rápidos -->
    <div class="kpi-grid">
        <div class="kpi-card" style="border-left-color: #438944;">
            <div class="kpi-title">Receita Recebida no Mês</div>
            <div class="kpi-value" style="color: #438944;">R$ {{ number_format($kpis['recebido_mes'] ?? 0, 2, ',', '.') }}</div>
            <div class="kpi-desc">Liquidações via Asaas, Pix e Secretaria</div>
        </div>

        <div class="kpi-card" style="border-left-color: #47728f;">
            <div class="kpi-title">A Receber no Mês Atual</div>
            <div class="kpi-value">R$ {{ number_format($kpis['a_receber_mes'] ?? 0, 2, ',', '.') }}</div>
            <div class="kpi-desc">Parcelas com vencimento neste mês</div>
        </div>

        <div class="kpi-card" style="border-left-color: #d9534f;">
            <div class="kpi-title">Total em Atraso (Inadimplência)</div>
            <div class="kpi-value" style="color: #d9534f;">R$ {{ number_format($kpis['total_inadimplente'] ?? 0, 2, ',', '.') }}</div>
            <div class="kpi-desc">{{ $kpis['titulos_vencidos_count'] ?? 0 }} parcelas vencidas</div>
        </div>

        <div class="kpi-card" style="border-left-color: #f0ad4e;">
            <div class="kpi-title">Taxa de Inadimplência</div>
            <div class="kpi-value" style="color: #f0ad4e;">{{ number_format($kpis['taxa_inadimplencia'] ?? 0, 1, ',', '.') }}%</div>
            <div class="kpi-desc">Em relação à receita faturada</div>
        </div>
    </div>

    <!-- Tabela de Últimas Movimentações e Cobranças (Estilo Oficial i-Educar) -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0">
        <thead>
            <tr>
                <th colspan="7" class="formdktd">
                    <i class="fa fa-list"></i> Próximos Vencimentos e Cobranças Recentes
                </th>
            </tr>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="80">Vencimento</th>
                <th>Aluno</th>
                <th>Responsável Financeiro</th>
                <th>Turma / Série</th>
                <th width="100">Parcela</th>
                <th width="110" style="text-align: right;">Valor</th>
                <th width="100" style="text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($titulosRecentes ?? [] as $titulo)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</td>
                    <td><strong>{{ $titulo->aluno->nome ?? 'Aluno ' . $titulo->aluno_id }}</strong></td>
                    <td>{{ $titulo->responsavel->nome ?? 'Não informado' }}</td>
                    <td>{{ $titulo->turma_nome ?? '-' }}</td>
                    <td>{{ $titulo->numero_parcela }}/{{ $titulo->total_parcelas }}</td>
                    <td style="text-align: right; font-weight: 600;">
                        R$ {{ number_format($titulo->valor_nominal, 2, ',', '.') }}
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $titulo->status }}">{{ ucfirst($titulo->status) }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 25px; color: #777777;">
                        <i class="fa fa-info-circle fa-2x" style="color: #47728f; display: block; margin-bottom: 8px;"></i>
                        Nenhuma mensalidade lançada para exibição no momento.<br>
                        Cadastre responsáveis financeiros e configure os planos de pagamento para gerar contratos e parcelas.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Régua e Ações Rápidas -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div style="background-color: #ffffff; border: 1px solid #cddce6; padding: 15px; border-radius: 3px;">
            <h4 style="color: #47728f; margin: 0 0 10px 0; font-size: 15px; font-weight: 700;">
                <i class="fa fa-bell-o"></i> Status da Régua de Cobrança (Asaas)
            </h4>
            <p style="font-size: 13px; color: #555555; margin-bottom: 12px;">
                A régua de cobrança monitora diariamente os vencimentos e dispara avisos com links diretos de Boleto e Pix para os responsáveis por E-mail e WhatsApp.
            </p>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('financeiro.configuracoes.gateway') }}" class="btn btn-sm">
                    <i class="fa fa-sliders"></i> Ajustar Gatilhos (D-7, D-3, D0)
                </a>
            </div>
        </div>

        <div style="background-color: #ffffff; border: 1px solid #cddce6; padding: 15px; border-radius: 3px;">
            <h4 style="color: #47728f; margin: 0 0 10px 0; font-size: 15px; font-weight: 700;">
                <i class="fa fa-file-pdf-o"></i> Relatórios Oficiais Rápidos
            </h4>
            <p style="font-size: 13px; color: #555555; margin-bottom: 12px;">
                Emita relatórios consolidados em PDF e planilhas em conformidade com as rotinas da secretaria escolar.
            </p>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('financeiro.relatorios.inadimplencia') }}" class="btn btn-sm">
                    <i class="fa fa-exclamation-circle"></i> Inadimplência
                </a>
                <a href="{{ route('financeiro.relatorios.informe-irpf') }}" class="btn btn-sm">
                    <i class="fa fa-file-text-o"></i> Informe IRPF
                </a>
                <a href="{{ route('financeiro.relatorios.quitacao-anual') }}" class="btn btn-sm">
                    <i class="fa fa-check-square-o"></i> Lei 12.007/2009
                </a>
            </div>
        </div>
    </div>
@endsection
