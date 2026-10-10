@extends('financeiro::layouts.app')

@section('title', 'Responsáveis Financeiros')
@section('breadcrumb', 'Responsáveis Financeiros')

@section('financeiro-content')
    <!-- Cabeçalho Oficial do Módulo -->
    <div class="financeiro-header">
        <div class="financeiro-header-title">
            <h2>
                <i class="fa fa-users" style="color: #47728f;"></i> Cadastro de Responsáveis Financeiros
            </h2>
            <span class="financeiro-header-desc">
                Gestão dos responsáveis legais e financeiros vinculados aos alunos da instituição
            </span>
        </div>
        <div class="financeiro-header-actions">
            <a href="{{ route('financeiro.responsaveis.create') }}" class="btn-ieducar-action btn-ieducar-green">
                <i class="fa fa-plus-circle"></i> Novo Responsável
            </a>
        </div>
    </div>

    <!-- Barra de Filtro / Busca -->
    <div class="financeiro-filter-box">
        <form method="get" action="{{ route('financeiro.responsaveis.index') }}" class="financeiro-filter-form">
            <div class="filter-item">
                <label class="filter-label"><i class="fa fa-search"></i> Buscar:</label>
                <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar por Nome, CPF/CNPJ ou E-mail..." class="ieducar-input" style="min-width: 320px;">
            </div>

            <div class="filter-actions" style="display: flex; gap: 8px;">
                <button type="submit" class="btn-ieducar-action btn-ieducar-blue">
                    <i class="fa fa-search"></i> Buscar
                </button>
                @if($busca)
                    <a href="{{ route('financeiro.responsaveis.index') }}" class="btn-ieducar-action btn-ieducar-outline">
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
                <i class="fa fa-list"></i> Responsáveis Cadastrados ({{ $responsaveis->total() }})
            </span>
            <span class="box-header-info">
                Total de registros: {{ $responsaveis->total() }}
            </span>
        </div>
        <div class="financeiro-table-responsive">
            <table class="financeiro-table">
                <thead>
                    <tr>
                        <th>Nome do Responsável</th>
                        <th width="150">CPF / CNPJ</th>
                        <th width="220">E-mail</th>
                        <th width="140">Celular</th>
                        <th width="140">Cidade / UF</th>
                        <th width="140" style="text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($responsaveis as $resp)
                        <tr>
                            <td>
                                <a href="{{ route('financeiro.responsaveis.show', $resp->id) }}" style="color: #47728f; font-weight: 700; text-decoration: underline;">
                                    {{ $resp->nome }}
                                </a>
                            </td>
                            <td>{{ $resp->cpf_cnpj_formatado }}</td>
                            <td>{{ $resp->email ?? '-' }}</td>
                            <td>{{ $resp->telefone_celular ?? '-' }}</td>
                            <td>{{ $resp->cidade ? $resp->cidade . ' / ' . $resp->estado : '-' }}</td>
                            <td align="center">
                                <div class="table-actions-group">
                                    <a href="{{ route('financeiro.responsaveis.show', $resp->id) }}" class="btn-table-action btn-action-blue" title="Ver Detalhes e Alunos">
                                        <i class="fa fa-eye"></i> Detalhes
                                    </a>
                                    <a href="{{ route('financeiro.responsaveis.edit', $resp->id) }}" class="btn-table-action btn-action-amber" title="Editar">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="financeiro-empty-state">
                                <i class="fa fa-folder-open-o empty-icon"></i>
                                <div class="empty-title">Nenhum responsável financeiro encontrado.</div>
                                <div class="empty-desc">Cadastre o primeiro responsável para emitir contratos e planos de cobrança.</div>
                                <div style="margin-top: 12px;">
                                    <a href="{{ route('financeiro.responsaveis.create') }}" class="btn-ieducar-action btn-ieducar-green">
                                        <i class="fa fa-plus-circle"></i> Cadastrar Responsável Agora
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($responsaveis->hasPages())
        <div class="financeiro-pagination-wrapper">
            {{ $responsaveis->appends(['busca' => $busca])->links() }}
        </div>
    @endif
@endsection
