@extends('financeiro::layouts.app')

@section('title', 'Planos de Cobrança')
@section('breadcrumb', 'Planos de Cobrança')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-list-alt"></i> Modelos de Planos de Pagamento e Anuidades
        </h2>
        <a href="{{ route('financeiro.planos.create') }}" class="btn btn-green">
            <i class="fa fa-plus"></i> Novo Plano de Cobrança
        </a>
    </div>

    <!-- Filtro de Ano Letivo -->
    <div style="background-color: #ffffff; border: 1px solid #cddce6; padding: 12px; margin-bottom: 15px; border-radius: 3px;">
        <form method="get" action="{{ route('financeiro.planos.index') }}" style="display: flex; gap: 10px; align-items: center;">
            <label class="form" style="margin: 0; font-size: 13px;">Ano Letivo:</label>
            <input type="number" name="ano_letivo" value="{{ $anoLetivo }}" class="form-input" style="max-width: 120px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-filter"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- Tabela de Listagem -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0">
        <thead>
            <tr>
                <th colspan="6" class="formdktd">
                    <i class="fa fa-table"></i> Planos Cadastrados ({{ $planos->total() }})
                </th>
            </tr>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th>Nome do Plano</th>
                <th width="100">Ano Letivo</th>
                <th width="120" style="text-align: center;">Parcelas</th>
                <th width="140" style="text-align: right;">Valor Anual</th>
                <th width="140" style="text-align: right;">Média Mensal</th>
                <th width="120" style="text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($planos as $plano)
                <tr>
                    <td>
                        <a href="{{ route('financeiro.planos.show', $plano->id) }}">
                            <strong>{{ $plano->nome }}</strong>
                        </a>
                        @if($plano->descricao)
                            <div style="font-size: 11px; color: #777;">{{ $plano->descricao }}</div>
                        @endif
                    </td>
                    <td>{{ $plano->ano_letivo }}</td>
                    <td style="text-align: center;">{{ $plano->qtd_parcelas }}x</td>
                    <td style="text-align: right; font-weight: 700; color: #47728f;">
                        R$ {{ number_format($plano->valor_total, 2, ',', '.') }}
                    </td>
                    <td style="text-align: right;">
                        R$ {{ number_format($plano->qtd_parcelas > 0 ? $plano->valor_total / $plano->qtd_parcelas : 0, 2, ',', '.') }}
                    </td>
                    <td style="text-align: center;">
                        <a href="{{ route('financeiro.planos.show', $plano->id) }}" class="btn btn-sm" title="Ver Parcelas">
                            <i class="fa fa-eye"></i> Detalhes
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 25px; color: #777777;">
                        Nenhum plano de cobrança cadastrado para o ano selecionado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 15px;">
        {{ $planos->appends(['ano_letivo' => $anoLetivo])->links() }}
    </div>
@endsection
