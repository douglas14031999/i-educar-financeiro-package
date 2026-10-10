@extends('financeiro::layouts.app')

@section('title', 'Contrato ' . $contrato->numero_contrato)
@section('breadcrumb', 'Contrato ' . $contrato->numero_contrato)

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-file-text-o"></i> Contrato {{ $contrato->numero_contrato }}
        </h2>
        <div>
            @if($contrato->status === 'ativo')
                <button type="button" class="btn btn-sm" style="color: #cc0000;" onclick="document.getElementById('modal-cancelar').style.display='block';">
                    <i class="fa fa-ban"></i> Cancelar / Rescindir Contrato
                </button>
            @endif
            <a href="{{ route('financeiro.contratos.index') }}" class="btn btn-sm" style="margin-left: 5px;">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Tabela Resumo do Contrato -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 20px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-info-circle"></i> Dados do Contrato</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" width="160"><strong>Matrícula i-Educar:</strong></td>
                <td class="formmdtd">#{{ $contrato->matricula_id }} (Aluno #{{ $contrato->aluno_id }})</td>
                <td class="formmdtd" width="160"><strong>Ano Letivo:</strong></td>
                <td class="formmdtd">{{ $contrato->ano_letivo }}</td>
            </tr>
            <tr>
                <td class="formlttd"><strong>Responsável Principal:</strong></td>
                <td class="formlttd">
                    <a href="{{ route('financeiro.responsaveis.show', $contrato->responsavel_financeiro_id) }}">
                        <strong>{{ $contrato->responsavel->nome ?? 'Não informado' }}</strong>
                    </a>
                </td>
                <td class="formlttd"><strong>Status:</strong></td>
                <td class="formlttd">
                    <span class="badge badge-{{ $contrato->status === 'ativo' ? 'pago' : ($contrato->status === 'cancelado' ? 'vencido' : 'aberto') }}">
                        {{ ucfirst($contrato->status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="formmdtd"><strong>Plano:</strong></td>
                <td class="formmdtd">{{ $contrato->plano->nome ?? '-' }}</td>
                <td class="formmdtd"><strong>Dia Vencimento:</strong></td>
                <td class="formmdtd">Todo dia {{ sprintf('%02d', $contrato->dia_vencimento) }}</td>
            </tr>
            <tr>
                <td class="formlttd"><strong>Valor Bruto:</strong></td>
                <td class="formlttd">R$ {{ number_format($contrato->valor_total_bruto, 2, ',', '.') }}</td>
                <td class="formlttd"><strong>Valor Líquido Contratado:</strong></td>
                <td class="formlttd" style="font-weight: 700; color: #438944; font-size: 15px;">
                    R$ {{ number_format($contrato->valor_total_liquido, 2, ',', '.') }}
                </td>
            </tr>
            @if($contrato->motivo_cancelamento)
                <tr>
                    <td class="formmdtd" style="color: #cc0000;"><strong>Motivo Cancelamento:</strong></td>
                    <td class="formmdtd" colspan="3" style="color: #cc0000;">{{ $contrato->motivo_cancelamento }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Grade de Parcelas e Títulos do Contrato -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0">
        <thead>
            <tr>
                <th colspan="7" class="formdktd">
                    <i class="fa fa-barcode"></i> Grade de Parcelas Geradas ({{ $contrato->titulos->count() }})
                </th>
            </tr>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="80" style="text-align: center;">Parcela</th>
                <th width="100">Vencimento</th>
                <th>Quem Paga (Responsável)</th>
                <th>Descrição / Tipo</th>
                <th width="120" style="text-align: right;">Valor Saldo</th>
                <th width="100" style="text-align: center;">Status</th>
                <th width="140" style="text-align: center;">Cobrança / Ação</th>
            </tr>
        </thead>
        <tbody>
            @forelse($contrato->titulos as $titulo)
                <tr>
                    <td style="text-align: center; font-weight: 700;">
                        {{ $titulo->numero_parcela == 0 ? 'Matrícula' : sprintf('%02d/%02d', $titulo->numero_parcela, $titulo->total_parcelas) }}
                    </td>
                    <td>{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</td>
                    <td>{{ $titulo->responsavel->nome ?? '-' }}</td>
                    <td>{{ $titulo->observacoes ?? ucfirst($titulo->tipo) }}</td>
                    <td style="text-align: right; font-weight: 700; color: #47728f;">
                        R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $titulo->status }}">{{ ucfirst($titulo->status) }}</span>
                    </td>
                    <td style="text-align: center;">
                        @if($titulo->status === 'aberto' || $titulo->status === 'parcial')
                            <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}" class="btn btn-sm btn-green" title="Baixa no Balcão">
                                <i class="fa fa-money"></i> Baixar
                            </a>
                        @elseif($titulo->status === 'pago')
                            <a href="{{ route('financeiro.titulos.recibo-pdf', $titulo->id) }}" class="btn btn-sm" title="Imprimir Recibo" target="_blank">
                                <i class="fa fa-print"></i> Recibo
                            </a>
                        @else
                            <span style="font-size: 11px; color: #888;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 25px; color: #777;">
                        Nenhuma parcela gerada para este contrato.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Modal Simples de Cancelamento -->
    <div id="modal-cancelar" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 9999;">
        <div style="background-color: #ffffff; max-width: 500px; margin: 100px auto; border: 2px solid #ccdce6; border-radius: 4px; overflow: hidden;">
            <div style="background-color: #ccdce6; padding: 12px 15px; font-weight: 700; color: #47728f;">
                <i class="fa fa-warning"></i> Confirmar Cancelamento do Contrato
            </div>
            <form method="post" action="{{ route('financeiro.contratos.cancelar', $contrato->id) }}" style="padding: 20px;">
                @csrf
                <p style="font-size: 13px; color: #555; margin-top: 0;">
                    Ao cancelar este contrato, <strong>apenas as parcelas futuras não vencidas</strong> serão canceladas. Parcelas já vencidas ou débitos anteriores permanecerão em cobrança ativa.
                </p>
                <div style="margin-bottom: 15px;">
                    <label class="form">Motivo do Cancelamento <span class="campo_obrigatorio">*</span></label>
                    <textarea name="motivo" required rows="3" class="form-input" style="width: 100%; max-width: 100%;" placeholder="Ex: Transferência de escola solicitada pelos pais..."></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn geral-botao" onclick="document.getElementById('modal-cancelar').style.display='none';">Voltar</button>
                    <button type="submit" class="btn" style="background-color: #cc0000; color: #fff; border-color: #aa0000; margin-left: 8px;">Confirmar Cancelamento</button>
                </div>
            </form>
        </div>
    </div>
@endsection
