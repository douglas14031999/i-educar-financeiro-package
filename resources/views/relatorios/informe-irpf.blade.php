@extends('financeiro::layouts.app')

@section('title', 'Informe de Pagamentos para IRPF')
@section('breadcrumb', 'Relatórios > Informe IRPF')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div>
            <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-file-text-o"></i> Comprovante de Pagamentos para Imposto de Renda (IRPF)
            </h2>
            <span style="font-size: 12px; color: #666;">
                Emissão da declaração oficial de pagamentos efetuados no ano-calendário para dedução no IRPF
            </span>
        </div>
        <div>
            @if($responsavel)
                <button type="button" class="btn btn-sm btn-green" onclick="window.print();">
                    <i class="fa fa-print"></i> Imprimir Comprovante
                </button>
            @endif
            <a href="{{ route('financeiro.relatorios.index') }}" class="btn btn-sm" style="margin-left: 8px;">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Filtros de Emissão -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="3" cellspacing="0" style="margin-bottom: 25px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-filter"></i> Selecionar Responsável Financeiro e Ano-Base</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" colspan="4" style="padding: 10px;">
                    <form method="get" action="{{ route('financeiro.relatorios.informe-irpf') }}" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        <div>
                            <label style="font-size: 12px; font-weight: bold; margin: 0;">Ano-Base:</label>
                            <input type="number" name="ano_base" value="{{ $anoBase }}" class="form-input" style="width: 90px;" required>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 12px; font-weight: bold; margin: 0;">Responsável Financeiro:</label>
                            <select name="responsavel_id" class="form-input" style="width: 100%;" required>
                                <option value="">-- Selecione o Responsável Financeiro --</option>
                                @foreach($responsaveis as $resp)
                                    <option value="{{ $resp->id }}" {{ ($responsavel && $responsavel->id == $resp->id) ? 'selected' : '' }}>
                                        {{ $resp->nome }} (CPF: {{ $resp->cpf_cnpj_formatado }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="align-self: flex-end;">
                            <button type="submit" class="botaosalvar">
                                <i class="fa fa-check"></i> Gerar Informe
                            </button>
                        </div>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>

    @if($responsavel)
        <!-- Documento Oficial para Impressão -->
        <div id="documento-irpf" style="background: #fff; border: 1px solid #ccdce6; padding: 25px; margin-bottom: 30px; font-family: 'Open Sans', Arial, sans-serif;">
            <div style="text-align: center; border-bottom: 2px solid #47728f; padding-bottom: 15px; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #47728f; text-transform: uppercase; font-size: 16px;">
                    DECLARAÇÃO DE PAGAMENTOS DE INSTRUÇÃO E EDUCAÇÃO
                </h3>
                <span style="font-size: 13px; color: #444;">
                    Comprovante de Rendimentos Pagos a Pessoas Jurídicas para Fins de Declaração de IRPF
                </span>
            </div>

            <table width="100%" border="0" cellpadding="4" cellspacing="0" style="font-size: 13px; margin-bottom: 20px;">
                <tr>
                    <td width="50%">
                        <strong>INSTITUIÇÃO DE ENSINO:</strong><br>
                        Escola / Mantenedora Integrada i-Educar<br>
                        CNPJ: Conforme Cadastro Escolar Municipal/Privado
                    </td>
                    <td width="50%">
                        <strong>ANO-CALENDÁRIO:</strong> {{ $anoBase }}<br>
                        <strong>DATA DE EMISSÃO:</strong> {{ date('d/m/Y') }}
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 15px;">
                        <strong>BENEFICIÁRIO / RESPONSÁVEL DO PAGAMENTO:</strong><br>
                        Nome: <strong>{{ $responsavel->nome }}</strong><br>
                        CPF: <strong>{{ $responsavel->cpf_cnpj_formatado }}</strong>
                    </td>
                </tr>
            </table>

            <h4 style="color: #47728f; font-size: 14px; border-bottom: 1px solid #ccdce6; padding-bottom: 5px; margin-bottom: 10px;">
                DISCRIMINAÇÃO DOS VALORES PAGOS POR ALUNO/DEPENDENTE
            </h4>

            @foreach($pagamentosAgrupados as $alunoId => $dadosAluno)
                <div style="margin-bottom: 15px;">
                    <strong>Aluno(a) / Beneficiário(a):</strong> Código #{{ $alunoId }}<br>
                    <table class="table-default" border="0" cellpadding="2" cellspacing="0" style="margin-top: 5px;">
                        <thead>
                            <tr style="background-color: #f2f7fa; font-size: 12px;">
                                <th>Data Pagamento</th>
                                <th>Competência</th>
                                <th>Forma</th>
                                <th style="text-align: right;">Valor Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dadosAluno['itens'] as $item)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($item->data_pagamento)->format('d/m/Y') }}</td>
                                    <td>{{ $item->titulo->competencia ?? '-' }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $item->forma_pagamento)) }}</td>
                                    <td style="text-align: right;">R$ {{ number_format($item->valor_pago, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr style="background: #fafafa; font-weight: bold;">
                                <td colspan="3" style="text-align: right;">Subtotal Aluno #{{ $alunoId }}:</td>
                                <td style="text-align: right; color: #438944;">R$ {{ number_format($dadosAluno['total'], 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach

            <div style="background-color: #f5f9fd; border: 1px solid #ccdce6; padding: 12px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 14px; font-weight: bold; color: #47728f;">
                    VALOR TOTAL COMPROVADO PAGO NO ANO-BASE {{ $anoBase }}:
                </span>
                <span style="font-size: 18px; font-weight: 700; color: #438944;">
                    R$ {{ number_format($totalDeclarado, 2, ',', '.') }}
                </span>
            </div>

            <div style="margin-top: 30px; text-align: center; font-size: 11px; color: #777;">
                Declaração gerada eletronicamente pelo Sistema de Gestão Financeira Escolar i-Educar em {{ date('d/m/Y H:i') }}.<br>
                Autenticação de Rastreabilidade SHA-256: <code>{{ hash('sha256', $responsavel->id . $anoBase . $totalDeclarado . config('app.key')) }}</code>
            </div>
        </div>
    @endif
@endsection
