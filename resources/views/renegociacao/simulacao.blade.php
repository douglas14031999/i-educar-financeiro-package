@extends('financeiro::layouts.app')

@section('title', 'Simulação de Acordo')
@section('breadcrumb', 'Renegociação > Simulação')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div>
            <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-calculator"></i> Proposta de Acordo e Novação de Dívida
            </h2>
            <span style="font-size: 12px; color: #666;">
                Confira o demonstrativo financeiro e as novas parcelas geradas antes de formalizar o acordo
            </span>
        </div>
        <div>
            <a href="{{ route('financeiro.renegociacao.index') }}" class="btn btn-sm">
                <i class="fa fa-arrow-left"></i> Voltar à Seleção
            </a>
        </div>
    </div>

    <!-- Tabela Resumo da Negociação -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="3" cellspacing="0" style="margin-bottom: 25px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-check-circle"></i> Resumo Financeiro da Proposta</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" width="180"><strong>Valor Original dos Títulos:</strong></td>
                <td class="formmdtd" style="font-size: 14px; font-weight: bold; color: #333;">
                    R$ {{ number_format($simulacao['total_original'], 2, ',', '.') }}
                </td>
                <td class="formmdtd" width="180"><strong>Desconto Concedido:</strong></td>
                <td class="formmdtd" style="font-size: 14px; font-weight: bold; color: #d9534f;">
                    - R$ {{ number_format($simulacao['desconto_concedido'], 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td class="formlttd"><strong>Valor Final do Acordo:</strong></td>
                <td class="formlttd" style="font-size: 16px; font-weight: 700; color: #438944;">
                    R$ {{ number_format($simulacao['valor_acordo'], 2, ',', '.') }}
                </td>
                <td class="formlttd"><strong>Condição de Pagamento:</strong></td>
                <td class="formlttd" style="font-size: 14px; font-weight: bold; color: #47728f;">
                    {{ $simulacao['qtd_parcelas'] }} parcela(s)
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Projeção das Novas Parcelas -->
    <h3 style="color: #47728f; font-size: 15px; margin-bottom: 10px;">
        <i class="fa fa-calendar"></i> Grade das Novas Parcelas a Serem Emitidas
    </h3>
    <table class="table-default" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 25px;">
        <thead>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="100">Parcela</th>
                <th width="150">Data de Vencimento</th>
                <th style="text-align: right; width: 180px;">Valor da Parcela</th>
                <th>Observações Contábeis</th>
            </tr>
        </thead>
        <tbody>
            @foreach($simulacao['parcelas'] as $p)
                <tr>
                    <td><strong>{{ $p['numero_parcela'] }} / {{ $p['total_parcelas'] }}</strong></td>
                    <td style="font-weight: bold; color: #47728f;">{{ $p['data_vencimento_formatada'] }}</td>
                    <td style="text-align: right; font-weight: 700; color: #438944;">
                        R$ {{ number_format($p['valor'], 2, ',', '.') }}
                    </td>
                    <td style="font-size: 12px; color: #666;">
                        Parcela resultante de renegociação extrajudicial escolar
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Formulário Oculto de Confirmação Definitiva -->
    <form method="post" action="{{ route('financeiro.renegociacao.confirmar') }}" onsubmit="return confirm('ATENÇÃO: Ao confirmar, os títulos antigos serão baixados como RENEGOCIADOS e novas parcelas serão geradas. Deseja prosseguir?');">
        @csrf
        @foreach($simulacao['titulos'] as $t)
            <input type="hidden" name="titulos_ids[]" value="{{ $t->id }}">
        @endforeach
        <input type="hidden" name="desconto_valor" value="{{ $simulacao['desconto_concedido'] }}">
        <input type="hidden" name="qtd_parcelas" value="{{ $simulacao['qtd_parcelas'] }}">
        <input type="hidden" name="primeiro_vencimento" value="{{ $simulacao['parcelas'][0]['data_vencimento'] }}">

        <div style="text-align: right; padding-top: 15px; border-top: 1px solid #ccdce6;">
            <a href="{{ route('financeiro.renegociacao.index') }}" class="btn btn-sm" style="margin-right: 10px;">
                <i class="fa fa-times"></i> Cancelar
            </a>
            <button type="submit" class="botaosalvar" style="font-size: 14px; padding: 8px 20px;">
                <i class="fa fa-check"></i> Formalizar e Gerar Acordo &raquo;
            </button>
        </div>
    </form>
@endsection
