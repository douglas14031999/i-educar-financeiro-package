@extends('financeiro::layouts.app')

@section('title', 'Detalhes do Responsável Financeiro')
@section('breadcrumb', 'Detalhes do Responsável')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-user"></i> {{ $responsavel->nome }}
        </h2>
        <div>
            <a href="{{ route('financeiro.responsaveis.edit', $responsavel->id) }}" class="btn btn-sm">
                <i class="fa fa-pencil"></i> Editar Cadastro
            </a>
            <a href="{{ route('financeiro.responsaveis.index') }}" class="btn btn-sm" style="margin-left: 5px;">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Tabela de Dados Cadastrais -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 20px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-id-card-o"></i> Ficha Cadastral do Responsável</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" width="150"><strong>CPF/CNPJ:</strong></td>
                <td class="formmdtd">{{ $responsavel->cpf_cnpj_formatado }}</td>
                <td class="formmdtd" width="150"><strong>E-mail:</strong></td>
                <td class="formmdtd">{{ $responsavel->email }}</td>
            </tr>
            <tr>
                <td class="formlttd"><strong>Celular/WhatsApp:</strong></td>
                <td class="formlttd">{{ $responsavel->telefone_celular }}</td>
                <td class="formlttd"><strong>Telefone Fixo:</strong></td>
                <td class="formlttd">{{ $responsavel->telefone_fixo ?? '-' }}</td>
            </tr>
            <tr>
                <td class="formmdtd"><strong>Endereço:</strong></td>
                <td class="formmdtd" colspan="3">
                    {{ $responsavel->logradouro }}, nº {{ $responsavel->numero }}
                    @if($responsavel->complemento) ({{ $responsavel->complemento }}) @endif
                    - {{ $responsavel->bairro }}, {{ $responsavel->cidade }}/{{ $responsavel->estado }} - CEP: {{ $responsavel->cep }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Alunos Vinculados com Rateio Percentual -->
    <div style="background-color: #ffffff; border: 1px solid #cddce6; margin-bottom: 25px; border-radius: 3px;">
        <div style="padding: 10px 15px; background-color: #ccdce6; color: #47728f; font-weight: 700; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fa fa-graduation-cap"></i> Alunos Vinculados e Rateio Financeiro</span>
        </div>

        <table class="table-default" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 0;">
            <thead>
                <tr style="background-color: #dbe7ef; font-size: 13px;">
                    <th>ID Aluno</th>
                    <th>Parentesco</th>
                    <th width="150" style="text-align: right;">Rateio Financeiro (%)</th>
                    <th width="150" style="text-align: center;">Resp. Principal</th>
                    <th width="100" style="text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($responsavel->alunoVinculos as $vinculo)
                    <tr>
                        <td><strong>Aluno #{{ $vinculo->aluno_id }}</strong></td>
                        <td>{{ ucfirst($vinculo->parentesco) }}</td>
                        <td style="text-align: right; font-weight: 700; color: #47728f;">
                            {{ number_format($vinculo->percentual_rateio, 2, ',', '.') }}%
                        </td>
                        <td style="text-align: center;">
                            @if($vinculo->responsavel_principal)
                                <span class="badge badge-pago">Sim</span>
                            @else
                                <span class="badge badge-aberto">Secundário</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <form method="post" action="{{ route('financeiro.responsaveis.desvincular-aluno', [$responsavel->id, $vinculo->aluno_id]) }}" onsubmit="return confirm('Deseja remover o vínculo deste aluno?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm" style="color: #cc0000; padding: 2px 6px;">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px; color: #777;">
                            Nenhum aluno vinculado a este responsável financeiro até o momento.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Formulário para Adicionar Vínculo de Aluno -->
        <div style="padding: 15px; background-color: #f5f9fd; border-top: 1px solid #cddce6;">
            <h5 style="color: #47728f; margin: 0 0 10px 0; font-size: 14px; font-weight: 700;">
                <i class="fa fa-plus-circle"></i> Vincular Novo Aluno
            </h5>
            <form method="post" action="{{ route('financeiro.responsaveis.vincular-aluno', $responsavel->id) }}" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                @csrf
                <div>
                    <label class="form" style="font-size: 12px;">Código do Aluno (i-Educar) <span class="campo_obrigatorio">*</span></label>
                    <input type="number" name="aluno_id" required class="form-input" placeholder="Ex: 1042" style="max-width: 140px;">
                </div>

                <div>
                    <label class="form" style="font-size: 12px;">Parentesco <span class="campo_obrigatorio">*</span></label>
                    <select name="parentesco" class="form-input" style="max-width: 140px;">
                        <option value="pai">Pai</option>
                        <option value="mae">Mãe</option>
                        <option value="avo">Avô / Avó</option>
                        <option value="tutor">Tutor Legal</option>
                        <option value="empresa">Empresa</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div>
                    <label class="form" style="font-size: 12px;">% Rateio <span class="campo_obrigatorio">*</span></label>
                    <input type="number" name="percentual_rateio" step="0.01" min="1" max="100" value="100.00" required class="form-input" style="max-width: 110px;">
                </div>

                <div style="padding-bottom: 8px;">
                    <label style="font-size: 12px; color: #47728f; font-weight: 600;">
                        <input type="checkbox" name="responsavel_principal" value="1" checked> Responsável Principal
                    </label>
                </div>

                <div>
                    <button type="submit" class="btn btn-green">
                        <i class="fa fa-link"></i> Vincular Aluno
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
