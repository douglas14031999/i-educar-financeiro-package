@extends('financeiro::layouts.app')

@section('title', 'Nova Conta a Pagar')
@section('breadcrumb', 'Contas a Pagar > Novo')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-plus-circle"></i> Cadastrar Nova Conta a Pagar
        </h2>
        <div>
            <a href="{{ route('financeiro.contas-pagar.index') }}" class="btn btn-sm">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <form method="post" action="{{ route('financeiro.contas-pagar.store') }}">
        @csrf
        <table class="tablecadastro" width="100%" border="0" cellpadding="3" cellspacing="0" style="margin-bottom: 25px;">
            <tbody>
                <tr>
                    <td class="formdktd" colspan="4" height="24">
                        <b><i class="fa fa-file-text-o"></i> Dados da Despesa</b>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd" width="180"><strong>Descrição da Despesa:</strong></td>
                    <td class="formmdtd" colspan="3">
                        <input type="text" name="descricao" value="{{ old('descricao') }}" placeholder="Ex: Energia Elétrica - Prédio Ensino Fundamental" class="form-input" style="width: 100%;" required>
                    </td>
                </tr>
                <tr>
                    <td class="formlttd"><strong>Fornecedor / Favorecido:</strong></td>
                    <td class="formlttd">
                        <input type="text" name="fornecedor_nome" value="{{ old('fornecedor_nome') }}" placeholder="Ex: Companhia Energética" class="form-input" style="width: 100%;">
                    </td>
                    <td class="formlttd" width="150"><strong>Valor (R$):</strong></td>
                    <td class="formlttd">
                        <input type="number" step="0.01" name="valor" value="{{ old('valor') }}" placeholder="0,00" class="form-input" style="width: 140px; font-weight: bold; color: #d9534f;" required>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd"><strong>Data Competência:</strong></td>
                    <td class="formmdtd">
                        <input type="date" name="data_competencia" value="{{ old('data_competencia', date('Y-m-01')) }}" class="form-input" style="width: 150px;" required>
                    </td>
                    <td class="formmdtd"><strong>Data Vencimento:</strong></td>
                    <td class="formmdtd">
                        <input type="date" name="data_vencimento" value="{{ old('data_vencimento', date('Y-m-d')) }}" class="form-input" style="width: 150px;" required>
                    </td>
                </tr>
                <tr>
                    <td class="formlttd"><strong>Plano de Contas:</strong></td>
                    <td class="formlttd">
                        <select name="plano_conta_id" class="form-input" style="width: 100%;" required>
                            <option value="">-- Selecione a Categoria de Despesa --</option>
                            @foreach($planosContas as $pc)
                                <option value="{{ $pc->id }}" {{ old('plano_conta_id') == $pc->id ? 'selected' : '' }}>
                                    {{ $pc->codigo }} - {{ $pc->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="formlttd"><strong>Centro de Custo:</strong></td>
                    <td class="formlttd">
                        <select name="centro_custo_id" class="form-input" style="width: 100%;">
                            <option value="">-- Opcional (Geral) --</option>
                            @foreach($centrosCusto as $cc)
                                <option value="{{ $cc->id }}" {{ old('centro_custo_id') == $cc->id ? 'selected' : '' }}>
                                    {{ $cc->codigo }} - {{ $cc->nome }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd"><strong>Documento Fiscal (NF/Recibo):</strong></td>
                    <td class="formmdtd" colspan="3">
                        <input type="text" name="documento_fiscal" value="{{ old('documento_fiscal') }}" placeholder="Ex: NF-e 45291" class="form-input" style="width: 250px;">
                    </td>
                </tr>
                <tr>
                    <td class="formlttd" valign="top"><strong>Observações:</strong></td>
                    <td class="formlttd" colspan="3">
                        <textarea name="observacoes" class="form-input" style="width: 100%; height: 60px;">{{ old('observacoes') }}</textarea>
                    </td>
                </tr>
                <tr>
                    <td class="formmdtd" colspan="4" style="text-align: right; padding: 10px;">
                        <button type="submit" class="botaosalvar">
                            <i class="fa fa-save"></i> Salvar Conta a Pagar
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection
