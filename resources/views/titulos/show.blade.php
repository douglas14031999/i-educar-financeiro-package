@extends('financeiro::layouts.app')

@section('title', 'Detalhes da Mensalidade #' . $titulo->id)
@section('breadcrumb', 'Título #' . $titulo->id)

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-barcode"></i> Título #{{ $titulo->id }} (Parcela {{ $titulo->numero_parcela == 0 ? 'Matrícula' : $titulo->numero_parcela . '/' . $titulo->total_parcelas }})
        </h2>
        <div>
            @if($titulo->status === 'aberto' || $titulo->status === 'parcial' || $titulo->status === 'vencido')
                <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}" class="btn btn-sm btn-green">
                    <i class="fa fa-money"></i> Baixar no Balcão
                </a>
            @endif
            @if($titulo->status === 'pago')
                <a href="{{ route('financeiro.titulos.recibo-pdf', $titulo->id) }}" class="btn btn-sm" target="_blank">
                    <i class="fa fa-print"></i> Imprimir Recibo
                </a>
            @endif
            <a href="{{ route('financeiro.titulos.index') }}" class="btn btn-sm" style="margin-left: 5px;">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Tabela de Dados Financeiros do Título -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 20px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-info-circle"></i> Especificações Financeiras</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" width="160"><strong>Responsável:</strong></td>
                <td class="formmdtd">
                    <strong>{{ $titulo->responsavel->nome ?? '-' }}</strong> (CPF: {{ $titulo->responsavel->cpf_cnpj_formatado ?? '-' }})
                </td>
                <td class="formmdtd" width="160"><strong>Aluno:</strong></td>
                <td class="formmdtd">Aluno #{{ $titulo->aluno_id }}</td>
            </tr>
            <tr>
                <td class="formlttd"><strong>Competência:</strong></td>
                <td class="formlttd">{{ $titulo->competencia }}</td>
                <td class="formlttd"><strong>Vencimento:</strong></td>
                <td class="formlttd">
                    {{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}
                    @if($titulo->isVencido())
                        <span style="color: #cc0000; font-weight: bold; margin-left: 5px;">
                            (Atrasado {{ $titulo->dias_atraso }} dias)
                        </span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="formmdtd"><strong>Valor Nominal:</strong></td>
                <td class="formmdtd">R$ {{ number_format($titulo->valor_nominal, 2, ',', '.') }}</td>
                <td class="formmdtd"><strong>Status Atual:</strong></td>
                <td class="formmdtd">
                    <span class="badge badge-{{ $titulo->status }}">{{ ucfirst($titulo->status) }}</span>
                </td>
            </tr>
            <tr>
                <td class="formlttd"><strong>Valor Já Pago:</strong></td>
                <td class="formlttd" style="color: #438944; font-weight: bold;">
                    R$ {{ number_format($titulo->valor_pago, 2, ',', '.') }}
                </td>
                <td class="formlttd"><strong>Saldo Devedor:</strong></td>
                <td class="formlttd" style="color: #47728f; font-weight: 700; font-size: 16px;">
                    R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}
                </td>
            </tr>
            @if($titulo->isVencido())
                <tr>
                    <td class="formmdtd"><strong>Juros / Multa Hoje:</strong></td>
                    <td class="formmdtd" colspan="3" style="color: #d9534f;">
                        Multa: R$ {{ number_format($titulo->calcularMulta(), 2, ',', '.') }} |
                        Juros: R$ {{ number_format($titulo->calcularJuros(), 2, ',', '.') }} |
                        <strong>Total Corrigido: R$ {{ number_format($titulo->valor_atualizado, 2, ',', '.') }}</strong>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Cobrança Digital Asaas (Boleto Híbrido & Pix Dinâmico) -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 20px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-qrcode"></i> Cobrança Digital Gateway (Asaas - Boleto & Pix)</b>
                </td>
            </tr>
            @if($titulo->cobrancaGateway)
                <tr>
                    <td class="formmdtd" width="160"><strong>Status no Gateway:</strong></td>
                    <td class="formmdtd">
                        <span class="badge badge-info">{{ $titulo->cobrancaGateway->status_externo ?? 'PENDENTE' }}</span>
                        <span style="font-size: 11px; color: #666; margin-left: 8px;">ID Asaas: {{ $titulo->cobrancaGateway->gateway_charge_id }}</span>
                    </td>
                    <td class="formmdtd" width="160"><strong>Ações de Consulta:</strong></td>
                    <td class="formmdtd">
                        <form method="post" action="{{ route('financeiro.titulos.sincronizar-asaas', $titulo->id) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm" title="Consultar status no Asaas em tempo real">
                                <i class="fa fa-refresh"></i> Sincronizar Status
                            </button>
                        </form>
                        <a href="{{ route('financeiro.titulos.notificar-whatsapp', $titulo->id) }}" target="_blank" class="btn btn-sm btn-green" style="margin-left: 5px;">
                            <i class="fa fa-whatsapp"></i> Enviar por WhatsApp
                        </a>
                    </td>
                </tr>
                @if($titulo->cobrancaGateway->link_boleto_pdf || $titulo->cobrancaGateway->link_pagamento)
                    <tr>
                        <td class="formlttd"><strong>Links de Pagamento:</strong></td>
                        <td class="formlttd" colspan="3">
                            @if($titulo->cobrancaGateway->link_boleto_pdf)
                                <a href="{{ $titulo->cobrancaGateway->link_boleto_pdf }}" target="_blank" class="btn btn-sm" style="background-color: #47728f; color: #fff;">
                                    <i class="fa fa-file-pdf-o"></i> Abrir Boleto em PDF
                                </a>
                            @endif
                            @if($titulo->cobrancaGateway->link_pagamento)
                                <a href="{{ $titulo->cobrancaGateway->link_pagamento }}" target="_blank" class="btn btn-sm" style="margin-left: 5px;">
                                    <i class="fa fa-external-link"></i> Fatura Online / Cartão
                                </a>
                            @endif
                        </td>
                    </tr>
                @endif
                @if($titulo->cobrancaGateway->linha_digitavel)
                    <tr>
                        <td class="formmdtd"><strong>Linha Digitável:</strong></td>
                        <td class="formmdtd" colspan="3">
                            <input type="text" readonly value="{{ $titulo->cobrancaGateway->linha_digitavel }}" class="form-input" style="width: 70%; font-family: monospace;" id="input-linha-dig">
                            <button type="button" class="btn btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('input-linha-dig').value); alert('Linha digitável copiada!');">
                                <i class="fa fa-copy"></i> Copiar
                            </button>
                        </td>
                    </tr>
                @endif
                @if($titulo->cobrancaGateway->pix_copia_cola)
                    <tr>
                        <td class="formlttd"><strong>Pix Copia e Cola:</strong></td>
                        <td class="formlttd" colspan="3">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                @if($titulo->cobrancaGateway->pix_qr_code_base64)
                                    <div>
                                        <img src="data:image/png;base64,{{ $titulo->cobrancaGateway->pix_qr_code_base64 }}" width="90" height="90" style="border: 1px solid #ccc; padding: 2px; background: #fff;" alt="QR Code Pix">
                                    </div>
                                @endif
                                <div style="flex: 1;">
                                    <textarea readonly class="form-input" style="width: 100%; height: 60px; font-size: 11px; font-family: monospace;" id="input-pix">{{ $titulo->cobrancaGateway->pix_copia_cola }}</textarea>
                                    <button type="button" class="btn btn-sm" style="margin-top: 4px;" onclick="navigator.clipboard.writeText(document.getElementById('input-pix').value); alert('Código Pix copiado!');">
                                        <i class="fa fa-copy"></i> Copiar Código Pix
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endif
            @else
                <tr>
                    <td class="formlttd" colspan="4" style="padding: 15px; text-align: center;">
                        <span style="color: #666; margin-right: 15px;">Este título ainda não foi emitido no Asaas.</span>
                        @if(!$titulo->isLiquidado())
                            <form method="post" action="{{ route('financeiro.titulos.emitir-asaas', $titulo->id) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-green">
                                    <i class="fa fa-cloud-upload"></i> Emitir Cobrança Asaas (Boleto Híbrido / Pix)
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Histórico de Pagamentos e Baixas -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 25px;">
        <thead>
            <tr>
                <th colspan="7" class="formdktd">
                    <i class="fa fa-history"></i> Histórico de Pagamentos / Baixas ({{ $titulo->pagamentos->count() }})
                </th>
            </tr>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="100">Data</th>
                <th width="130">Forma</th>
                <th width="100">Origem</th>
                <th width="130" style="text-align: right;">Valor Pago</th>
                <th width="150">Autenticação (Hash)</th>
                <th width="100" style="text-align: center;">Status</th>
                <th width="100" style="text-align: center;">Estorno</th>
            </tr>
        </thead>
        <tbody>
            @forelse($titulo->pagamentos as $pag)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($pag->data_pagamento)->format('d/m/Y') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $pag->forma_pagamento)) }}</td>
                    <td>{{ ucfirst($pag->origem_baixa) }}</td>
                    <td style="text-align: right; font-weight: 700; color: #438944;">
                        R$ {{ number_format($pag->valor_pago, 2, ',', '.') }}
                    </td>
                    <td><code style="font-size: 11px;">{{ substr($pag->comprovante_hash, 0, 16) }}...</code></td>
                    <td style="text-align: center;">
                        @if($pag->estornado)
                            <span class="badge badge-vencido">Estornado</span>
                        @else
                            <span class="badge badge-pago">Liquidado</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if(!$pag->estornado)
                            <form method="post" action="{{ route('financeiro.baixas.estornar', $pag->id) }}" onsubmit="return confirm('Deseja realmente estornar este recebimento? O saldo do título será recalculado.');" style="display: inline;">
                                @csrf
                                <input type="hidden" name="motivo_estorno" value="Estorno administrativo presencial">
                                <button type="submit" class="btn btn-sm" style="color: #cc0000; padding: 2px 6px;" title="Estornar">
                                    <i class="fa fa-undo"></i> Estornar
                                </button>
                            </form>
                        @else
                            <span style="font-size: 11px; color: #888;">{{ $pag->motivo_estorno }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 15px; color: #777;">
                        Nenhum pagamento registrado para este título até o momento.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
