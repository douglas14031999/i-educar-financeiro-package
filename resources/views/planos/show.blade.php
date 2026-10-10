@extends('financeiro::layouts.app')

@section('title', 'Detalhes do Plano de Cobrança')
@section('breadcrumb', 'Detalhes do Plano')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-list-alt"></i> {{ $plano->nome }}
        </h2>
        <div>
            <form method="post" action="{{ route('financeiro.planos.destroy', $plano->id) }}" onsubmit="return confirm('Deseja realmente excluir este plano?');" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm" style="color: #cc0000;">
                    <i class="fa fa-trash"></i> Excluir Plano
                </button>
            </form>
            <a href="{{ route('financeiro.planos.index') }}" class="btn btn-sm" style="margin-left: 5px;">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Tabela Resumo do Plano -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 20px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-info-circle"></i> Especificações do Plano</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" width="160"><strong>Ano Letivo:</strong></td>
                <td class="formmdtd">{{ $plano->ano_letivo }}</td>
                <td class="formmdtd" width="160"><strong>Escola:</strong></td>
                <td class="formmdtd">Escola #{{ $plano->escola_id }}</td>
            </tr>
            <tr>
                <td class="formlttd"><strong>Valor Total:</strong></td>
                <td class="formlttd" style="font-weight: 700; color: #438944; font-size: 16px;">
                    R$ {{ number_format($plano->valor_total, 2, ',', '.') }}
                </td>
                <td class="formlttd"><strong>Qtd. Parcelas:</strong></td>
                <td class="formlttd">{{ $plano->qtd_parcelas }} parcelas</td>
            </tr>
            @if($plano->descricao)
                <tr>
                    <td class="formmdtd"><strong>Descrição:</strong></td>
                    <td class="formmdtd" colspan="3">{{ $plano->descricao }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Tabela das Parcelas/Itens da Anuidade -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0">
        <thead>
            <tr>
                <th colspan="6" class="formdktd">
                    <i class="fa fa-calendar-check-o"></i> Grade de Parcelas Geradas ({{ $plano->itens->count() }})
                </th>
            </tr>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="80" style="text-align: center;">Parcela</th>
                <th>Descrição do Item</th>
                <th width="150">Tipo</th>
                <th width="120" style="text-align: center;">Competência</th>
                <th width="140" style="text-align: center;">Vencimento Padrão</th>
                <th width="140" style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plano->itens as $item)
                <tr>
                    <td style="text-align: center; font-weight: 700;">
                        {{ $item->numero_parcela == 0 ? 'Matrícula' : sprintf('%02d', $item->numero_parcela) }}
                    </td>
                    <td><strong>{{ $item->descricao }}</strong></td>
                    <td>
                        <span class="badge {{ $item->tipo_item === 'matricula' ? 'badge-parcial' : 'badge-aberto' }}">
                            {{ ucfirst($item->tipo_item) }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        Mês {{ sprintf('%02d', $item->mes_competencia) }}
                    </td>
                    <td style="text-align: center;">
                        Dia {{ sprintf('%02d', $item->dia_vencimento_padrao) }}
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #47728f;">
                        R$ {{ number_format($item->valor, 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #777;">
                        Nenhuma parcela configurada para este plano.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
