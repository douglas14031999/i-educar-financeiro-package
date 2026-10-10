@extends('financeiro::layouts.app')

@section('title', 'Fluxo de Caixa Realizado')
@section('breadcrumb', 'Relatórios > Fluxo de Caixa')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div>
            <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-line-chart"></i> Fluxo de Caixa Realizado (Exercício {{ $ano }})
            </h2>
            <span style="font-size: 12px; color: #666;">
                Entradas efetivas de mensalidades vs despesas pagas consolidadas mês a mês
            </span>
        </div>
        <div>
            <form method="get" action="{{ route('financeiro.relatorios.fluxo-caixa') }}" style="display: inline-flex; gap: 5px; align-items: center;">
                <label style="font-size: 12px; margin: 0; font-weight: bold;">Ano:</label>
                <select name="ano" class="form-input" onchange="this.form.submit();" style="width: 100px;">
                    @for($y = date('Y') + 1; $y >= date('Y') - 4; $y--)
                        <option value="{{ $y }}" {{ $ano == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <a href="{{ route('financeiro.relatorios.index') }}" class="btn btn-sm" style="margin-left: 8px;">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Tabela Mês a Mês do Fluxo de Caixa -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 25px;">
        <thead>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th>Mês de Referência</th>
                <th style="text-align: right; width: 200px;">Receitas Realizadas (Entradas)</th>
                <th style="text-align: right; width: 200px;">Despesas Pagas (Saídas)</th>
                <th style="text-align: right; width: 200px;">Saldo Operacional do Mês</th>
            </tr>
        </thead>
        <tbody>
            @php
                $mesesNomes = [
                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                ];
            @endphp

            @for($m = 1; $m <= 12; $m++)
                @php
                    $recMes = $receitasPorMes[$m] ?? 0.0;
                    $despMes = $despesasPorMes[$m] ?? 0.0;
                    $saldoMes = $recMes - $despMes;
                @endphp
                <tr>
                    <td><strong>{{ $mesesNomes[$m] }} / {{ $ano }}</strong></td>
                    <td style="text-align: right; color: #438944; font-weight: bold;">
                        R$ {{ number_format($recMes, 2, ',', '.') }}
                    </td>
                    <td style="text-align: right; color: #d9534f; font-weight: bold;">
                        R$ {{ number_format($despMes, 2, ',', '.') }}
                    </td>
                    <td style="text-align: right; font-weight: 700; color: {{ $saldoMes >= 0 ? '#438944' : '#cc0000' }};">
                        R$ {{ number_format($saldoMes, 2, ',', '.') }}
                    </td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr style="background-color: #ccdce6; font-weight: bold; font-size: 14px;">
                <td>TOTAL CONSOLIDADO {{ $ano }}:</td>
                <td style="text-align: right; color: #438944;">
                    R$ {{ number_format($totalReceitasAno, 2, ',', '.') }}
                </td>
                <td style="text-align: right; color: #d9534f;">
                    R$ {{ number_format($totalDespesasAno, 2, ',', '.') }}
                </td>
                <td style="text-align: right; color: {{ $saldoOperacionalAno >= 0 ? '#438944' : '#cc0000' }}; font-size: 15px;">
                    R$ {{ number_format($saldoOperacionalAno, 2, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>
@endsection
