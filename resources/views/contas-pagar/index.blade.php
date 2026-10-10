@extends('financeiro::layouts.app')

@section('title', 'Contas a Pagar')
@section('breadcrumb', 'Contas a Pagar')

@section('financeiro-content')
    <!-- Cabeçalho -->
    <div class="financeiro-header">
        <div class="financeiro-header-title">
            <h2>
                <i class="fa fa-credit-card" style="color: #47728f;"></i> Gestão de Contas a Pagar e Despesas
            </h2>
            <span class="financeiro-header-desc">
                Total a liquidar em aberto: <strong style="color: #d9534f;">R$ {{ number_format($totalAberto, 2, ',', '.') }}</strong>
            </span>
        </div>
        <div class="financeiro-header-actions">
            <a href="{{ route('financeiro.contas-pagar.create') }}" class="btn-ieducar-action btn-ieducar-green">
                <i class="fa fa-plus-circle"></i> Nova Conta a Pagar
            </a>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <div class="financeiro-filter-box">
        <form method="get" action="{{ route('financeiro.contas-pagar.index') }}" class="financeiro-filter-form">
            <div class="filter-item">
                <label class="filter-label">Buscar Descrição ou Fornecedor:</label>
                <input type="text" name="busca" value="{{ $busca }}" placeholder="Ex: Energia, Material Escolar, Limpeza..." class="ieducar-input" style="min-width: 350px;">
            </div>

            <div class="filter-item">
                <label class="filter-label">Status:</label>
                <select name="status" class="ieducar-select" style="min-width: 130px;">
                    <option value="a_pagar" {{ $status === 'a_pagar' ? 'selected' : '' }}>A Pagar</option>
                    <option value="pago" {{ $status === 'pago' ? 'selected' : '' }}>Pagas</option>
                    <option value="todos" {{ $status === 'todos' ? 'selected' : '' }}>Todas</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-ieducar-action btn-ieducar-blue">
                    <i class="fa fa-filter"></i> Filtrar
                </button>
                @if($busca || $status !== 'a_pagar')
                    <a href="{{ route('financeiro.contas-pagar.index') }}" class="btn-ieducar-action btn-ieducar-outline">
                        Limpar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Listagem de Contas a Pagar -->
    <div class="financeiro-table-box">
        <div class="financeiro-box-header">
            <span>
                <i class="fa fa-list"></i> Despesas Cadastradas ({{ $despesas->total() }})
            </span>
            <span class="box-header-info">Total de registros: {{ $despesas->total() }}</span>
        </div>
        <div class="financeiro-table-responsive">
            <table class="financeiro-table">
                <thead>
                    <tr>
                        <th width="70">ID</th>
                        <th width="100">Vencimento</th>
                        <th>Descrição / Fornecedor</th>
                        <th width="180">Categoria Contábil</th>
                        <th width="130" style="text-align: right;">Valor</th>
                        <th width="90" style="text-align: center;">Status</th>
                        <th width="150" style="text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($despesas as $d)
                        <tr>
                            <td>#{{ $d->id }}</td>
                            <td style="font-weight: bold; color: {{ $d->status === 'a_pagar' && \Carbon\Carbon::parse($d->data_vencimento)->isPast() ? '#cc0000' : '#333' }};">
                                {{ \Carbon\Carbon::parse($d->data_vencimento)->format('d/m/Y') }}
                            </td>
                            <td>
                                <strong>{{ $d->descricao }}</strong>
                                @if($d->fornecedor_nome)
                                    <br><span style="font-size: 11px; color: #666;">Fornecedor: {{ $d->fornecedor_nome }}</span>
                                @endif
                                @if($d->documento_fiscal)
                                    <span style="font-size: 11px; color: #888;">(NF: {{ $d->documento_fiscal }})</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-size: 12px; color: #47728f;">
                                    {{ $d->planoConta->descricao ?? '-' }}
                                </span>
                                @if($d->centroCusto)
                                    <br><span style="font-size: 11px; color: #888;">C. Custo: {{ $d->centroCusto->nome }}</span>
                                @endif
                            </td>
                            <td align="right" class="col-valor" style="color: #d9534f;">
                                R$ {{ number_format($d->valor, 2, ',', '.') }}
                            </td>
                            <td align="center">
                                <span class="badge-status badge-{{ $d->status === 'pago' ? 'pago' : 'vencido' }}">
                                    {{ $d->status === 'pago' ? 'Pago' : 'A Pagar' }}
                                </span>
                            </td>
                            <td align="center">
                                <div class="table-actions-group">
                                    @if($d->status === 'a_pagar')
                                        <button type="button" class="btn-table-action btn-action-green" onclick="document.getElementById('modal-liquidar-{{ $d->id }}').style.display='block';" title="Liquidar Despesa">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        <a href="{{ route('financeiro.contas-pagar.edit', $d->id) }}" class="btn-table-action btn-action-amber" title="Editar">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    @else
                                        <span style="font-size: 11px; color: #438944; display: inline-flex; align-items: center; gap: 4px;" title="Pago em {{ \Carbon\Carbon::parse($d->data_pagamento)->format('d/m/Y') }}">
                                            <i class="fa fa-check-circle"></i> Liquidado
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Modal / Linha de Baixa Direta -->
                        @if($d->status === 'a_pagar')
                            <div id="modal-liquidar-{{ $d->id }}" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
                                <div style="background: #fff; width: 450px; margin: 100px auto; padding: 20px; border-top: 4px solid #438944; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); text-align: left;">
                                    <h3 style="margin-top: 0; color: #47728f; font-size: 16px;">
                                        <i class="fa fa-money"></i> Liquidar Despesa #{{ $d->id }}
                                    </h3>
                                    <p style="font-size: 13px; color: #333;">
                                        <strong>{{ $d->descricao }}</strong> - R$ {{ number_format($d->valor, 2, ',', '.') }}
                                    </p>
                                    <form method="post" action="{{ route('financeiro.contas-pagar.liquidar', $d->id) }}">
                                        @csrf
                                        <div style="margin-bottom: 12px;">
                                            <label style="font-size: 12px; font-weight: bold; display: block; margin-bottom: 4px;">Data de Pagamento:</label>
                                            <input type="date" name="data_pagamento" value="{{ date('Y-m-d') }}" class="ieducar-input" style="width: 100%;" required>
                                        </div>
                                        <div style="margin-bottom: 15px;">
                                            <label style="font-size: 12px; font-weight: bold; display: block; margin-bottom: 4px;">Conta Bancária de Saída:</label>
                                            <select name="conta_bancaria_id" class="ieducar-select" style="width: 100%;" required>
                                                <option value="1">Caixa Geral da Escola</option>
                                                <option value="2">Banco do Brasil - Conta Operacional</option>
                                            </select>
                                        </div>
                                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                            <button type="button" class="btn-ieducar-action btn-ieducar-outline" onclick="document.getElementById('modal-liquidar-{{ $d->id }}').style.display='none';">Cancelar</button>
                                            <button type="submit" class="btn-ieducar-action btn-ieducar-green"><i class="fa fa-check"></i> Confirmar Liquidação</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="financeiro-empty-state">
                                <i class="fa fa-folder-open-o empty-icon"></i>
                                <div class="empty-title">Nenhuma conta a pagar encontrada.</div>
                                <div class="empty-desc">Utilize os filtros acima ou registre uma nova despesa.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 15px;">
        {{ $despesas->appends(request()->query())->links() }}
    </div>
@endsection
