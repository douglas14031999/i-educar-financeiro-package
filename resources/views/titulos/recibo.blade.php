<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pagamento - Parcela #{{ $titulo->id }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 13px;
            color: #1e293b;
            margin: 20px;
            background-color: #f8fafc;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .recibo-container {
            max-width: 780px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 35px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #47728f;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header-title {
            font-size: 18px;
            font-weight: 800;
            color: #47728f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }
        .recibo-numero {
            font-size: 14px;
            font-weight: 700;
            color: #059669;
            background-color: #ecfdf5;
            padding: 6px 12px;
            border: 1px solid #a7f3d0;
            border-radius: 4px;
        }
        .corpo-texto {
            line-height: 1.8;
            font-size: 13.5px;
            text-align: justify;
            color: #334155;
            margin-bottom: 25px;
            background-color: #f8fafc;
            padding: 16px;
            border: 1px solid #e2e8f0;
        }
        .detalhes-tabela {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .detalhes-tabela th, .detalhes-tabela td {
            border: 1px solid #e2e8f0;
            padding: 9px 14px;
            font-size: 13px;
        }
        .detalhes-tabela th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 600;
            text-align: left;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        .total-box {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 14px 18px;
            font-size: 16px;
            font-weight: 800;
            color: #065f46;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .rodape {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px dashed #cbd5e1;
        }
        .assinatura {
            text-align: center;
            width: 280px;
            border-top: 1px solid #334155;
            padding-top: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }
        .autenticacao {
            font-size: 10.5px;
            color: #64748b;
            line-height: 1.5;
        }
        .btn-imprimir {
            background-color: #47728f;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: background 0.15s;
        }
        .btn-imprimir:hover {
            background-color: #47728f;
        }
        @media print {
            body { background: #fff; margin: 0; }
            .recibo-container { border: 1px solid #94a3b8; box-shadow: none; padding: 20px; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 780px; margin: 0 auto 12px auto; text-align: right;">
        <button class="btn-imprimir" onclick="window.print();">
            Imprimir Comprovante
        </button>
    </div>

    <div class="recibo-container">
        <div class="header">
            <div>
                <div class="header-title">i-Educar &bull; Recibo Escolar Oficial</div>
                <div class="header-subtitle">Gestão Financeira e Tesouraria Escolar Integrada</div>
            </div>
            <div class="recibo-numero">
                RECIBO Nº {{ sprintf('%06d', $ultimoPagamento->id ?? $titulo->id) }}
            </div>
        </div>

        @if(!empty($ultimoPagamento) && $ultimoPagamento->estornado)
            <div style="background-color: #ffe4e6; border: 2px solid #e11d48; color: #9f1239; padding: 12px 16px; margin: 16px 0; text-align: center; font-weight: bold; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">
                ⚠️ Este Comprovante foi Estornado em {{ \Carbon\Carbon::parse($ultimoPagamento->estornado_em)->format('d/m/Y H:i') }} &bull; Motivo: {{ $ultimoPagamento->motivo_estorno ?? 'Estorno administrativo' }}
            </div>
        @endif

        <div class="corpo-texto">
            Recebemos de <strong>{{ $titulo->responsavel->nome ?? 'Responsável não identificado' }}</strong>,
            inscrito(a) no CPF/CNPJ sob o nº <strong>{{ $titulo->responsavel->cpf_cnpj_formatado ?? '-' }}</strong>,
            a quantia de <strong>R$ {{ number_format($ultimoPagamento->valor_pago ?? $titulo->valor_pago, 2, ',', '.') }}</strong>,
            referente à liquidação de <strong>{{ $titulo->observacoes ?? ucfirst($titulo->tipo) }}</strong>
            do(a) aluno(a) <strong>{{ $titulo->aluno_nome }}</strong>,
            competência <strong>{{ $titulo->competencia }}</strong>,
            com vencimento original em <strong>{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</strong>.
        </div>

        <table class="detalhes-tabela">
            <thead>
                <tr>
                    <th>Item / Especificação Financeira</th>
                    <th width="150" style="text-align: right;">Valor</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Valor Nominal do Título</td>
                    <td style="text-align: right; font-weight: 500;">R$ {{ number_format($titulo->valor_nominal, 2, ',', '.') }}</td>
                </tr>
                @if(($ultimoPagamento->valor_desconto_concedido ?? 0) > 0)
                    <tr>
                        <td>(-) Desconto Concedido / Pontualidade</td>
                        <td style="text-align: right; color: #059669; font-weight: 600;">- R$ {{ number_format($ultimoPagamento->valor_desconto_concedido, 2, ',', '.') }}</td>
                    </tr>
                @endif
                @if(($ultimoPagamento->valor_multa ?? 0) > 0)
                    <tr>
                        <td>(+) Multa por Atraso</td>
                        <td style="text-align: right; color: #e11d48; font-weight: 600;">+ R$ {{ number_format($ultimoPagamento->valor_multa, 2, ',', '.') }}</td>
                    </tr>
                @endif
                @if(($ultimoPagamento->valor_juros ?? 0) > 0)
                    <tr>
                        <td>(+) Juros de Mora</td>
                        <td style="text-align: right; color: #e11d48; font-weight: 600;">+ R$ {{ number_format($ultimoPagamento->valor_juros, 2, ',', '.') }}</td>
                    </tr>
                @endif
                @if(($ultimoPagamento->valor_amortizado ?? 0) > 0)
                    <tr>
                        <td>Amortização Efetiva da Dívida</td>
                        <td style="text-align: right; font-weight: 600; color: #0284c7;">R$ {{ number_format($ultimoPagamento->valor_amortizado, 2, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <td><strong>Forma de Pagamento</strong></td>
                    <td style="text-align: right; text-transform: capitalize; font-weight: 600;">{{ str_replace('_', ' ', $ultimoPagamento->forma_pagamento ?? 'dinheiro') }}</td>
                </tr>
                <tr>
                    <td><strong>Data do Recebimento Efetivo</strong></td>
                    <td style="text-align: right; font-weight: 600;">{{ \Carbon\Carbon::parse($ultimoPagamento->data_pagamento ?? now())->format('d/m/Y') }}</td>
                </tr>
                <tr style="background-color: #f8fafc; border-top: 1px solid #cbd5e1;">
                    <td><strong>Saldo Restante da Dívida</strong></td>
                    <td style="text-align: right; font-weight: 700; color: #334155;">R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total-box">
            <span>TOTAL LIQUIDADO:</span>
            <span>R$ {{ number_format($ultimoPagamento->valor_pago ?? $titulo->valor_pago, 2, ',', '.') }}</span>
        </div>

        <div class="rodape">
            <div class="autenticacao">
                Autenticação Digital Rastreável:<br>
                <code style="font-family: monospace; color: #475569;">{{ $ultimoPagamento->comprovante_hash ?? hash('sha256', (string)$titulo->id) }}</code><br>
                Emitido eletronicamente em {{ date('d/m/Y \à\s H:i:s') }}
            </div>
            <div class="assinatura">
                Setor Financeiro / Tesouraria Escolar
            </div>
        </div>
    </div>
</body>
</html>
