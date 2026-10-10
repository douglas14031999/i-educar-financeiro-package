@extends('financeiro::layouts.app')

@section('title', 'Contratos Financeiros')
@section('breadcrumb', 'Contratos Financeiros')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-file-text-o"></i> Gestão de Contratos Financeiros
        </h2>
        <a href="{{ route('financeiro.contratos.create') }}" class="btn btn-green">
            <i class="fa fa-plus"></i> Novo Contrato por Matrícula
        </a>
    </div>

    <!-- Barra de Filtros -->
    <div style="background-color: #ffffff; border: 1px solid #cddce6; padding: 12px; margin-bottom: 15px; border-radius: 3px;">
        <form method="get" action="{{ route('financeiro.contratos.index') }}" style="display: flex; gap: 10px; align-items: center;">
            <label class="form" style="margin: 0; font-size: 13px;">Ano Letivo:</label>
            <input type="number" name="ano_letivo" value="{{ $anoLetivo }}" class="form-input" style="max-width: 100px;">

            <label class="form" style="margin: 0 0 0 10px; font-size: 13px;">Status:</label>
            <select name="status" class="form-input" style="max-width: 150px;">
                <option value="">Todos</option>
                <option value="ativo" {{ $status === 'ativo' ? 'selected' : '' }}>Ativos</option>
                <option value="cancelado" {{ $status === 'cancelado' ? 'selected' : '' }}>Cancelados</option>
                <option value="quitado" {{ $status === 'quitado' ? 'selected' : '' }}>Quitados</option>
            </select>

            <button type="submit" class="btn btn-primary">
                <i class="fa fa-filter"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- Tabela de Listagem -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0">
        <thead>
            <tr>
                <th colspan="7" class="formdktd">
                    <i class="fa fa-list"></i> Contratos Registrados ({{ $contratos->total() }})
                </th>
            </tr>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="140">Número Contrato</th>
                <th width="90">Matrícula</th>
                <th width="90">Aluno ID</th>
                <th>Responsável Financeiro</th>
                <th>Plano Contratado</th>
                <th width="130" style="text-align: right;">Valor Total</th>
                <th width="100" style="text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($contratos as $contrato)
                <tr>
                    <td>
                        <a href="{{ route('financeiro.contratos.show', $contrato->id) }}">
                            <strong>{{ $contrato->numero_contrato }}</strong>
                        </a>
                    </td>
                    <td>#{{ $contrato->matricula_id }}</td>
                    <td>Aluno #{{ $contrato->aluno_id }}</td>
                    <td>{{ $contrato->responsavel->nome ?? 'Não informado' }}</td>
                    <td>{{ $contrato->plano->nome ?? '-' }}</td>
                    <td style="text-align: right; font-weight: 700; color: #47728f;">
                        R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $contrato->status === 'ativo' ? 'pago' : ($contrato->status === 'cancelado' ? 'vencido' : 'aberto') }}">
                            {{ ucfirst($contrato->status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 25px; color: #777777;">
                        Nenhum contrato financeiro encontrado para os filtros selecionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 15px;">
        {{ $contratos->appends(['ano_letivo' => $anoLetivo, 'status' => $status])->links() }}
    </div>
@endsection
