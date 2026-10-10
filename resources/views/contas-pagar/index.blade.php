@extends('financeiro::layouts.app')

@section('title', 'Contas a Pagar')
@section('breadcrumb', 'Contas a Pagar')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div>
            <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-credit-card"></i> Gestão de Contas a Pagar e Despesas
            </h2>
            <span style="font-size: 12px; color: #666;">
                Total a liquidar em aberto: <strong style="color: #d9534f;">R$ {{ number_format($totalAberto, 2, ',', '.') }}</strong>
            </span>
        </div>
        <div>
            <a href="{{ route('financeiro.contas-pagar.create') }}" class="btn btn-sm btn-green">
                <i class="fa fa-plus"></i> Nova Conta a Pagar
            </a>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="3" cellspacing="0" style="margin-bottom: 20px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-filter"></i> Filtrar Despesas</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" colspan="4" style="padding: 10px;">
                    <form method="get" action="{{ route('financeiro.contas-pagar.index') }}" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        <div>
                            <label style="font-size: 12px; font-weight: bold; margin: 0;">Status:</label>
                            <select name="status" class="form-input" style="width: 130px;">
                                <option value="a_pagar" {{ $status === 'a_pagar' ? 'selected' : '' }}>A Pagar</option>
                                <option value="pago" {{ $status === 'pago' ? 'selected' : '' }}>Pagas</option>
                                <option value="todos" {{ $status === 'todos' ? 'selected' : '' }}>Todas</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 12px; font-weight: bold; margin: 0;">Buscar Descrição ou Fornecedor:</label>
                            <input type="text" name="busca" value="{{ $busca }}" placeholder="Ex: Energia, Material Escolar, Limpeza..." class="form-input" style="width: 100%;">
                        </div>
                        <div style="align-self: flex-end;">
                            <button type="submit" class="botaosalvar">
                                <i class="fa fa-search"></i> Filtrar
                            </button>
                            @if($busca || $status !== 'a_pagar')
                                <a href="{{ route('financeiro.contas-pagar.index') }}" class="btn btn-sm" style="margin-left: 5px;">Limpar</a>
                            @endif
                        </div>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Listagem de Contas a Pagar -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 25px;">
        <thead>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
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
                    <td style="text-align: right; font-weight: bold; color: #d9534f;">
                        R$ {{ number_format($d->valor, 2, ',', '.') }}
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $d->status === 'pago' ? 'pago' : 'vencido' }}">
                            {{ $d->status === 'pago' ? 'Pago' : 'A Pagar' }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        @if($d->status === 'a_pagar')
                            <button type="button" class="btn btn-sm btn-green" onclick="document.getElementById('modal-liquidar-{{ $d->id }}').style.display='block';" title="Liquidar Despesa">
                                <i class="fa fa-check"></i> Pagar
                            </button>
                            <a href="{{ route('financeiro.contas-pagar.edit', $d->id) }}" class="btn btn-sm" title="Editar">
                                <i class="fa fa-pencil"></i>
                            </a>
                        @else
                            <span style="font-size: 11px; color: #438944;" title="Pago em {{ \Carbon\Carbon::parse($d->data_pagamento)->format('d/m/Y') }}">
                                <i class="fa fa-check-circle"></i> Liquidado
                            </span>
                        @endif
                    </td>
                </tr>

                <!-- Modal / Linha de Baixa Direta -->
                @if($d->status === 'a_pagar')
                    <div id="modal-liquidar-{{ $d->id }}" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
                        <div style="background: #fff; width: 450px; margin: 100px auto; padding: 20px; border-top: 4px solid #438944; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                            <h3 style="margin-top: 0; color: #47728f; font-size: 16px;">
                                <i class="fa fa-money"></i> Liquidar Despesa #{{ $d->id }}
                            </h3>
                            <p style="font-size: 13px; color: #333;">
                                <strong>{{ $d->descricao }}</strong> - R$ {{ number_format($d->valor, 2, ',', '.') }}
                            </p>
                            <form method="post" action="{{ route('financeiro.contas-pagar.liquidar', $d->id) }}">
                                @csrf
                                <div style="margin-bottom: 12px;">
                                    <label style="font-size: 12px; font-weight: bold; display: block;">Data de Pagamento:</label>
                                    <input type="date" name="data_pagamento" value="{{ date('Y-m-d') }}" class="form-input" style="width: 100%;" required>
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label style="font-size: 12px; font-weight: bold; display: block;">Conta Bancária de Saída:</label>
                                    <select name="conta_bancaria_id" class="form-input" style="width: 100%;" required>
                                        <option value="1">Caixa Geral da Escola</option>
                                        <option value="2">Banco do Brasil - Conta Operacional</option>
                                    </select>
                                </div>
                                <div style="text-align: right;">
                                    <button type="button" class="btn btn-sm" onclick="document.getElementById('modal-liquidar-{{ $d->id }}').style.display='none';">Cancelar</button>
                                    <button type="submit" class="botaosalvar" style="margin-left: 8px;">Confirmar Liquidação</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 15px; color: #777;">
                        Nenhuma conta a pagar encontrada.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 15px;">
        {{ $despesas->appends(request()->query())->links() }}
    </div>
@endsection
