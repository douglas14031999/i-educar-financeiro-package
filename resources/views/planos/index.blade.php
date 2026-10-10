@extends('financeiro::layouts.app')

@section('title', 'Planos de Cobrança')
@section('breadcrumb', 'Planos de Cobrança')

@section('financeiro-content')
    <!-- Cabeçalho Oficial do Módulo -->
    <div class="financeiro-header">
        <div class="financeiro-header-title">
            <h2>
                <i class="fa fa-list-alt" style="color: #47728f;"></i> Modelos de Planos de Pagamento e Anuidades
            </h2>
            <span class="financeiro-header-desc">
                Defina as regras de parcelamento, valores anuais e condições de cobrança por ano letivo
            </span>
        </div>
        <div class="financeiro-header-actions">
            <a href="{{ route('financeiro.planos.create') }}" class="btn-ieducar-action btn-ieducar-green">
                <i class="fa fa-plus-circle"></i> Novo Plano de Cobrança
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="financeiro-filter-box">
        <form method="get" action="{{ route('financeiro.planos.index') }}" class="financeiro-filter-form">
            <div class="filter-item">
                <label class="filter-label"><i class="fa fa-calendar"></i> Ano Letivo:</label>
                <input type="number" name="ano_letivo" value="{{ $anoLetivo }}" class="ieducar-input" style="width: 110px;">
            </div>

            <div class="filter-actions" style="display: flex; gap: 8px;">
                <button type="submit" class="btn-ieducar-action btn-ieducar-blue">
                    <i class="fa fa-filter"></i> Filtrar
                </button>
                @if(request('ano_letivo') && request('ano_letivo') != date('Y'))
                    <a href="{{ route('financeiro.planos.index') }}" class="btn-ieducar-action btn-ieducar-outline">
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
                <i class="fa fa-table"></i> Planos Cadastrados ({{ $planos->total() }})
            </span>
            <span class="box-header-info">
                Total de registros: {{ $planos->total() }}
            </span>
        </div>
        <div class="financeiro-table-responsive">
            <table class="financeiro-table">
                <thead>
                    <tr>
                        <th>Nome do Plano</th>
                        <th width="110" style="text-align: center;">Ano Letivo</th>
                        <th width="110" style="text-align: center;">Parcelas</th>
                        <th width="140" style="text-align: right;">Valor Anual</th>
                        <th width="140" style="text-align: right;">Média Mensal</th>
                        <th width="120" style="text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($planos as $plano)
                        <tr>
                            <td>
                                <a href="{{ route('financeiro.planos.show', $plano->id) }}" style="color: #47728f; font-weight: 700; text-decoration: underline;">
                                    {{ $plano->nome }}
                                </a>
                                @if($plano->descricao)
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">{{ $plano->descricao }}</div>
                                @endif
                            </td>
                            <td align="center"><strong>{{ $plano->ano_letivo }}</strong></td>
                            <td align="center"><strong>{{ $plano->qtd_parcelas }}x</strong></td>
                            <td align="right" class="col-valor">
                                R$ {{ number_format($plano->valor_total, 2, ',', '.') }}
                            </td>
                            <td align="right" style="color: #47728f;">
                                R$ {{ number_format($plano->qtd_parcelas > 0 ? $plano->valor_total / $plano->qtd_parcelas : 0, 2, ',', '.') }}
                            </td>
                            <td align="center">
                                <div class="table-actions-group">
                                    <a href="{{ route('financeiro.planos.show', $plano->id) }}" class="btn-table-action btn-action-blue" title="Ver Parcelas">
                                        <i class="fa fa-eye"></i> Detalhes
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="financeiro-empty-state">
                                <i class="fa fa-folder-open-o empty-icon"></i>
                                <div class="empty-title">Nenhum plano de cobrança cadastrado para o ano selecionado.</div>
                                <div class="empty-desc">Crie um plano para vincular às matrículas dos alunos.</div>
                                <div style="margin-top: 12px;">
                                    <a href="{{ route('financeiro.planos.create') }}" class="btn-ieducar-action btn-ieducar-green">
                                        <i class="fa fa-plus-circle"></i> Novo Plano de Cobrança
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($planos->hasPages())
        <div class="financeiro-pagination-wrapper">
            {{ $planos->appends(['ano_letivo' => $anoLetivo])->links() }}
        </div>
    @endif
@endsection
