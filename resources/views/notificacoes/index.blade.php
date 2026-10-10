@extends('financeiro::layouts.app')

@section('title', 'Histórico de Notificações e Mensagens')
@section('breadcrumb', 'Histórico de Notificações')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div>
            <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-envelope-o"></i> Histórico de Notificações Financeiras
            </h2>
            <span style="font-size: 12px; color: #666;">
                Rastreabilidade de todas as mensagens e cobranças enviadas por WhatsApp, E-mail e SMS aos pais
            </span>
        </div>
        <div>
            <a href="{{ route('financeiro.configuracoes.regua') }}" class="btn btn-sm">
                <i class="fa fa-sliders"></i> Ajustar Regras da Régua
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="3" cellspacing="0" style="margin-bottom: 20px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-filter"></i> Filtrar Disparos</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" colspan="4" style="padding: 10px;">
                    <form method="get" action="{{ route('financeiro.notificacoes.index') }}" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        <div>
                            <label style="font-size: 12px; font-weight: bold; margin: 0;">Canal:</label>
                            <select name="canal" class="form-input" style="width: 140px;">
                                <option value="">Todos</option>
                                <option value="whatsapp" {{ $canal === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                <option value="email" {{ $canal === 'email' ? 'selected' : '' }}>E-mail</option>
                                <option value="sms" {{ $canal === 'sms' ? 'selected' : '' }}>SMS</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 12px; font-weight: bold; margin: 0;">Buscar Destinatário, Responsável ou Texto:</label>
                            <input type="text" name="busca" value="{{ $busca }}" placeholder="Ex: nome do pai, número ou parte da mensagem..." class="form-input" style="width: 100%;">
                        </div>
                        <div style="align-self: flex-end;">
                            <button type="submit" class="botaosalvar">
                                <i class="fa fa-search"></i> Filtrar
                            </button>
                            @if($busca || $canal)
                                <a href="{{ route('financeiro.notificacoes.index') }}" class="btn btn-sm" style="margin-left: 5px;">Limpar</a>
                            @endif
                        </div>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Tabela de Histórico -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 25px;">
        <thead>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="130">Data / Hora</th>
                <th width="90">Canal</th>
                <th width="200">Destinatário</th>
                <th>Conteúdo da Notificação</th>
                <th width="90" style="text-align: center;">Título</th>
                <th width="90" style="text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($envios as $envio)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($envio->enviado_em)->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="badge badge-info">{{ strtoupper($envio->canal) }}</span>
                    </td>
                    <td>
                        <strong>{{ $envio->responsavel->nome ?? 'Responsável' }}</strong><br>
                        <span style="font-size: 11px; color: #666;">{{ $envio->destinatario }}</span>
                    </td>
                    <td style="font-size: 12px;">
                        {{ $envio->mensagem_enviada }}
                    </td>
                    <td style="text-align: center;">
                        @if($envio->titulo_id)
                            <a href="{{ route('financeiro.titulos.show', $envio->titulo_id) }}" class="btn btn-sm" style="padding: 2px 6px;">
                                #{{ $envio->titulo_id }}
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if($envio->sucesso)
                            <span class="badge badge-pago">Sucesso</span>
                        @else
                            <span class="badge badge-vencido" title="{{ $envio->erro }}">Erro</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 25px; color: #777;">
                        Nenhuma notificação registrada no período.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 15px;">
        {{ $envios->appends(request()->query())->links() }}
    </div>
@endsection
