@extends('financeiro::layouts.app')

@section('title', 'Baixa de Balcão (Caixa / Secretaria)')
@section('breadcrumb', 'Baixa de Balcão')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-money"></i> Recebimento Presencial no Caixa Escolar
        </h2>
        <a href="{{ route('financeiro.titulos.index') }}" class="btn btn-sm">
            <i class="fa fa-arrow-left"></i> Voltar para Mensalidades
        </a>
    </div>

    @if(!$titulo)
        <!-- Seletor de Título em Aberto -->
        <div style="background-color: #ffffff; border: 1px solid #cddce6; padding: 15px; margin-bottom: 20px; border-radius: 3px;">
            <h4 style="color: #47728f; margin: 0 0 10px 0; font-size: 14px; font-weight: 700;">
                <i class="fa fa-search"></i> Selecione a Mensalidade ou Parcela a Baixar
            </h4>
            <table class="table-default" border="0" cellpadding="2" cellspacing="0">
                <thead>
                    <tr style="background-color: #dbe7ef; font-size: 13px;">
                        <th width="80">Parcela</th>
                        <th>Responsável Financeiro</th>
                        <th>Aluno / Descrição</th>
                        <th width="100">Vencimento</th>
                        <th width="120" style="text-align: right;">Saldo Devedor</th>
                        <th width="100" style="text-align: center;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($titulosAbertos as $t)
                        <tr>
                            <td><strong>{{ $t->numero_parcela == 0 ? 'Matr.' : sprintf('%02d/%02d', $t->numero_parcela, $t->total_parcelas) }}</strong></td>
                            <td>{{ $t->responsavel->nome ?? '-' }}</td>
                            <td>Aluno #{{ $t->aluno_id }} - {{ $t->observacoes }}</td>
                            <td>{{ \Carbon\Carbon::parse($t->data_vencimento)->format('d/m/Y') }}</td>
                            <td style="text-align: right; font-weight: 700; color: #47728f;">
                                R$ {{ number_format($t->valor_saldo, 2, ',', '.') }}
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $t->id]) }}" class="btn btn-sm btn-green">
                                    Selecionar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 15px; color: #777;">
                                Não há títulos com saldo em aberto no momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <!-- Formulário de Baixa com o Título Selecionado -->
        <form id="formcadastro" method="post" action="{{ route('financeiro.baixas.store') }}">
            @csrf
            <input type="hidden" name="titulo_id" value="{{ $titulo->id }}">

            <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="formdktd" colspan="2" height="24">
                            <b><i class="fa fa-file-text-o"></i> Dados da Parcela Selecionada (#{{ $titulo->id }})</b>
                        </td>
                    </tr>

                    <tr>
                        <td class="formmdtd" width="220"><strong>Aluno:</strong></td>
                        <td class="formmdtd">Aluno #{{ $titulo->aluno_id }}</td>
                    </tr>

                    <tr>
                        <td class="formlttd"><strong>Responsável Financeiro:</strong></td>
                        <td class="formlttd">
                            <strong>{{ $titulo->responsavel->nome ?? '-' }}</strong> (CPF: {{ $titulo->responsavel->cpf_cnpj_formatado ?? '-' }})
                        </td>
                    </tr>

                    <tr>
                        <td class="formmdtd"><strong>Vencimento Original:</strong></td>
                        <td class="formmdtd">{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</td>
                    </tr>

                    <tr>
                        <td class="formlttd"><strong>Saldo Atual da Dívida:</strong></td>
                        <td class="formlttd" style="font-size: 16px; font-weight: 700; color: #47728f;">
                            R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}
                            @if($titulo->isVencido())
                                <span style="font-size: 12px; color: #d9534f; margin-left: 10px;">
                                    (Vencido há {{ $titulo->dias_atraso }} dias | Sugestão com juros/multa: R$ {{ number_format($titulo->valor_atualizado, 2, ',', '.') }})
                                </span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td class="formdktd" colspan="2" height="24">
                            <b><i class="fa fa-calculator"></i> Dados do Recebimento</b>
                        </td>
                    </tr>

                    <tr>
                        <td class="formmdtd" valign="top">
                            <label for="valor_pago" class="form">Valor Efetivamente Pago (R$) <span class="campo_obrigatorio">*</span></label>
                        </td>
                        <td class="formmdtd">
                            <input type="number" name="valor_pago" id="valor_pago" step="0.01" min="0.01" value="{{ old('valor_pago', $titulo->valor_saldo) }}" required class="form-input" style="max-width: 200px; font-size: 16px; font-weight: 700; color: #438944;">
                        </td>
                    </tr>

                    <tr>
                        <td class="formlttd" valign="top">
                            <label for="forma_pagamento" class="form">Forma de Pagamento <span class="campo_obrigatorio">*</span></label>
                        </td>
                        <td class="formlttd">
                            <select name="forma_pagamento" id="forma_pagamento" required class="form-input" style="max-width: 250px;">
                                <option value="dinheiro">Dinheiro (Espécie)</option>
                                <option value="pix">Pix (Chave da Escola)</option>
                                <option value="cartao_debito">Cartão de Débito</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="transferencia">Transferência / TED</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="formmdtd" valign="top">
                            <label for="data_pagamento" class="form">Data do Recebimento <span class="campo_obrigatorio">*</span></label>
                        </td>
                        <td class="formmdtd">
                            <input type="date" name="data_pagamento" id="data_pagamento" value="{{ old('data_pagamento', date('Y-m-d')) }}" required class="form-input" style="max-width: 200px;">
                        </td>
                    </tr>

                    <tr>
                        <td class="formlttd" valign="top">
                            <label for="valor_desconto_concedido" class="form">Desconto Concedido no Caixa (R$)</label>
                        </td>
                        <td class="formlttd">
                            <input type="number" name="valor_desconto_concedido" id="valor_desconto_concedido" step="0.01" min="0" value="{{ old('valor_desconto_concedido', '0.00') }}" class="form-input" style="max-width: 200px;">
                        </td>
                    </tr>

                    <tr>
                        <td class="formmdtd" valign="top">
                            <label for="valor_juros" class="form">Juros Cobrados (R$)</label>
                        </td>
                        <td class="formmdtd">
                            <input type="number" name="valor_juros" id="valor_juros" step="0.01" min="0" value="{{ old('valor_juros', $titulo->calcularJuros()) }}" class="form-input" style="max-width: 200px;">
                        </td>
                    </tr>

                    <tr>
                        <td class="formlttd" valign="top">
                            <label for="valor_multa" class="form">Multa Cobrada (R$)</label>
                        </td>
                        <td class="formlttd">
                            <input type="number" name="valor_multa" id="valor_multa" step="0.01" min="0" value="{{ old('valor_multa', $titulo->calcularMulta()) }}" class="form-input" style="max-width: 200px;">
                        </td>
                    </tr>

                    <tr>
                        <td class="formmdtd" colspan="2" style="padding: 15px;">
                            <button type="submit" class="btn btn-green botaosalvar">
                                <i class="fa fa-check-circle"></i> Confirmar Recebimento e Emitir Recibo
                            </button>
                            <a href="{{ route('financeiro.baixas.create') }}" class="btn geral-botao" style="margin-left: 8px;">
                                Cancelar
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    @endif
@endsection
