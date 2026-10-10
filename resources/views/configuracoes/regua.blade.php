@extends('financeiro::layouts.app')

@section('title', 'Régua de Cobrança Escolar')
@section('breadcrumb', 'Configurações > Régua de Cobrança')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div>
            <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-bell-o"></i> Régua de Cobrança Automática
            </h2>
            <span style="font-size: 12px; color: #666;">
                Automação preventiva e reativa de avisos de vencimento e cobranças por WhatsApp e E-mail
            </span>
        </div>
        <div>
            <form method="post" action="{{ route('financeiro.configuracoes.regua.executar-manual') }}" style="display: inline;" onsubmit="return confirm('Deseja executar a varredura da régua de cobrança agora? Os títulos elegíveis serão notificados.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-green">
                    <i class="fa fa-play"></i> Processar Régua Agora
                </button>
            </form>
        </div>
    </div>

    <!-- Tabela explicativa de tags dinâmicas -->
    <div style="background-color: #f5f9fd; border: 1px solid #ccdce6; border-left: 4px solid #47728f; padding: 10px 15px; margin-bottom: 20px; font-size: 12px;">
        <strong style="color: #47728f;"><i class="fa fa-info-circle"></i> Tags disponíveis para personalização das mensagens:</strong><br>
        <code>{responsavel}</code> (Nome do Responsável), 
        <code>{aluno}</code> (Identificação do Aluno), 
        <code>{competencia}</code> (Ex: 04/2026), 
        <code>{vencimento}</code> (Data formatada), 
        <code>{valor}</code> (Valor do débito), 
        <code>{link_pagamento}</code> (Link direto da fatura Asaas/Pix), 
        <code>{linha_digitavel}</code> (Código de barras para pagamento).
    </div>

    <!-- Lista de Regras Configuráveis -->
    @foreach($regras as $regra)
        <form method="post" action="{{ route('financeiro.configuracoes.regua.atualizar', $regra->id) }}" style="margin-bottom: 20px;">
            @csrf
            <table class="tablecadastro" width="100%" border="0" cellpadding="3" cellspacing="0">
                <tbody>
                    <tr>
                        <td class="formdktd" colspan="4" height="24" style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <b>
                                    @if($regra->dias_gatilho < 0)
                                        <i class="fa fa-clock-o"></i> Pré-Vencimento ({{ abs($regra->dias_gatilho) }} dias antes)
                                    @elseif($regra->dias_gatilho == 0)
                                        <i class="fa fa-calendar-check-o"></i> No Dia do Vencimento (D0)
                                    @else
                                        <i class="fa fa-exclamation-triangle"></i> Pós-Vencimento ({{ $regra->dias_gatilho }} dias de atraso)
                                    @endif
                                    - {{ $regra->nome_regra }}
                                </b>
                            </div>
                            <div>
                                <label style="font-weight: normal; cursor: pointer; color: #fff; margin: 0;">
                                    <input type="checkbox" name="ativo" value="1" {{ $regra->ativo ? 'checked' : '' }}>
                                    <strong>Regra Ativa</strong>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="formmdtd" width="160"><strong>Nome da Regra:</strong></td>
                        <td class="formmdtd">
                            <input type="text" name="nome_regra" value="{{ $regra->nome_regra }}" class="form-input" style="width: 100%;" required>
                        </td>
                        <td class="formmdtd" width="140"><strong>Canal:</strong></td>
                        <td class="formmdtd">
                            <select name="canal" class="form-input" style="width: 160px;">
                                <option value="whatsapp" {{ $regra->canal === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                <option value="email" {{ $regra->canal === 'email' ? 'selected' : '' }}>E-mail</option>
                                <option value="sms" {{ $regra->canal === 'sms' ? 'selected' : '' }}>SMS</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="formlttd" valign="top"><strong>Mensagem Template:</strong></td>
                        <td class="formlttd" colspan="3">
                            <textarea name="corpo_template" class="form-input" style="width: 100%; height: 75px; font-family: monospace; font-size: 12px;" required>{{ $regra->corpo_template }}</textarea>
                            <div style="text-align: right; margin-top: 5px;">
                                <button type="submit" class="botaosalvar">
                                    <i class="fa fa-save"></i> Salvar Esta Regra
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    @endforeach

    <!-- Histórico Recente de Disparos -->
    <h3 style="color: #47728f; font-size: 15px; margin-top: 30px; margin-bottom: 10px;">
        <i class="fa fa-history"></i> Últimos Disparos Realizados pela Régua
    </h3>
    <table class="table-default" border="0" cellpadding="2" cellspacing="0">
        <thead>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="130">Data / Hora</th>
                <th width="180">Regra</th>
                <th>Responsável / Destinatário</th>
                <th width="100">Canal</th>
                <th width="90" style="text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ultimosEnvios as $envio)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($envio->enviado_em)->format('d/m/Y H:i') }}</td>
                    <td>{{ $envio->regra->nome_regra ?? 'Geral' }}</td>
                    <td>
                        <strong>{{ $envio->responsavel->nome ?? 'Responsável' }}</strong>
                        <span style="font-size: 11px; color: #666; margin-left: 5px;">({{ $envio->destinatario }})</span>
                    </td>
                    <td><span class="badge badge-info">{{ strtoupper($envio->canal) }}</span></td>
                    <td style="text-align: center;">
                        @if($envio->sucesso)
                            <span class="badge badge-pago">Enviado</span>
                        @else
                            <span class="badge badge-vencido" title="{{ $envio->erro }}">Falha</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 15px; color: #777;">
                        Nenhum disparo registrado até o momento.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
