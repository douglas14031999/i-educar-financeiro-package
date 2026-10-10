@extends('financeiro::layouts.app')

@section('title', 'Painel de Inadimplência & Aging')
@section('breadcrumb', 'Relatórios > Inadimplência')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div>
            <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-exclamation-triangle"></i> Painel de Inadimplência Escolar (Aging List)
            </h2>
            <span style="font-size: 12px; color: #666;">
                Monitoramento por faixas de atraso e verificação de débitos para renovação de matrícula (Lei 9.870/99)
            </span>
        </div>
        <div>
            <a href="{{ route('financeiro.relatorios.index') }}" class="btn btn-sm">
                <i class="fa fa-arrow-left"></i> Voltar aos Relatórios
            </a>
        </div>
    </div>

    <!-- Indicadores por Faixa de Atraso (Aging) -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 25px;">
        @foreach($aging['faixas'] as $chave => $f)
            <div style="background: #fff; border: 1px solid #ccdce6; border-left: 4px solid {{ $chave === 'faixa_acima_90' ? '#d9534f' : ($chave === 'faixa_61_90' ? '#f0ad4e' : '#47728f') }}; padding: 12px;">
                <div style="font-size: 12px; color: #666; font-weight: 600;">{{ $f['nome'] }}</div>
                <div style="font-size: 18px; font-weight: 700; color: #333; margin: 4px 0;">
                    R$ {{ number_format($f['total'], 2, ',', '.') }}
                </div>
                <div style="font-size: 11px; color: #888;">
                    {{ $f['qtd'] }} {{ $f['qtd'] === 1 ? 'título vencido' : 'títulos vencidos' }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- Total Geral Inadimplente -->
    <div style="background-color: #fdf7f7; border: 1px solid #ebccd1; padding: 12px 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="color: #a94442; font-weight: bold; font-size: 14px;">
                <i class="fa fa-calculator"></i> Total Geral Inadimplente:
            </span>
            <span style="font-size: 18px; font-weight: 700; color: #a94442; margin-left: 10px;">
                R$ {{ number_format($aging['total_geral'], 2, ',', '.') }}
            </span>
        </div>
        <div style="font-size: 12px; color: #a94442;">
            Volume Total: <strong>{{ $aging['total_titulos'] }} parcelas em atraso</strong>
        </div>
    </div>

    <!-- Verificação de Aluno para Rematrícula (Art. 5º da Lei Federal nº 9.870/1999) -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="3" cellspacing="0" style="margin-bottom: 25px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-user-check"></i> Consulta de Situação para Rematrícula (Lei 9.870/99)</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" colspan="4" style="padding: 10px;">
                    <form method="get" action="{{ route('financeiro.relatorios.inadimplencia') }}" style="display: flex; gap: 10px; align-items: center;">
                        <label style="font-weight: bold; margin: 0;">Código do Aluno (i-Educar):</label>
                        <input type="number" name="aluno_id" value="{{ $alunoId }}" placeholder="Ex: 1024" class="form-input" style="width: 140px;" required>
                        <button type="submit" class="botaosalvar">
                            <i class="fa fa-search"></i> Verificar Débitos
                        </button>
                        @if($alunoId)
                            <a href="{{ route('financeiro.relatorios.inadimplencia') }}" class="btn btn-sm">Limpar Consulta</a>
                        @endif
                    </form>
                </td>
            </tr>
            @if($resultadoAluno)
                <tr>
                    <td class="formlttd" colspan="4" style="padding: 15px;">
                        @if($resultadoAluno['pode_rematricular'])
                            <div style="background-color: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; padding: 12px; border-radius: 3px;">
                                <h4 style="margin: 0 0 5px 0;"><i class="fa fa-check-circle"></i> REMATRÍCULA AUTORIZADA: Aluno Regular</h4>
                                <p style="margin: 0; font-size: 13px;">{{ $resultadoAluno['fundamentacao_legal'] }}</p>
                            </div>
                        @else
                            <div style="background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; padding: 12px; border-radius: 3px;">
                                <h4 style="margin: 0 0 5px 0;"><i class="fa fa-ban"></i> REMATRÍCULA BLOQUEADA POR INADIMPLÊNCIA</h4>
                                <p style="margin: 0 0 8px 0; font-size: 13px;">
                                    O aluno possui <strong>{{ $resultadoAluno['qtd_titulos_vencidos'] }} título(s) vencido(s)</strong> totalizando 
                                    <strong>R$ {{ number_format($resultadoAluno['total_debito'], 2, ',', '.') }}</strong> 
                                    (Maior atraso: {{ $resultadoAluno['maior_atraso_dias'] }} dias).
                                </p>
                                <p style="margin: 0; font-size: 11px; color: #733;">
                                    <i class="fa fa-legal"></i> {{ $resultadoAluno['fundamentacao_legal'] }}
                                </p>
                            </div>
                        @endif
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Listagem dos Principais Títulos Vencidos por Faixa -->
    <h3 style="color: #47728f; font-size: 15px; margin-bottom: 10px;">
        <i class="fa fa-list"></i> Títulos Inadimplentes (Amostragem Recente)
    </h3>
    <table class="table-default" border="0" cellpadding="2" cellspacing="0">
        <thead>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="70">ID</th>
                <th width="90">Vencimento</th>
                <th width="100">Dias Atraso</th>
                <th>Responsável / Aluno</th>
                <th width="110">Competência</th>
                <th width="110" style="text-align: right;">Saldo Devedor</th>
                <th width="160" style="text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @php $countExibidos = 0; @endphp
            @foreach($aging['faixas'] as $f)
                @foreach($f['titulos'] as $titulo)
                    @if($countExibidos < 50)
                        @php $countExibidos++; @endphp
                        <tr>
                            <td>#{{ $titulo->id }}</td>
                            <td style="color: #cc0000; font-weight: bold;">
                                {{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}
                            </td>
                            <td>
                                <span class="badge badge-vencido">
                                    {{ \Carbon\Carbon::parse($titulo->data_vencimento)->diffInDays(now()) }} dias
                                </span>
                            </td>
                            <td>
                                <strong>{{ $titulo->responsavel->nome ?? '-' }}</strong><br>
                                <span style="font-size: 11px; color: #666;">Aluno #{{ $titulo->aluno_id }}</span>
                            </td>
                            <td>{{ $titulo->competencia }}</td>
                            <td style="text-align: right; font-weight: bold; color: #cc0000;">
                                R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('financeiro.titulos.show', $titulo->id) }}" class="btn btn-sm" title="Ver Detalhes">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ route('financeiro.titulos.notificar-whatsapp', $titulo->id) }}" target="_blank" class="btn btn-sm btn-green" title="Cobrar por WhatsApp">
                                    <i class="fa fa-whatsapp"></i>
                                </a>
                                <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}" class="btn btn-sm" style="background-color: #47728f; color: #fff;" title="Baixa">
                                    <i class="fa fa-money"></i>
                                </a>
                            </td>
                        </tr>
                    @endif
                @endforeach
            @endforeach

            @if($countExibidos === 0)
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #438944; font-weight: bold;">
                        <i class="fa fa-check-circle"></i> Parabéns! Nenhuma mensalidade em atraso encontrada.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
@endsection
