@extends('financeiro::layouts.app')

@section('title', 'Responsáveis Financeiros')
@section('breadcrumb', 'Responsáveis Financeiros')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-users"></i> Cadastro de Responsáveis Financeiros
        </h2>
        <a href="{{ route('financeiro.responsaveis.create') }}" class="btn btn-green">
            <i class="fa fa-plus"></i> Novo Responsável
        </a>
    </div>

    <!-- Barra de Filtro / Busca Padrão i-Educar -->
    <div style="background-color: #ffffff; border: 1px solid #cddce6; padding: 12px; margin-bottom: 15px; border-radius: 3px;">
        <form method="get" action="{{ route('financeiro.responsaveis.index') }}" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar por Nome, CPF/CNPJ ou E-mail..." class="form-input" style="max-width: 400px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-search"></i> Buscar
            </button>
            @if($busca)
                <a href="{{ route('financeiro.responsaveis.index') }}" class="btn">Limpar</a>
            @endif
        </form>
    </div>

    <!-- Tabela de Listagem -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0">
        <thead>
            <tr>
                <th colspan="6" class="formdktd">
                    <i class="fa fa-list"></i> Responsáveis Cadastrados ({{ $responsaveis->total() }})
                </th>
            </tr>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th>Nome do Responsável</th>
                <th width="150">CPF / CNPJ</th>
                <th width="200">E-mail</th>
                <th width="140">Celular</th>
                <th width="120">Cidade / UF</th>
                <th width="150" style="text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($responsaveis as $resp)
                <tr>
                    <td>
                        <a href="{{ route('financeiro.responsaveis.show', $resp->id) }}">
                            <strong>{{ $resp->nome }}</strong>
                        </a>
                    </td>
                    <td>{{ $resp->cpf_cnpj_formatado }}</td>
                    <td>{{ $resp->email }}</td>
                    <td>{{ $resp->telefone_celular }}</td>
                    <td>{{ $resp->cidade }} / {{ $resp->estado }}</td>
                    <td style="text-align: center;">
                        <a href="{{ route('financeiro.responsaveis.show', $resp->id) }}" class="btn btn-sm" title="Ver Detalhes e Alunos">
                            <i class="fa fa-eye"></i> Detalhes
                        </a>
                        <a href="{{ route('financeiro.responsaveis.edit', $resp->id) }}" class="btn btn-sm" title="Editar">
                            <i class="fa fa-pencil"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 25px; color: #777777;">
                        Nenhum responsável financeiro encontrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 15px;">
        {{ $responsaveis->appends(['busca' => $busca])->links() }}
    </div>
@endsection
