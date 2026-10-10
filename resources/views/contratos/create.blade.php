@extends('financeiro::layouts.app')

@section('title', 'Novo Contrato Financeiro')
@section('breadcrumb', 'Cadastrar Contrato')

@section('financeiro-content')
    <form id="formcadastro" method="post" action="{{ route('financeiro.contratos.store') }}">
        @csrf
        <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0">
            <tbody>
                <tr>
                    <td class="formdktd" colspan="2" height="24">
                        <b><i class="fa fa-file-text-o"></i> Vinculação de Contrato Financeiro à Matrícula</b>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" width="220" valign="top">
                        <label for="matricula_id" class="form">Código da Matrícula (i-Educar) <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="number" name="matricula_id" id="matricula_id" value="{{ old('matricula_id') }}" required class="form-input" style="max-width: 200px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="aluno_id" class="form">Código do Aluno (i-Educar) <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="number" name="aluno_id" id="aluno_id" value="{{ old('aluno_id') }}" required class="form-input" style="max-width: 200px;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="ano_letivo" class="form">Ano Letivo <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <input type="number" name="ano_letivo" id="ano_letivo" value="{{ old('ano_letivo', $anoAtual) }}" required class="form-input" style="max-width: 150px;">
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="plano_id" class="form">Plano de Cobrança / Anuidade <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <select name="plano_id" id="plano_id" required class="form-input" style="max-width: 450px;">
                            <option value="">Selecione o plano...</option>
                            @foreach($planos as $plano)
                                <option value="{{ $plano->id }}" {{ old('plano_id') == $plano->id ? 'selected' : '' }}>
                                    {{ $plano->nome }} - R$ {{ number_format($plano->valor_total, 2, ',', '.') }} ({{ $plano->qtd_parcelas }}x)
                                </option>
                            @endforeach
                        </select>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="responsavel_financeiro_id" class="form">Responsável Financeiro Principal</label>
                    </td>
                    <td class="formmdtd">
                        <select name="responsavel_financeiro_id" id="responsavel_financeiro_id" class="form-input" style="max-width: 450px;">
                            <option value="">Utilizar responsáveis vinculados ao aluno</option>
                            @foreach($responsaveis as $resp)
                                <option value="{{ $resp->id }}" {{ old('responsavel_financeiro_id') == $resp->id ? 'selected' : '' }}>
                                    {{ $resp->nome }} ({{ $resp->cpf_cnpj_formatado }})
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size: 11px; color: #777; margin-top: 4px;">
                            Se o aluno já tiver responsáveis com percentual de rateio (ex: 50%/50%), o sistema dividirá os boletos automaticamente.
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="dia_vencimento" class="form">Dia do Vencimento das Parcelas <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="number" name="dia_vencimento" id="dia_vencimento" min="1" max="31" value="{{ old('dia_vencimento', 10) }}" required class="form-input" style="max-width: 120px;">
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="valor_total_descontos" class="form">Desconto Total Concedido (R$)</label>
                    </td>
                    <td class="formmdtd">
                        <input type="number" name="valor_total_descontos" id="valor_total_descontos" step="0.01" min="0" value="{{ old('valor_total_descontos', '0.00') }}" class="form-input" style="max-width: 200px;">
                        <span style="font-size: 11px; color: #777;">Abatimento negociado no valor global do contrato.</span>
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" colspan="2" style="padding: 15px;">
                        <button type="submit" class="btn btn-green botaosalvar">
                            <i class="fa fa-file-text"></i> Emitir Contrato e Gerar Parcelas
                        </button>
                        <a href="{{ route('financeiro.contratos.index') }}" class="btn geral-botao" style="margin-left: 8px;">
                            Cancelar
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection
