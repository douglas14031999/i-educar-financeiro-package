@extends('financeiro::layouts.app')

@section('title', 'Painel Geral Financeiro')
@section('breadcrumb', 'Painel Geral')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ Asset::get('css/ieducar.css') }}"/>
@endpush

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h2 style="color: #47728f; margin: 0; font-size: 16px; font-weight: bold;">
            Painel Geral de Gestão Financeira
        </h2>
        <div>
            <a href="{{ route('financeiro.baixas.create') }}">
                <button type="button" class="btn-green">Nova Baixa de Balcão</button>
            </a>
            <a href="{{ route('financeiro.titulos.index') }}" style="margin-left: 6px;">
                <button type="button" class="btn-green">Ver Todas as Mensalidades</button>
            </a>
        </div>
    </div>

    <!-- 1. Tabela de Resumo dos Indicadores Financeiros (Padrão Oficial tablecadastro) -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0" role="presentation">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24"><b>Resumo Financeiro do Mês Atual</b></td>
            </tr>
            <tr>
                <td class="formmdtd" width="22%" valign="top">
                    <span class="form">Receita Recebida:</span>
                </td>
                <td class="formmdtd" width="28%" valign="top">
                    <b style="color: #438944; font-size: 13px;">R$ {{ number_format($kpis['recebido_mes'] ?? 0, 2, ',', '.') }}</b><br>
                    <sub style="color: #666;">Liquidações via Asaas, Pix e Caixa</sub>
                </td>
                <td class="formmdtd" width="22%" valign="top">
                    <span class="form">A Receber no Mês:</span>
                </td>
                <td class="formmdtd" width="28%" valign="top">
                    <b style="color: #47728f; font-size: 13px;">R$ {{ number_format($kpis['a_receber_mes'] ?? 0, 2, ',', '.') }}</b><br>
                    <sub style="color: #666;">Parcelas vincendas deste período</sub>
                </td>
            </tr>
            <tr>
                <td class="formlttd" valign="top">
                    <span class="form">Total em Atraso (Inadimplência):</span>
                </td>
                <td class="formlttd" valign="top">
                    <b style="color: #b94a48; font-size: 13px;">R$ {{ number_format($kpis['total_inadimplente'] ?? 0, 2, ',', '.') }}</b><br>
                    <sub style="color: #666;">{{ $kpis['titulos_vencidos_count'] ?? 0 }} parcelas vencidas</sub>
                </td>
                <td class="formlttd" valign="top">
                    <span class="form">Taxa de Inadimplência:</span>
                </td>
                <td class="formlttd" valign="top">
                    <b style="font-size: 13px;">{{ number_format($kpis['taxa_inadimplencia'] ?? 0, 1, ',', '.') }}%</b><br>
                    <sub style="color: #666;">Em relação à receita faturada</sub>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="separator" style="margin: 15px 0;"></div>

    <!-- 2. Tabela de Listagem de Cobranças Recentes (Padrão Oficial table-default) -->
    <table class="table-default" width="100%">
        <thead>
            <tr class="titulo-tabela-listagem">
                <th colspan="7">Próximos Vencimentos e Cobranças Recentes</th>
            </tr>
            <tr>
                <th style="font-weight: bold; width: 90px;">Vencimento</th>
                <th style="font-weight: bold;">Aluno</th>
                <th style="font-weight: bold;">Responsável Financeiro</th>
                <th style="font-weight: bold;">Turma / Série</th>
                <th style="font-weight: bold; width: 80px; text-align: center;">Parcela</th>
                <th style="font-weight: bold; width: 110px; text-align: right;">Valor</th>
                <th style="font-weight: bold; width: 90px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($titulosRecentes ?? [] as $titulo)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</td>
                    <td><b>{{ $titulo->aluno->nome ?? 'Aluno ' . $titulo->aluno_id }}</b></td>
                    <td>{{ $titulo->responsavel->nome ?? 'Não informado' }}</td>
                    <td>{{ $titulo->turma_nome ?? '-' }}</td>
                    <td align="center">{{ $titulo->numero_parcela }}/{{ $titulo->total_parcelas }}</td>
                    <td align="right">R$ {{ number_format($titulo->valor_nominal, 2, ',', '.') }}</td>
                    <td align="center">{{ ucfirst($titulo->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" align="center" style="padding: 20px; color: #555;">
                        Não há informação para ser apresentada no momento.<br>
                        <sub style="color: #777;">Cadastre responsáveis financeiros e configure os planos de pagamento para gerar contratos e parcelas.</sub>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="separator" style="margin: 15px 0;"></div>

    <!-- 3. Painel de Ferramentas e Relatórios (Padrão Oficial tablecadastro) -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0" role="presentation">
        <tbody>
            <tr>
                <td class="formdktd" colspan="2" height="24"><b>Operações e Relatórios Rápidos</b></td>
            </tr>
            <tr>
                <td class="formmdtd" width="50%" valign="top">
                    <span class="form"><b>Régua de Cobrança Automática:</b></span><br>
                    <span>Disparo diário de lembretes e notificações de vencimento com links diretos de Boleto e Pix para os responsáveis por E-mail e WhatsApp.</span><br><br>
                    <a href="{{ route('financeiro.configuracoes.gateway') }}">
                        <u>Configurações do Gateway Asaas e Chaves de Integração</u> &raquo;
                    </a>
                </td>
                <td class="formmdtd" width="50%" valign="top">
                    <span class="form"><b>Relatórios Gerenciais:</b></span><br>
                    <span>Emissão de relatórios e certidões oficiais:</span><br><br>
                    &bull; <a href="{{ route('financeiro.relatorios.inadimplencia') }}"><u>Relatório de Inadimplência</u></a><br>
                    &bull; <a href="{{ route('financeiro.relatorios.informe-irpf') }}"><u>Demonstrativo de Pagamentos para IRPF</u></a><br>
                    &bull; <a href="{{ route('financeiro.relatorios.quitacao-anual') }}"><u>Declaração de Quitação Anual de Débitos (Lei 12.007/2009)</u></a>
                </td>
            </tr>
        </tbody>
    </table>
@endsection
