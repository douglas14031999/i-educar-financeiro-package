@extends('financeiro::layouts.app')

@section('title', 'Relatórios Financeiros')
@section('breadcrumb', 'Relatórios')

@section('financeiro-content')
    <h2 style="color: #47728f; margin-top: 0; margin-bottom: 20px; font-size: 18px; font-weight: 700;">
        <i class="fa fa-bar-chart"></i> Central de Relatórios Financeiros
    </h2>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px;">
        <!-- Card Inadimplência -->
        <div style="border: 1px solid #ccdce6; background: #fff; padding: 15px; border-top: 3px solid #d9534f;">
            <h3 style="margin-top: 0; color: #d9534f; font-size: 16px;">
                <i class="fa fa-exclamation-triangle"></i> Painel de Inadimplência & Aging
            </h3>
            <p style="font-size: 12px; color: #555; height: 36px;">
                Faixas de atraso (1-30, 31-60, 61-90 e >90 dias) e verificação de débitos para rematrícula (Lei 9.870/99).
            </p>
            <a href="{{ route('financeiro.relatorios.inadimplencia') }}" class="btn btn-sm btn-green" style="display: inline-block;">
                Acessar Inadimplência &raquo;
            </a>
        </div>

        <!-- Card Fluxo de Caixa -->
        <div style="border: 1px solid #ccdce6; background: #fff; padding: 15px; border-top: 3px solid #47728f;">
            <h3 style="margin-top: 0; color: #47728f; font-size: 16px;">
                <i class="fa fa-line-chart"></i> Fluxo de Caixa Realizado
            </h3>
            <p style="font-size: 12px; color: #555; height: 36px;">
                Entradas efetivas vs despesas pagas consolidadas mês a mês para o ano letivo.
            </p>
            <a href="{{ route('financeiro.relatorios.fluxo-caixa') }}" class="btn btn-sm" style="background-color: #47728f; color: #fff; display: inline-block;">
                Acessar Fluxo de Caixa &raquo;
            </a>
        </div>

        <!-- Card DRE Escolar -->
        <div style="border: 1px solid #ccdce6; background: #fff; padding: 15px; border-top: 3px solid #438944;">
            <h3 style="margin-top: 0; color: #438944; font-size: 16px;">
                <i class="fa fa-balance-scale"></i> DRE Escolar
            </h3>
            <p style="font-size: 12px; color: #555; height: 36px;">
                Demonstração do Resultado do Exercício: Receita bruta, bolsas/descontos e despesas por centro de custo.
            </p>
            <a href="{{ route('financeiro.relatorios.dre') }}" class="btn btn-sm btn-green" style="display: inline-block;">
                Acessar DRE &raquo;
            </a>
        </div>

        <!-- Card Informe IRPF -->
        <div style="border: 1px solid #ccdce6; background: #fff; padding: 15px; border-top: 3px solid #f0ad4e;">
            <h3 style="margin-top: 0; color: #f0ad4e; font-size: 16px;">
                <i class="fa fa-file-text-o"></i> Informe de Pagamentos IRPF
            </h3>
            <p style="font-size: 12px; color: #555; height: 36px;">
                Declaração oficial com CPF, CNPJ e valores anuais pagos por dependente para o Imposto de Renda.
            </p>
            <a href="{{ route('financeiro.relatorios.informe-irpf') }}" class="btn btn-sm" style="background-color: #f0ad4e; color: #fff; display: inline-block;">
                Emitir Informe IRPF &raquo;
            </a>
        </div>

        <!-- Card Quitação Anual Lei 12.007 -->
        <div style="border: 1px solid #ccdce6; background: #fff; padding: 15px; border-top: 3px solid #5bc0de;">
            <h3 style="margin-top: 0; color: #5bc0de; font-size: 16px;">
                <i class="fa fa-certificate"></i> Quitação Anual (Lei 12.007/09)
            </h3>
            <p style="font-size: 12px; color: #555; height: 36px;">
                Declaração obrigatória por lei atestando a inexistência de pendências financeiras no exercício anterior.
            </p>
            <a href="{{ route('financeiro.relatorios.quitacao-anual') }}" class="btn btn-sm" style="background-color: #5bc0de; color: #fff; display: inline-block;">
                Emitir Quitação Anual &raquo;
            </a>
        </div>
    </div>
@endsection
