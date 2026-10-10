@extends('financeiro::layouts.app')

@section('title', 'Editar Conta a Pagar #' . $despesa->id)
@section('breadcrumb', 'Contas a Pagar > Editar #' . $despesa->id)

@section('financeiro-content')
    <!-- Cabeçalho -->
    <div class="financeiro-header">
        <div class="financeiro-header-title">
            <h2>
                <i class="fa fa-pencil" style="color: #47728f;"></i> Editar Conta a Pagar #{{ $despesa->id }}
            </h2>
            <span class="financeiro-header-desc">
                Atualização de dados da despesa ou conta
            </span>
        </div>
        <div class="financeiro-header-actions">
            <a href="{{ route('financeiro.contas-pagar.index') }}" class="btn-ieducar-action btn-ieducar-outline">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <form id="formcadastro" method="post" action="{{ route('financeiro.contas-pagar.update', $despesa->id) }}">
        @csrf
        @method('PUT')
        <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0">
            <tbody>
                <tr>
                    <td class="formdktd" colspan="4" height="28">
                        <b><i class="fa fa-file-text-o"></i> Dados da Despesa</b>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd" width="180"><strong>Descrição da Despesa:</strong></td>
                    <td class="formmdtd" colspan="3">
                        <input type="text" name="descricao" value="{{ old('descricao', $despesa->descricao) }}" class="ieducar-input" style="width: 100%; max-width: 600px;" required>
                    </td>
                </tr>
                <tr>
                    <td class="formlttd"><strong>Fornecedor / Favorecido:</strong></td>
                    <td class="formlttd">
                        <input type="text" name="fornecedor_nome" value="{{ old('fornecedor_nome', $despesa->fornecedor_nome) }}" class="ieducar-input" style="width: 100%; max-width: 300px;">
                    </td>
                    <td class="formlttd" width="150"><strong>Valor (R$):</strong></td>
                    <td class="formlttd">
                        <input type="number" step="0.01" name="valor" value="{{ old('valor', $despesa->valor) }}" class="ieducar-input" style="width: 140px; font-weight: bold; color: #d9534f;" required>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd"><strong>Data Competência:</strong></td>
                    <td class="formmdtd">
                        <input type="date" name="data_competencia" value="{{ old('data_competencia', $despesa->data_competencia ? \Carbon\Carbon::parse($despesa->data_competencia)->format('Y-m-d') : '') }}" class="ieducar-input" style="width: 150px;" required>
                    </td>
                    <td class="formmdtd"><strong>Data Vencimento:</strong></td>
                    <td class="formmdtd">
                        <input type="date" name="data_vencimento" value="{{ old('data_vencimento', $despesa->data_vencimento ? \Carbon\Carbon::parse($despesa->data_vencimento)->format('Y-m-d') : '') }}" class="ieducar-input" style="width: 150px;" required>
                    </td>
                </tr>
                <tr>
                    <td class="formlttd"><strong>Plano de Contas:</strong></td>
                    <td class="formlttd">
                        <select name="plano_conta_id" class="ieducar-select" style="width: 100%; max-width: 300px;" required>
                            <option value="">-- Selecione a Categoria de Despesa --</option>
                            @foreach($planosContas as $pc)
                                <option value="{{ $pc->id }}" {{ old('plano_conta_id', $despesa->plano_conta_id) == $pc->id ? 'selected' : '' }}>
                                    {{ $pc->codigo }} - {{ $pc->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="formlttd"><strong>Centro de Custo:</strong></td>
                    <td class="formlttd">
                        <select name="centro_custo_id" class="ieducar-select" style="width: 100%; max-width: 300px;">
                            <option value="">-- Opcional (Geral) --</option>
                            @foreach($centrosCusto as $cc)
                                <option value="{{ $cc->id }}" {{ old('centro_custo_id', $despesa->centro_custo_id) == $cc->id ? 'selected' : '' }}>
                                    {{ $cc->codigo }} - {{ $cc->nome }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd"><strong>Documento Fiscal (NF/Recibo):</strong></td>
                    <td class="formmdtd" colspan="3">
                        <input type="text" name="documento_fiscal" value="{{ old('documento_fiscal', $despesa->documento_fiscal) }}" class="ieducar-input" style="width: 250px;">
                    </td>
                </tr>
                <tr>
                    <td class="formlttd" valign="top"><strong>Observações:</strong></td>
                    <td class="formlttd" colspan="3">
                        <textarea name="observacoes" class="ieducar-input" style="width: 100%; max-width: 600px; height: 60px;">{{ old('observacoes', $despesa->observacoes) }}</textarea>
                    </td>
                </tr>
                <tr>
                    <td class="formlttd" colspan="4" style="padding: 16px;">
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn-ieducar-action btn-ieducar-green">
                                <i class="fa fa-save"></i> Atualizar Conta a Pagar
                            </button>
                            <a href="{{ route('financeiro.contas-pagar.index') }}" class="btn-ieducar-action btn-ieducar-outline">
                                Cancelar
                            </a>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection
