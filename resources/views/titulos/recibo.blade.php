<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pagamento - Parcela #{{ $titulo->id }}</title>
    <style>
        body {
            font-family: "Open Sans", Arial, sans-serif;
            font-size: 13px;
            color: #333;
            margin: 20px;
            background-color: #f7f9fa;
        }
        .recibo-container {
            max-width: 750px;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #ccdce6;
            padding: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #47728f;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-title {
            font-size: 18px;
            font-weight: bold;
            color: #47728f;
            text-transform: uppercase;
        }
        .recibo-numero {
            font-size: 15px;
            font-weight: bold;
            color: #438944;
        }
        .corpo-texto {
            line-height: 1.8;
            font-size: 14px;
            text-align: justify;
            margin-bottom: 25px;
        }
        .detalhes-tabela {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .detalhes-tabela th, .detalhes-tabela td {
            border: 1px solid #cddce6;
            padding: 8px 12px;
            font-size: 13px;
        }
        .detalhes-tabela th {
            background-color: #ccdce6;
            color: #47728f;
            text-align: left;
        }
        .total-box {
            background-color: #f5f9fd;
            border: 1px solid #ccdce6;
            padding: 12px 15px;
            font-size: 16px;
            font-weight: bold;
            color: #47728f;
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .rodape {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        .assinatura {
            text-align: center;
            width: 280px;
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 12px;
        }
        .autenticacao {
            font-size: 10px;
            color: #777;
        }
        .btn-imprimir {
            background-color: #47728f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 3px;
            margin-bottom: 15px;
        }
        @media print {
            body { background: #fff; margin: 0; }
            .recibo-container { border: 1px solid #000; box-shadow: none; padding: 15px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 750px; margin: 0 auto 10px auto; text-align: right;">
        <button class="btn-imprimir" onclick="window.print();">
            Imprimir Recibo
        </button>
    </div>

    <div class="recibo-container">
        <div class="header">
            <div>
                <div class="header-title">i-Educar - Recibo Escolar</div>
                <div style="font-size: 12px; color: #666;">Gestão Financeira Escolar</div>
            </div>
            <div class="recibo-numero">
                RECIBO Nº {{ sprintf('%06d', $ultimoPagamento->id ?? $titulo->id) }}
            </div>
        </div>

        <div class="corpo-texto">
            Recebemos de <strong>{{ $titulo->responsavel->nome ?? 'Responsável não identificado' }}</strong>,
            inscrito(a) no CPF/CNPJ <strong>{{ $titulo->responsavel->cpf_cnpj_formatado ?? '-' }}</strong>,
            a quantia de <strong>R$ {{ number_format($ultimoPagamento->valor_pago ?? $titulo->valor_pago, 2, ',', '.') }}</strong>,
            referente à liquidação de <strong>{{ $titulo->observacoes ?? ucfirst($titulo->tipo) }}</strong>
            do(a) aluno(a) código <strong>#{{ $titulo->aluno_id }}</strong>,
            competência <strong>{{ $titulo->competencia }}</strong>,
            com vencimento original em <strong>{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</strong>.
        </div>

        <table class="detalhes-tabela">
            <thead>
                <tr>
                    <th>Item / Especificação</th>
                    <th width="120" style="text-align: right;">Valor</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Valor Nominal do Título</td>
                    <td style="text-align: right;">R$ {{ number_format($titulo->valor_nominal, 2, ',', '.') }}</td>
                </tr>
                @if(($ultimoPagamento->valor_desconto_concedido ?? 0) > 0)
                    <tr>
                        <td>(-) Desconto Concedido</td>
                        <td style="text-align: right; color: #438944;">- R$ {{ number_format($ultimoPagamento->valor_desconto_concedido, 2, ',', '.') }}</td>
                    </tr>
                @endif
                @if(($ultimoPagamento->valor_multa ?? 0) > 0)
                    <tr>
                        <td>(+) Multa por Atraso</td>
                        <td style="text-align: right; color: #cc0000;">+ R$ {{ number_format($ultimoPagamento->valor_multa, 2, ',', '.') }}</td>
                    </tr>
                @endif
                @if(($ultimoPagamento->valor_juros ?? 0) > 0)
                    <tr>
                        <td>(+) Juros de Mora</td>
                        <td style="text-align: right; color: #cc0000;">+ R$ {{ number_format($ultimoPagamento->valor_juros, 2, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <td><strong>Forma de Pagamento</strong></td>
                    <td style="text-align: right;">{{ ucfirst(str_replace('_', ' ', $ultimoPagamento->forma_pagamento ?? 'dinheiro')) }}</td>
                </tr>
                <tr>
                    <td><strong>Data do Recebimento</strong></td>
                    <td style="text-align: right;">{{ \Carbon\Carbon::parse($ultimoPagamento->data_pagamento ?? now())->format('d/m/Y') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total-box">
            <span>TOTAL LIQUIDADO:</span>
            <span>R$ {{ number_format($ultimoPagamento->valor_pago ?? $titulo->valor_pago, 2, ',', '.') }}</span>
        </div>

        <div class="rodape">
            <div class="autenticacao">
                Autenticação Digital Anti-fraude:<br>
                <code>{{ $ultimoPagamento->comprovante_hash ?? hash('sha256', (string)$titulo->id) }}</code><br>
                Emitido em {{ date('d/m/Y H:i:s') }}
            </div>
            <div class="assinatura">
                Tesouraria / Secretaria Escolar
            </div>
        </div>
    </div>
</body>
</html>
