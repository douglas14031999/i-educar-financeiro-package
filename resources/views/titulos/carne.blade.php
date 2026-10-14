<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emissão de Carnê Escolar - i-Educar Financeiro</title>
    <style>
        /* Reset & Base */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.25;
            padding: 20px;
        }

        /* Barra de Controle (Apenas em tela) */
        .no-print-toolbar {
            max-width: 800px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #cbd5e1;
        }
        .toolbar-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }
        .toolbar-desc {
            font-size: 12px;
            color: #64748b;
        }
        .toolbar-actions {
            display: flex;
            gap: 10px;
        }
        .btn-print {
            background-color: #0ac336;
            color: #ffffff;
            border: 1px solid #09b031;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-print:hover {
            background-color: #09b031;
        }
        .btn-back {
            background-color: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back:hover {
            background-color: #f8fafc;
        }

        /* Container da Folha A4 */
        .carne-page {
            width: 210mm;
            min-height: 290mm;
            margin: 0 auto 30px auto;
            background: #ffffff;
            padding: 8mm 10mm;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            page-break-after: always;
        }

        /* Lâmina do Carnê */
        .carne-item {
            border: 1px solid #94a3b8;
            margin-bottom: 6mm;
            display: flex;
            background: #ffffff;
            height: 82mm;
            position: relative;
        }
        .carne-item:last-child {
            margin-bottom: 0;
        }

        /* Linha de Destaque / Corte */
        .cut-line {
            position: absolute;
            bottom: -3.5mm;
            left: 0;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #64748b;
        }
        .cut-line span {
            background: #ffffff;
            padding: 0 8px;
        }
        .cut-line-dash {
            position: absolute;
            left: 0;
            width: 100%;
            border-top: 1px dashed #94a3b8;
            z-index: 1;
        }

        /* Canhoto do Emissor / Escola (Esquerda - 32%) */
        .carne-canhoto {
            width: 32%;
            border-right: 1px dashed #94a3b8;
            padding: 5px 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: #fafbfc;
        }
        .canhoto-header {
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }
        .canhoto-title {
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
        }
        .canhoto-sub {
            font-size: 9px;
            color: #64748b;
        }
        .canhoto-row {
            margin-bottom: 4px;
        }
        .canhoto-label {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
        }
        .canhoto-val {
            font-size: 10.5px;
            font-weight: 600;
            color: #0f172a;
        }
        .canhoto-signature {
            border-top: 1px solid #94a3b8;
            margin-top: 6px;
            padding-top: 2px;
            text-align: center;
            font-size: 8.5px;
            color: #64748b;
        }

        /* Ficha de Compensação (Direita - 68%) */
        .carne-ficha {
            width: 68%;
            padding: 5px 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .ficha-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }
        .ficha-banco-logo {
            font-size: 14px;
            font-weight: 800;
            color: #47728f;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .ficha-linha-digitavel {
            font-family: "Courier New", Courier, monospace;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        /* Tabela de Campos da Ficha */
        .ficha-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .ficha-table td {
            border: 1px solid #cbd5e1;
            padding: 2px 5px;
            vertical-align: top;
        }
        .f-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            display: block;
        }
        .f-val {
            font-size: 10.5px;
            font-weight: 700;
            color: #0f172a;
        }
        .ficha-instrucoes {
            font-size: 8.5px;
            color: #334155;
            line-height: 1.25;
            height: 38px;
            overflow: hidden;
        }

        /* Footer da Ficha: Pagador, Pix e Barras */
        .ficha-footer {
            display: flex;
            gap: 8px;
            margin-top: 4px;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            align-items: center;
        }
        .ficha-pagador {
            flex: 1;
            font-size: 9px;
            line-height: 1.25;
        }
        .ficha-pix-box {
            width: 60px;
            text-align: center;
        }
        .ficha-pix-qr {
            width: 52px;
            height: 52px;
            display: block;
            margin: 0 auto;
        }
        .ficha-pix-label {
            font-size: 7.5px;
            font-weight: 700;
            color: #059669;
            text-transform: uppercase;
        }

        /* Código de Barras */
        .barcode-representation {
            font-family: "Courier New", Courier, monospace;
            font-size: 13px;
            letter-spacing: 2px;
            text-align: center;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
            margin-top: 2px;
        }
        .barcode-bars {
            height: 18px;
            background: repeating-linear-gradient(
                90deg,
                #000 0px,
                #000 2px,
                #fff 2px,
                #fff 3px,
                #000 3px,
                #000 5px,
                #fff 5px,
                #fff 8px
            );
            width: 85%;
            margin: 2px auto 0 auto;
        }

        /* Regras de Impressão */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print,
            .no-print-toolbar {
                display: none !important;
            }
            .carne-page {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 5mm 6mm !important;
                width: 100% !important;
                height: 100% !important;
            }
        }
    </style>
</head>
<body>

    <!-- Barra de Ferramentas / Impressão (Oculta na impressora) -->
    <div class="no-print-toolbar">
        <div>
            <div class="toolbar-title">📄 Emissão de Carnê Escolar</div>
            <div class="toolbar-desc">
                Total de {{ $titulos->count() }} {{ $titulos->count() == 1 ? 'parcela pronta' : 'parcelas prontas' }} para impressão em formato A4 (3 carnês por folha)
            </div>
        </div>
        <div class="toolbar-actions">
            <a href="javascript:window.history.back();" class="btn-back">
                ← Voltar
            </a>
            <button onclick="window.print();" class="btn-print">
                🖨️ Imprimir Carnê
            </button>
        </div>
    </div>

    @php
        // Divide os títulos em grupos de 3 por folha A4
        $paginas = $titulos->chunk(3);
    @endphp

    @forelse($paginas as $paginaIndex => $grupoTitulos)
        <div class="carne-page">
            @foreach($grupoTitulos as $titulo)
                @php
                    $responsavel = $titulo->responsavel;
                    $cobrancaGateway = $titulo->cobrancaGateway;
                    $alunoNome = $titulo->aluno ? ($titulo->aluno->nome ?? 'Aluno(a)') : ($responsavel ? $responsavel->nome : 'Aluno(a)');
                    $vencimentoFormatado = $titulo->data_vencimento ? \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') : '-';
                    $valorNominal = number_format($titulo->valor_nominal, 2, ',', '.');
                    $valorDesconto = number_format($titulo->valor_desconto ?? 0, 2, ',', '.');
                    $valorCobrado = number_format($titulo->valor_saldo > 0 ? $titulo->valor_saldo : $titulo->valor_nominal, 2, ',', '.');
                    $parcelaTexto = ($titulo->numero_parcela ?? 1) . ' / ' . ($titulo->total_parcelas ?? 12);
                    $linhaDigitavel = $cobrancaGateway->linha_digitavel ?? ('00190.00009 01234.567890 12345.678901 1 ' . str_pad($titulo->id, 8, '0', STR_PAD_LEFT));
                @endphp

                <div class="carne-item">
                    <!-- Canhoto do Emissor / Escola -->
                    <div class="carne-canhoto">
                        <div class="canhoto-header">
                            <div class="canhoto-title">Recibo do Sacado</div>
                            <div class="canhoto-sub">Mensalidade Escolar</div>
                        </div>

                        <div class="canhoto-row">
                            <span class="canhoto-label">Aluno(a) / Matrícula:</span>
                            <div class="canhoto-val">{{ Str::limit($alunoNome, 28) }}</div>
                        </div>

                        <div class="canhoto-row">
                            <span class="canhoto-label">Responsável:</span>
                            <div class="canhoto-val">{{ Str::limit($responsavel->nome ?? 'Não informado', 28) }}</div>
                        </div>

                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <div>
                                <span class="canhoto-label">Parcela:</span>
                                <div class="canhoto-val">{{ $parcelaTexto }}</div>
                            </div>
                            <div>
                                <span class="canhoto-label">Vencimento:</span>
                                <div class="canhoto-val" style="color: #b91c1c;">{{ $vencimentoFormatado }}</div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <div>
                                <span class="canhoto-label">Valor:</span>
                                <div class="canhoto-val">R$ {{ $valorNominal }}</div>
                            </div>
                            <div>
                                <span class="canhoto-label">Valor Cobrado:</span>
                                <div class="canhoto-val" style="color: #047857;">R$ {{ $valorCobrado }}</div>
                            </div>
                        </div>

                        <div class="canhoto-signature">
                            Data de Recebimento & Assinatura do Caixa
                        </div>
                    </div>

                    <!-- Ficha de Compensação -->
                    <div class="carne-ficha">
                        <div class="ficha-header">
                            <div class="ficha-banco-logo">
                                <span>🏛️ i-Educar Financeiro</span>
                                <span style="font-size: 11px; font-weight: normal; color: #64748b;">| Cobrança Escolar</span>
                            </div>
                            <div class="ficha-linha-digitavel">{{ $linhaDigitavel }}</div>
                        </div>

                        <table class="ficha-table">
                            <tr>
                                <td style="width: 65%;">
                                    <span class="f-label">Local de Pagamento</span>
                                    <span>Pagável em qualquer banco, rede lotérica, Internet Banking ou via Pix</span>
                                </td>
                                <td style="width: 35%;">
                                    <span class="f-label">Data de Vencimento</span>
                                    <span class="f-val" style="color: #b91c1c;">{{ $vencimentoFormatado }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="f-label">Beneficiário</span>
                                    <span class="f-val">Instituição de Ensino / i-Educar Financeiro</span>
                                </td>
                                <td>
                                    <span class="f-label">Agência / Código Beneficiário</span>
                                    <span>{{ $cobrancaGateway->nosso_numero ?? ('0001 / ' . str_pad($titulo->id, 7, '0', STR_PAD_LEFT)) }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display: flex; gap: 15px;">
                                        <div>
                                            <span class="f-label">Data do Doc.</span>
                                            <span>{{ now()->format('d/m/Y') }}</span>
                                        </div>
                                        <div>
                                            <span class="f-label">Nº Documento</span>
                                            <span>TIT-{{ str_pad($titulo->id, 6, '0', STR_PAD_LEFT) }}</span>
                                        </div>
                                        <div>
                                            <span class="f-label">Espécie Doc.</span>
                                            <span>DM (Mensalidade)</span>
                                        </div>
                                        <div>
                                            <span class="f-label">Parcela</span>
                                            <span class="f-val">{{ $parcelaTexto }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="f-label">(=) Valor do Documento</span>
                                    <span class="f-val" style="font-size: 11px; color: #047857;">R$ {{ $valorCobrado }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td rowspan="2" class="ficha-instrucoes">
                                    <span class="f-label">Instruções de Pagamento</span>
                                    • Não receber após 30 dias do vencimento.<br>
                                    • Após o vencimento, cobrar multa de 2,00% e juros de 1,00% ao mês.<br>
                                    • Em caso de dúvidas, procure a secretaria escolar.
                                </td>
                                <td>
                                    <span class="f-label">(-) Desconto / Abatimento</span>
                                    <span>R$ {{ $valorDesconto }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span class="f-label">(=) Valor Pago / Cobrado</span>
                                    <span class="f-val">R$ {{ $valorCobrado }}</span>
                                </td>
                            </tr>
                        </table>

                        <!-- Rodapé: Pagador e Pix -->
                        <div class="ficha-footer">
                            <div class="ficha-pagador">
                                <span class="f-label">Pagador / Responsável:</span>
                                <strong>{{ $responsavel->nome ?? 'Responsável não cadastrado' }}</strong> - CPF: {{ $responsavel->cpf_cnpj ?? 'Não informado' }}<br>
                                <span style="color: #64748b;">Aluno(a): {{ $alunoNome }} | Competência: {{ $titulo->competencia ?? \Carbon\Carbon::parse($titulo->data_vencimento)->format('m/Y') }}</span>
                            </div>

                            @if(!empty($cobrancaGateway->qr_code_pix))
                                <div class="ficha-pix-box">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode($cobrancaGateway->qr_code_pix) }}" alt="Pix QR" class="ficha-pix-qr">
                                    <div class="ficha-pix-label">Pague via Pix</div>
                                </div>
                            @elseif(!empty($cobrancaGateway->url_boleto))
                                <div class="ficha-pix-box">
                                    <a href="{{ $cobrancaGateway->url_boleto }}" target="_blank" style="text-decoration: none; font-size: 8px; color: #0284c7; font-weight: bold; display: block; padding-top: 10px;">
                                        🌐 Boleto Online
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Representação do Código de Barras -->
                        <div class="barcode-bars"></div>
                    </div>

                    <!-- Marca de Corte entre Carnês -->
                    @if(!$loop->last)
                        <div class="cut-line">
                            <div class="cut-line-dash"></div>
                            <span style="position: relative; z-index: 2;">✂ Destaque aqui</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @empty
        <div style="text-align: center; padding: 40px; background: white; border-radius: 6px; max-width: 600px; margin: 40px auto; border: 1px solid #cbd5e1;">
            <h3 style="color: #b91c1c; margin-bottom: 8px;">Nenhum título selecionado para emissão de carnê</h3>
            <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">Selecione uma ou mais mensalidades na lista para gerar o carnê para impressão.</p>
            <a href="{{ route('financeiro.titulos.index') }}" class="btn-back">
                ← Voltar para Mensalidades
            </a>
        </div>
    @endforelse

</body>
</html>
