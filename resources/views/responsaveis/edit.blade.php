@extends('financeiro::layouts.app')

@section('title', 'Editar Responsável Financeiro')
@section('breadcrumb', 'Editar Responsável')

@section('financeiro-content')
    <form id="formcadastro" method="post" action="{{ route('financeiro.responsaveis.update', $responsavel->id) }}">
        @csrf
        @method('PUT')
        <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0">
            <tbody>
                <tr>
                    <td class="formdktd" colspan="2" height="24">
                        <b><i class="fa fa-user"></i> Alterar Dados do Responsável Financeiro</b>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" width="220" valign="top">
                        <label for="nome" class="form">Nome Completo <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="nome" id="nome" value="{{ old('nome', $responsavel->nome) }}" required class="form-input" style="max-width: 500px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="cpf_cnpj" class="form">CPF / CNPJ <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="text" name="cpf_cnpj" id="cpf_cnpj" value="{{ old('cpf_cnpj', $responsavel->cpf_cnpj_formatado) }}" required class="form-input" style="max-width: 250px;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="rg_ie" class="form">RG / Inscrição Estadual</label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="rg_ie" id="rg_ie" value="{{ old('rg_ie', $responsavel->rg_ie) }}" class="form-input" style="max-width: 250px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="email" class="form">E-mail Principal <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="email" name="email" id="email" value="{{ old('email', $responsavel->email) }}" required class="form-input" style="max-width: 400px;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="telefone_celular" class="form">Celular / WhatsApp <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="telefone_celular" id="telefone_celular" value="{{ old('telefone_celular', $responsavel->telefone_celular) }}" required class="form-input" style="max-width: 250px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="telefone_fixo" class="form">Telefone Fixo</label>
                    </td>
                    <td class="formlttd">
                        <input type="text" name="telefone_fixo" id="telefone_fixo" value="{{ old('telefone_fixo', $responsavel->telefone_fixo) }}" class="form-input" style="max-width: 250px;">
                    </td>
                </tr>

                <tr>
                    <td class="formdktd" colspan="2" height="24">
                        <b><i class="fa fa-map-marker"></i> Endereço</b>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="cep" class="form">CEP <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="cep" id="cep" value="{{ old('cep', $responsavel->cep) }}" required class="form-input" style="max-width: 150px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="logradouro" class="form">Logradouro <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="text" name="logradouro" id="logradouro" value="{{ old('logradouro', $responsavel->logradouro) }}" required class="form-input" style="max-width: 500px;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="numero" class="form">Número <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="numero" id="numero" value="{{ old('numero', $responsavel->numero) }}" required class="form-input" style="max-width: 120px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="complemento" class="form">Complemento</label>
                    </td>
                    <td class="formlttd">
                        <input type="text" name="complemento" id="complemento" value="{{ old('complemento', $responsavel->complemento) }}" class="form-input" style="max-width: 300px;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="bairro" class="form">Bairro <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="bairro" id="bairro" value="{{ old('bairro', $responsavel->bairro) }}" required class="form-input" style="max-width: 300px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="cidade" class="form">Cidade / Estado <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="text" name="cidade" id="cidade" value="{{ old('cidade', $responsavel->cidade) }}" required class="form-input" style="max-width: 300px; display: inline-block;">
                        <input type="text" name="estado" id="estado" value="{{ old('estado', $responsavel->estado) }}" maxlength="2" required class="form-input" style="max-width: 60px; display: inline-block; margin-left: 5px; text-transform: uppercase;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="observacoes" class="form">Observações</label>
                    </td>
                    <td class="formmdtd">
                        <textarea name="observacoes" id="observacoes" rows="3" class="form-input" style="max-width: 600px;">{{ old('observacoes', $responsavel->observacoes) }}</textarea>
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" colspan="2" style="padding: 15px;">
                        <button type="submit" class="btn btn-green botaosalvar">
                            <i class="fa fa-save"></i> Atualizar Responsável
                        </button>
                        <a href="{{ route('financeiro.responsaveis.show', $responsavel->id) }}" class="btn geral-botao" style="margin-left: 8px;">
                            Cancelar
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection
