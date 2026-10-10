@extends('financeiro::layouts.app')

@section('title', 'Novo Responsável Financeiro')
@section('breadcrumb', 'Cadastrar Responsável')

@section('financeiro-content')
    <form id="formcadastro" method="post" action="{{ route('financeiro.responsaveis.store') }}">
        @csrf
        <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0">
            <tbody>
                <tr>
                    <td class="formdktd" colspan="2" height="24">
                        <b><i class="fa fa-user"></i> Dados Pessoais do Responsável Financeiro</b>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" width="220" valign="top">
                        <label for="nome" class="form">Nome Completo <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="nome" id="nome" value="{{ old('nome') }}" required class="form-input" style="max-width: 500px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="cpf_cnpj" class="form">CPF / CNPJ <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="text" name="cpf_cnpj" id="cpf_cnpj" value="{{ old('cpf_cnpj') }}" placeholder="000.000.000-00" required class="form-input" style="max-width: 250px;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="rg_ie" class="form">RG / Inscrição Estadual</label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="rg_ie" id="rg_ie" value="{{ old('rg_ie') }}" class="form-input" style="max-width: 250px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="email" class="form">E-mail Principal <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required class="form-input" style="max-width: 400px;">
                        <span style="font-size: 11px; color: #777;">Utilizado para envio dos boletos, Pix e notificações da régua.</span>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="telefone_celular" class="form">Celular / WhatsApp <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="telefone_celular" id="telefone_celular" value="{{ old('telefone_celular') }}" placeholder="(00) 00000-0000" required class="form-input" style="max-width: 250px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="telefone_fixo" class="form">Telefone Fixo</label>
                    </td>
                    <td class="formlttd">
                        <input type="text" name="telefone_fixo" id="telefone_fixo" value="{{ old('telefone_fixo') }}" class="form-input" style="max-width: 250px;">
                    </td>
                </tr>

                <tr>
                    <td class="formdktd" colspan="2" height="24">
                        <b><i class="fa fa-map-marker"></i> Endereço de Cobrança e Emissão de Documentos</b>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="cep" class="form">CEP <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="cep" id="cep" value="{{ old('cep') }}" placeholder="00000-000" required class="form-input" style="max-width: 150px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="logradouro" class="form">Logradouro / Rua <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="text" name="logradouro" id="logradouro" value="{{ old('logradouro') }}" required class="form-input" style="max-width: 500px;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="numero" class="form">Número <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="numero" id="numero" value="{{ old('numero') }}" required class="form-input" style="max-width: 120px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="complemento" class="form">Complemento</label>
                    </td>
                    <td class="formlttd">
                        <input type="text" name="complemento" id="complemento" value="{{ old('complemento') }}" class="form-input" style="max-width: 300px;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="bairro" class="form">Bairro <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="bairro" id="bairro" value="{{ old('bairro') }}" required class="form-input" style="max-width: 300px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="cidade" class="form">Cidade / Estado <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="text" name="cidade" id="cidade" value="{{ old('cidade') }}" required class="form-input" style="max-width: 300px; display: inline-block;">
                        <input type="text" name="estado" id="estado" value="{{ old('estado') }}" placeholder="UF" maxlength="2" required class="form-input" style="max-width: 60px; display: inline-block; margin-left: 5px; text-transform: uppercase;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="observacoes" class="form">Observações Gerais</label>
                    </td>
                    <td class="formmdtd">
                        <textarea name="observacoes" id="observacoes" rows="3" class="form-input" style="max-width: 600px;">{{ old('observacoes') }}</textarea>
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" colspan="2" style="padding: 15px;">
                        <button type="submit" class="btn btn-green botaosalvar">
                            <i class="fa fa-check"></i> Salvar Responsável
                        </button>
                        <a href="{{ route('financeiro.responsaveis.index') }}" class="btn geral-botao" style="margin-left: 8px;">
                            Cancelar
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection
