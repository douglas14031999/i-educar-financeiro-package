@extends('financeiro::layouts.app')

@section('title', 'Termo de Confissão de Dívida - Acordo ' . $acordo->numero_acordo)
@section('breadcrumb', 'Renegociação > Termo de Acordo')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div>
            <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-legal"></i> Instrumento Particular de Acordo e Confissão de Dívida
            </h2>
            <span style="font-size: 12px; color: #666;">
                Acordo nº {{ $acordo->numero_acordo }} formalizado em {{ $acordo->created_at->format('d/m/Y \à\s H:i') }}
            </span>
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-green" onclick="window.print();">
                <i class="fa fa-print"></i> Imprimir Termo de Acordo
            </button>
            <a href="{{ route('financeiro.renegociacao.index') }}" class="btn btn-sm" style="margin-left: 8px;">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Termo Jurídico Formatado -->
    <div id="documento-termo" style="background: #fff; border: 1px solid #ccdce6; padding: 35px; margin-bottom: 30px; font-family: 'Open Sans', Arial, sans-serif; line-height: 1.7; font-size: 13px; color: #222;">
        <div style="text-align: center; border-bottom: 2px solid #47728f; padding-bottom: 15px; margin-bottom: 25px;">
            <h3 style="margin: 0; color: #47728f; text-transform: uppercase; font-size: 17px; letter-spacing: 0.5px;">
                INSTRUMENTO PARTICULAR DE CONFISSÃO E RENEGOCIAÇÃO DE DÍVIDA ESCOLAR
            </h3>
            <span style="font-size: 12px; color: #555; font-weight: bold;">
                ACORDO EXTRAJUDICIAL Nº {{ $acordo->numero_acordo }}
            </span>
        </div>

        <p style="text-align: justify;">
            Pelo presente instrumento particular, de um lado a <strong>INSTITUIÇÃO DE ENSINO CREDORA</strong>, devidamente qualificada no sistema de gestão escolar <strong>i-Educar</strong>, e de outro lado:
        </p>

        <p style="text-align: justify; background: #f9fbfd; border: 1px solid #e1eaf0; padding: 10px 15px;">
            <strong>DEVEDOR(A) / RESPONSÁVEL FINANCEIRO(A):</strong><br>
            Nome: <strong>{{ $acordo->responsavel->nome }}</strong><br>
            CPF/CNPJ: <strong>{{ $acordo->responsavel->cpf_cnpj_formatado }}</strong><br>
            Telefone / WhatsApp: {{ $acordo->responsavel->celular ?? $acordo->responsavel->telefone ?? 'Não informado' }}<br>
            Endereço: {{ $acordo->responsavel->endereco ?? 'Conforme cadastro geral' }} - {{ $acordo->responsavel->cidade ?? '-' }}/{{ $acordo->responsavel->uf ?? '-' }}
        </p>

        <p style="text-align: justify;">
            Têm entre si, justo e contratado, a novação e o parcelamento da dívida acumulada, mediante as seguintes cláusulas:
        </p>

        <p style="text-align: justify;">
            <strong>CLÁUSULA PRIMEIRA - DA CONFISSÃO DA DÍVIDA:</strong> O(A) DEVEDOR(A) reconhece expressamente e confessa ser legítimo(a) devedor(a) da quantia original de <strong>R$ {{ number_format($acordo->valor_original, 2, ',', '.') }}</strong>, oriunda de mensalidades e encargos educacionais inadimplidos referentes aos seguintes títulos:
            @foreach($acordo->titulosOriginais as $to)
                [Título #{{ $to->id }} - {{ $to->competencia }} - R$ {{ number_format($to->valor_nominal, 2, ',', '.') }}]{{ !$loop->last ? ',' : '.' }}
            @endforeach
        </p>

        <p style="text-align: justify;">
            <strong>CLÁUSULA SEGUNDA - DA CONCESSÃO DE DESCONTO E VALOR DO ACORDO:</strong> Por mera liberalidade da CREDORA, concede-se o desconto promocional de <strong>R$ {{ number_format($acordo->valor_desconto_concedido, 2, ',', '.') }}</strong>, restando o saldo líquido e certo a ser pago no valor de <strong>R$ {{ number_format($acordo->valor_acordo, 2, ',', '.') }}</strong>.
        </p>

        <p style="text-align: justify;">
            <strong>CLÁUSULA TERCEIRA - DA FORMA DE PAGAMENTO E PARCELAMENTO:</strong> A dívida renegociada será adimplida em <strong>{{ $acordo->qtd_parcelas }} parcela(s)</strong>, emitidas pelo sistema com opção de pagamento via Boleto Bancário e Pix dinâmico, conforme o seguinte cronograma de vencimentos:
        </p>

        <table class="table-default" border="0" cellpadding="2" cellspacing="0" style="margin: 15px 0 20px 0;">
            <thead>
                <tr style="background-color: #f2f7fa; font-size: 12px;">
                    <th width="100">Parcela</th>
                    <th width="150">Vencimento</th>
                    <th style="text-align: right; width: 180px;">Valor Previsto</th>
                    <th>Status Atual</th>
                </tr>
            </thead>
            <tbody>
                @foreach($acordo->novasParcelas as $np)
                    <tr>
                        <td><strong>{{ $np->numero_parcela }} / {{ $np->total_parcelas }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($np->data_vencimento)->format('d/m/Y') }}</td>
                        <td style="text-align: right; font-weight: bold; color: #438944;">
                            R$ {{ number_format($np->valor_nominal, 2, ',', '.') }}
                        </td>
                        <td><span class="badge badge-{{ $np->status }}">{{ ucfirst($np->status) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p style="text-align: justify;">
            <strong>CLÁUSULA QUARTA - DO DESCUMPRIMENTO E PERDA DO BENEFÍCIO:</strong> O não pagamento de qualquer parcela em até 30 (trinta) dias após o respectivo vencimento implicará no vencimento antecipado das parcelas vincendas, a perda integral do desconto concedido na Cláusula Segunda, incidência de multa de 2% (dois por cento) e juros de 1% (um por cento) ao mês, além do imediato encaminhamento do débito para cobrança jurídica e negativação nos órgãos de proteção ao crédito (SPC/Serasa).
        </p>

        <p style="text-align: justify;">
            E, por estarem de pleno e comum acordo, firmam o presente termo em 2 (duas) vias de igual teor e forma.
        </p>

        <div style="margin-top: 50px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; text-align: center;">
            <div>
                <div style="border-top: 1px solid #333; padding-top: 5px;">
                    <strong>INSTITUIÇÃO CREDORA</strong><br>
                    Setor Financeiro / i-Educar
                </div>
            </div>
            <div>
                <div style="border-top: 1px solid #333; padding-top: 5px;">
                    <strong>{{ $acordo->responsavel->nome }}</strong><br>
                    CPF: {{ $acordo->responsavel->cpf_cnpj_formatado }}
                </div>
            </div>
        </div>

        <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; text-align: center; font-size: 11px;">
            <div>
                <div style="border-top: 1px dashed #999; padding-top: 5px;">
                    Testemunha 1 (Nome e CPF)
                </div>
            </div>
            <div>
                <div style="border-top: 1px dashed #999; padding-top: 5px;">
                    Testemunha 2 (Nome e CPF)
                </div>
            </div>
        </div>
    </div>
@endsection
