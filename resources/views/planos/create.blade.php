@extends('financeiro::layouts.app')

@section('title', 'Novo Plano de Cobrança')
@section('breadcrumb', 'Cadastrar Plano de Cobrança')

@section('financeiro-content')
    <form id="formcadastro" method="post" action="{{ route('financeiro.planos.store') }}">
        @csrf
        <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0">
            <tbody>
                <tr>
                    <td class="formdktd" colspan="2" height="24">
                        <b><i class="fa fa-list-alt"></i> Configuração do Plano de Cobrança Escolar</b>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" width="220" valign="top">
                        <label for="nome" class="form">Nome do Plano <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="nome" id="nome" value="{{ old('nome') }}" placeholder="Ex: Ensino Fundamental I - Anual 2026 em 12x" required class="form-input" style="max-width: 500px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="ano_letivo" class="form">Ano Letivo <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="number" name="ano_letivo" id="ano_letivo" value="{{ old('ano_letivo', date('Y')) }}" required class="form-input" style="max-width: 150px;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="escola_id" class="form">Código da Escola (i-Educar) <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="number" name="escola_id" id="escola_id" value="{{ old('escola_id', 1) }}" required class="form-input" style="max-width: 150px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="valor_total" class="form">Valor Total do Plano (R$) <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="number" name="valor_total" id="valor_total" step="0.01" min="1" value="{{ old('valor_total') }}" placeholder="Ex: 9600.00" required class="form-input" style="max-width: 200px;">
                        <span style="font-size: 11px; color: #777;">Valor integral da anuidade que será dividido nas parcelas.</span>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="qtd_parcelas" class="form">Quantidade de Parcelas <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <select name="qtd_parcelas" id="qtd_parcelas" class="form-input" style="max-width: 180px;">
                            <option value="12" {{ old('qtd_parcelas', 12) == 12 ? 'selected' : '' }}>12 parcelas (Padrão)</option>
                            <option value="11" {{ old('qtd_parcelas') == 11 ? 'selected' : '' }}>11 parcelas</option>
                            <option value="10" {{ old('qtd_parcelas') == 10 ? 'selected' : '' }}>10 parcelas</option>
                            <option value="6" {{ old('qtd_parcelas') == 6 ? 'selected' : '' }}>6 parcelas (Semestral)</option>
                            <option value="1" {{ old('qtd_parcelas') == 1 ? 'selected' : '' }}>1 parcela (À vista)</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="dia_vencimento_padrao" class="form">Dia do Vencimento Padrão <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="number" name="dia_vencimento_padrao" id="dia_vencimento_padrao" min="1" max="31" value="{{ old('dia_vencimento_padrao', 10) }}" required class="form-input" style="max-width: 120px;">
                        <span style="font-size: 11px; color: #777;">Dia do mês sugerido para os boletos/Pix (ex: 10).</span>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label class="form">Taxa de Matrícula Separada?</label>
                    </td>
                    <td class="formmdtd">
                        <label style="color: #47728f; font-weight: 600;">
                            <input type="checkbox" name="gerar_matricula_avulsa" value="1" {{ old('gerar_matricula_avulsa') ? 'checked' : '' }}>
                            Gerar parcela 00 de Matrícula (à parte da anuidade)
                        </label>
                        <div style="margin-top: 8px;">
                            <input type="number" name="valor_matricula" step="0.01" min="0" value="{{ old('valor_matricula') }}" placeholder="Valor da Matrícula (R$)" class="form-input" style="max-width: 200px;">
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="descricao" class="form">Descrição / Observações</label>
                    </td>
                    <td class="formlttd">
                        <textarea name="descricao" id="descricao" rows="3" class="form-input" style="max-width: 500px;">{{ old('descricao') }}</textarea>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" colspan="2" style="padding: 15px;">
                        <button type="submit" class="btn btn-green botaosalvar">
                            <i class="fa fa-calculator"></i> Gerar Plano e Parcelas
                        </button>
                        <a href="{{ route('financeiro.planos.index') }}" class="btn geral-botao" style="margin-left: 8px;">
                            Cancelar
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection
