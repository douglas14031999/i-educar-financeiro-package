@extends('financeiro::layouts.app')

@section('title', 'Renegociação e Acordos de Dívidas')
@section('breadcrumb', 'Renegociação de Dívidas')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-handshake-o"></i> Renegociação de Dívidas e Acordos Financeiros
        </h2>
    </div>

    <!-- Etapa 1: Selecionar Responsável -->
    <table class="tablecadastro" width="100%" border="0" cellpadding="3" cellspacing="0" style="margin-bottom: 25px;">
        <tbody>
            <tr>
                <td class="formdktd" colspan="4" height="24">
                    <b><i class="fa fa-user"></i> 1. Selecionar Responsável Financeiro Devedor</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" colspan="4" style="padding: 10px;">
                    <form method="get" action="{{ route('financeiro.renegociacao.index') }}" style="display: flex; gap: 15px; align-items: center;">
                        <select name="responsavel_id" class="form-input" style="width: 450px;" onchange="this.form.submit();" required>
                            <option value="">-- Selecione o Responsável para Consultar Débitos --</option>
                            @foreach($responsaveis as $resp)
                                <option value="{{ $resp->id }}" {{ $responsavelId == $resp->id ? 'selected' : '' }}>
                                    {{ $resp->nome }} (CPF: {{ $resp->cpf_cnpj_formatado }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="botaosalvar">
                            <i class="fa fa-search"></i> Carregar Títulos
                        </button>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>

    @if($responsavelId)
        <!-- Etapa 2: Seleção dos Títulos e Parâmetros da Simulação -->
        <form method="post" action="{{ route('financeiro.renegociacao.simular') }}">
            @csrf
            <table class="tablecadastro" width="100%" border="0" cellpadding="3" cellspacing="0" style="margin-bottom: 25px;">
                <tbody>
                    <tr>
                        <td class="formdktd" colspan="4" height="24">
                            <b><i class="fa fa-sliders"></i> 2. Definir Condições da Renegociação</b>
                        </td>
                    </tr>
                    <tr>
                        <td class="formmdtd" width="180"><strong>Desconto no Total (R$):</strong></td>
                        <td class="formmdtd">
                            <input type="number" step="0.01" name="desconto_valor" value="0.00" class="form-input" style="width: 130px;">
                            <span style="font-size: 11px; color: #666; margin-left: 5px;">Abatimento sobre o total consolidado</span>
                        </td>
                        <td class="formmdtd" width="160"><strong>Quantidade de Parcelas:</strong></td>
                        <td class="formmdtd">
                            <select name="qtd_parcelas" class="form-input" style="width: 100px;">
                                @for($p = 1; $p <= 24; $p++)
                                    <option value="{{ $p }}">{{ $p }}x</option>
                                @endfor
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="formlttd"><strong>1º Vencimento:</strong></td>
                        <td class="formlttd" colspan="3">
                            <input type="date" name="primeiro_vencimento" value="{{ \Carbon\Carbon::today()->addDays(5)->format('Y-m-d') }}" class="form-input" style="width: 150px;" required>
                            <span style="font-size: 11px; color: #666; margin-left: 5px;">Data da primeira parcela do acordo</span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3 style="color: #47728f; font-size: 15px; margin-bottom: 10px;">
                <i class="fa fa-check-square-o"></i> 3. Selecione os Títulos que Entrarão no Acordo
            </h3>
            <table class="table-default" border="0" cellpadding="2" cellspacing="0" style="margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #dbe7ef; font-size: 13px;">
                        <th width="40" style="text-align: center;">
                            <input type="checkbox" id="check-todos" onclick="document.querySelectorAll('.check-titulo').forEach(cb => cb.checked = this.checked);">
                        </th>
                        <th width="70">ID</th>
                        <th>Competência / Descrição</th>
                        <th width="100">Vencimento</th>
                        <th width="120" style="text-align: right;">Valor Nominal</th>
                        <th width="120" style="text-align: right;">Saldo Devedor</th>
                        <th width="100" style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($titulosPendentes as $t)
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" name="titulos_ids[]" value="{{ $t->id }}" class="check-titulo" checked>
                            </td>
                            <td>#{{ $t->id }}</td>
                            <td>
                                <strong>{{ $t->competencia }}</strong> - {{ $t->observacoes ?? 'Mensalidade Escolar' }}
                                <span style="font-size: 11px; color: #666;">(Aluno #{{ $t->aluno_id }})</span>
                            </td>
                            <td style="color: {{ $t->isVencido() ? '#cc0000' : '#333' }}; font-weight: bold;">
                                {{ \Carbon\Carbon::parse($t->data_vencimento)->format('d/m/Y') }}
                            </td>
                            <td style="text-align: right;">R$ {{ number_format($t->valor_nominal, 2, ',', '.') }}</td>
                            <td style="text-align: right; font-weight: bold; color: #cc0000;">
                                R$ {{ number_format($t->valor_saldo, 2, ',', '.') }}
                            </td>
                            <td style="text-align: center;">
                                <span class="badge badge-{{ $t->status }}">{{ ucfirst($t->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #438944; font-weight: bold;">
                                Este responsável não possui mensalidades em aberto ou vencidas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($titulosPendentes->isNotEmpty())
                <div style="text-align: right; margin-bottom: 30px;">
                    <button type="submit" class="botaosalvar" style="font-size: 14px; padding: 8px 18px;">
                        <i class="fa fa-calculator"></i> Simular Proposta de Acordo &raquo;
                    </button>
                </div>
            @endif
        </form>
    @endif

    <!-- Acordos Firmados Recentemente -->
    <h3 style="color: #47728f; font-size: 15px; margin-top: 30px; margin-bottom: 10px;">
        <i class="fa fa-history"></i> Acordos e Termos Recentes Firmados
    </h3>
    <table class="table-default" border="0" cellpadding="2" cellspacing="0">
        <thead>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="120">Nº do Acordo</th>
                <th width="100">Data</th>
                <th>Responsável</th>
                <th width="120" style="text-align: right;">Valor Acordo</th>
                <th width="90" style="text-align: center;">Parcelas</th>
                <th width="90" style="text-align: center;">Status</th>
                <th width="100" style="text-align: center;">Termo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($acordosRecentes as $ac)
                <tr>
                    <td><strong>{{ $ac->numero_acordo }}</strong></td>
                    <td>{{ $ac->created_at->format('d/m/Y') }}</td>
                    <td>{{ $ac->responsavel->nome ?? '-' }}</td>
                    <td style="text-align: right; font-weight: bold; color: #438944;">
                        R$ {{ number_format($ac->valor_acordo, 2, ',', '.') }}
                    </td>
                    <td style="text-align: center;">{{ $ac->qtd_parcelas }}x</td>
                    <td style="text-align: center;">
                        <span class="badge badge-info">{{ ucfirst($ac->status) }}</span>
                    </td>
                    <td style="text-align: center;">
                        <a href="{{ route('financeiro.renegociacao.termo-pdf', $ac->id) }}" target="_blank" class="btn btn-sm" title="Imprimir Termo de Acordo">
                            <i class="fa fa-print"></i> Termo
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 15px; color: #777;">
                        Nenhum acordo formalizado registrado até o momento.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
