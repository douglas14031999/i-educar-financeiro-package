@extends('financeiro::layouts.app')

@section('title', 'Contratos Financeiros')
@section('breadcrumb', 'Contratos Financeiros')

@section('financeiro-content')
    <!-- Cabeçalho -->
    <div class="financeiro-header">
        <div class="financeiro-header-title">
            <h2>
                <i class="fa fa-file-text-o" style="color: #47728f;"></i> Gestão de Contratos Financeiros
            </h2>
            <span class="financeiro-header-desc">
                Administração de contratos, planos e matrículas vinculadas
            </span>
        </div>
        <div class="financeiro-header-actions">
            <a href="{{ route('financeiro.contratos.create') }}" class="btn-ieducar-action btn-ieducar-green">
                <i class="fa fa-plus-circle"></i> Novo Contrato por Matrícula
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="financeiro-filter-box">
        <form method="get" action="{{ route('financeiro.contratos.index') }}" class="financeiro-filter-form">
            <div class="filter-item">
                <label class="filter-label">Ano Letivo:</label>
                <input type="number" name="ano_letivo" value="{{ $anoLetivo }}" class="ieducar-input" style="max-width: 100px;">
            </div>

            <div class="filter-item">
                <label class="filter-label">Status:</label>
                <select name="status" class="ieducar-select" style="min-width: 150px;">
                    <option value="">Todos</option>
                    <option value="ativo" {{ $status === 'ativo' ? 'selected' : '' }}>Ativos</option>
                    <option value="cancelado" {{ $status === 'cancelado' ? 'selected' : '' }}>Cancelados</option>
                    <option value="quitado" {{ $status === 'quitado' ? 'selected' : '' }}>Quitados</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-ieducar-action btn-ieducar-blue">
                    <i class="fa fa-filter"></i> Filtrar
                </button>
                @if(request()->anyFilled(['ano_letivo', 'status']))
                    <a href="{{ route('financeiro.contratos.index') }}" class="btn-ieducar-action btn-ieducar-outline">
                        Limpar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabela de Listagem -->
    <div class="financeiro-table-box">
        <div class="financeiro-box-header">
            <span>
                <i class="fa fa-list"></i> Contratos Registrados ({{ $contratos->total() }})
            </span>
            <span class="box-header-info">Total de registros: {{ $contratos->total() }}</span>
        </div>
        <div class="financeiro-table-responsive">
            <table class="financeiro-table">
                <thead>
                    <tr>
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
                            <td align="right" class="col-valor">
                                R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}
                            </td>
                            <td align="center">
                                @php
                                    $badgeClass = 'badge-pendente';
                                    if ($contrato->status === 'ativo') $badgeClass = 'badge-pago';
                                    elseif ($contrato->status === 'cancelado') $badgeClass = 'badge-vencido';
                                    elseif ($contrato->status === 'quitado') $badgeClass = 'badge-pago';
                                @endphp
                                <span class="badge-status {{ $badgeClass }}">
                                    {{ ucfirst($contrato->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="financeiro-empty-state">
                                <i class="fa fa-folder-open-o empty-icon"></i>
                                <div class="empty-title">Nenhum contrato financeiro encontrado.</div>
                                <div class="empty-desc">Utilize os filtros acima para refinar sua busca ou cadastre um novo registro.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 15px;">
        {{ $contratos->appends(['ano_letivo' => $anoLetivo, 'status' => $status])->links() }}
    </div>
@endsection
