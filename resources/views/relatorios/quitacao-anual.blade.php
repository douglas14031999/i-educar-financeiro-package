@extends('financeiro::layouts.app')

@section('title', 'Declaração de Quitação Anual (Lei 12.007/09)')
@section('breadcrumb', 'Relatórios > Quitação Anual')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div>
            <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-certificate"></i> Declaração de Quitação Anual de Débitos
            </h2>
            <span style="font-size: 12px; color: #666;">
                Emissão da certidão anual de quitação obrigatória por força da Lei Federal nº 12.007/2009
            </span>
        </div>
        <div>
            @if($responsavel && $quitadoIntegralmente)
                <button type="button" class="btn btn-sm btn-green" onclick="window.print();">
                    <i class="fa fa-print"></i> Imprimir Declaração
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
                    <b><i class="fa fa-search"></i> Selecionar Responsável Financeiro e Ano de Referência</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" colspan="4" style="padding: 10px;">
                    <form method="get" action="{{ route('financeiro.relatorios.quitacao-anual') }}" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        <div>
                            <label style="font-size: 12px; font-weight: bold; margin: 0;">Ano de Referência:</label>
                            <input type="number" name="ano" value="{{ $ano }}" class="form-input" style="width: 90px;" required>
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
                                <i class="fa fa-check"></i> Consultar Quitação
                            </button>
                        </div>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>

    @if($responsavel)
        @if($quitadoIntegralmente)
            <!-- Certidão Positiva com Efeito de Negativa / Declaração de Quitação -->
            <div id="documento-quitacao" style="background: #fff; border: 2px solid #438944; padding: 30px; margin-bottom: 30px; font-family: 'Open Sans', Arial, sans-serif;">
                <div style="text-align: center; border-bottom: 2px solid #438944; padding-bottom: 15px; margin-bottom: 25px;">
                    <h3 style="margin: 0; color: #438944; text-transform: uppercase; font-size: 18px; letter-spacing: 1px;">
                        DECLARAÇÃO ANUAL DE QUITAÇÃO DE DÉBITOS
                    </h3>
                    <span style="font-size: 13px; color: #555; font-weight: 600;">
                        EMITIDA EM CUMPRIMENTO À LEI FEDERAL Nº 12.007, DE 29 DE JULHO DE 2009
                    </span>
                </div>

                <p style="font-size: 14px; line-height: 1.8; text-align: justify; color: #333;">
                    A instituição de ensino vinculada ao sistema <strong>i-Educar</strong>, para os devidos fins de direito e em estrito cumprimento ao disposto na <strong>Lei Federal nº 12.007/2009</strong>, 
                    <strong>DECLARA E CERTIFICA</strong> que o(a) responsável financeiro(a) <strong>{{ $responsavel->nome }}</strong>, 
                    inscrito(a) no CPF/CNPJ sob o nº <strong>{{ $responsavel->cpf_cnpj_formatado }}</strong>, 
                    encontra-se em <strong>PLENA QUITAÇÃO INTEGRAL</strong> de todas as obrigações e mensalidades escolares relativas ao ano de <strong>{{ $ano }}</strong>.
                </p>

                <p style="font-size: 14px; line-height: 1.8; text-align: justify; color: #333;">
                    Esta declaração compreende a liquidação de todos os títulos emitidos no referido ano-base, substituindo para todos os efeitos legais os recibos e comprovantes mensais anteriormente emitidos, conforme previsto no Artigo 2º da Lei 12.007/2009.
                </p>

                <table class="table-default" border="0" cellpadding="2" cellspacing="0" style="margin-top: 20px; margin-bottom: 25px;">
                    <thead>
                        <tr style="background-color: #f2f7fa; font-size: 12px;">
                            <th>Competência</th>
                            <th>Vencimento</th>
                            <th>Valor Nominal</th>
                            <th>Status Contábil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($titulosAno as $t)
                            <tr>
                                <td>{{ $t->competencia }}</td>
                                <td>{{ \Carbon\Carbon::parse($t->data_vencimento)->format('d/m/Y') }}</td>
                                <td>R$ {{ number_format($t->valor_nominal, 2, ',', '.') }}</td>
                                <td><span class="badge badge-pago">Quitado</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div style="margin-top: 40px; text-align: center;">
                    <div style="display: inline-block; border-top: 1px solid #333; width: 320px; padding-top: 5px; font-size: 13px;">
                        <strong>Setor Financeiro e Tesouraria Escolar</strong><br>
                        Emitido em {{ date('d/m/Y \à\s H:i:s') }}
                    </div>
                </div>

                <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #777;">
                    Chave de Autenticação Digital: <code>{{ hash('sha256', 'LEI12007' . $responsavel->id . $ano . config('app.key')) }}</code>
                </div>
            </div>
        @else
            <!-- Aviso de Pendência (Não pode emitir quitação) -->
            <div style="background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; padding: 20px; margin-bottom: 25px;">
                <h4 style="margin: 0 0 8px 0;"><i class="fa fa-exclamation-triangle"></i> Impossível Emitir Quitação Anual</h4>
                <p style="margin: 0; font-size: 13px;">
                    O responsável <strong>{{ $responsavel->nome }}</strong> possui parcelas em aberto, vencidas ou pendentes no exercício de <strong>{{ $ano }}</strong>. 
                    Conforme o Artigo 3º da Lei Federal nº 12.007/2009, a declaração anual somente poderá ser emitida quando todos os débitos do ano estiverem integralmente liquidados.
                </p>
            </div>
        @endif
    @endif
@endsection
