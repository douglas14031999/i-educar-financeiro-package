@extends('financeiro::layouts.app')

@section('title', 'DRE Escolar')
@section('breadcrumb', 'Relatórios > DRE')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div>
            <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
                <i class="fa fa-balance-scale"></i> Demonstração do Resultado do Exercício - DRE ({{ $ano }})
            </h2>
            <span style="font-size: 12px; color: #666;">
                Receitas brutas, bolsas/descontos concedidos, despesas operacionais e resultado líquido
            </span>
        </div>
        <div>
            <form method="get" action="{{ route('financeiro.relatorios.dre') }}" style="display: inline-flex; gap: 5px; align-items: center;">
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

    <!-- Estrutura Contábil da DRE -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="4" cellspacing="0" style="margin-bottom: 25px; border: 1px solid #ccdce6;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="2" height="26">
                    <b><i class="fa fa-calculator"></i> Demonstração do Resultado Contábil</b>
                </td>
            </tr>
            <!-- 1. Receita Bruta -->
            <tr>
                <td class="formmdtd" style="font-size: 14px; font-weight: bold; color: #333;">
                    (+) 1. RECEITA BRUTA COM MENSALIDADES E MATRÍCULAS
                </td>
                <td class="formmdtd" style="text-align: right; font-size: 14px; font-weight: bold; width: 220px; color: #438944;">
                    R$ {{ number_format($receitaBruta, 2, ',', '.') }}
                </td>
            </tr>

            <!-- 2. Deduções -->
            <tr>
                <td class="formlttd" style="font-size: 13px; color: #555; padding-left: 25px;">
                    (-) 2. DEDUÇÕES DA RECEITA BRUTA (Bolsas de Estudo, Descontos Irmãos, Pontualidade)
                </td>
                <td class="formlttd" style="text-align: right; font-size: 13px; color: #d9534f;">
                    (R$ {{ number_format($deducoes, 2, ',', '.') }})
                </td>
            </tr>

            <!-- 3. Receita Líquida -->
            <tr style="background-color: #f5f9fd;">
                <td class="formmdtd" style="font-size: 14px; font-weight: bold; color: #47728f;">
                    (=) 3. RECEITA OPERACIONAL LÍQUIDA
                </td>
                <td class="formmdtd" style="text-align: right; font-size: 14px; font-weight: bold; color: #47728f;">
                    R$ {{ number_format($receitaLiquida, 2, ',', '.') }}
                </td>
            </tr>

            <!-- 4. Despesas Operacionais -->
            <tr>
                <td class="formdktd" colspan="2" height="22" style="background-color: #e5edf2; color: #47728f;">
                    <b>(-) 4. DESPESAS OPERACIONAIS POR CATEGORIA</b>
                </td>
            </tr>
            @forelse($despesasPorCategoria as $desp)
                <tr>
                    <td class="formlttd" style="font-size: 13px; padding-left: 25px;">
                        • {{ $desp->planoConta->descricao ?? 'Outras Despesas Operacionais' }}
                    </td>
                    <td class="formlttd" style="text-align: right; font-size: 13px; color: #d9534f;">
                        (R$ {{ number_format($desp->total, 2, ',', '.') }})
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="formlttd" colspan="2" style="padding-left: 25px; color: #888;">
                        Nenhuma despesa paga registrada no exercício de {{ $ano }}.
                    </td>
                </tr>
            @endforelse

            <tr style="background-color: #f9f9f9;">
                <td class="formmdtd" style="font-weight: bold; padding-left: 25px; color: #333;">
                    TOTAL DAS DESPESAS OPERACIONAIS:
                </td>
                <td class="formmdtd" style="text-align: right; font-weight: bold; color: #d9534f;">
                    (R$ {{ number_format($totalDespesasOperacionais, 2, ',', '.') }})
                </td>
            </tr>

            <!-- 5. Resultado Líquido Final -->
            <tr style="background-color: {{ $resultadoLiquido >= 0 ? '#eaf4eb' : '#fdf2f2' }}; border-top: 2px solid #ccdce6;">
                <td style="padding: 10px; font-size: 15px; font-weight: 700; color: {{ $resultadoLiquido >= 0 ? '#438944' : '#cc0000' }};">
                    (=) 5. RESULTADO LÍQUIDO DO EXERCÍCIO ({{ $resultadoLiquido >= 0 ? 'SUPERÁVIT' : 'DÉFICIT' }})
                </td>
                <td style="padding: 10px; text-align: right; font-size: 16px; font-weight: 700; color: {{ $resultadoLiquido >= 0 ? '#438944' : '#cc0000' }};">
                    R$ {{ number_format($resultadoLiquido, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>
@endsection
