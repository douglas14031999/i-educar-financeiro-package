@extends('financeiro::layouts.app')

@section('title', 'Mensalidades e Títulos')
@section('breadcrumb', 'Mensalidades e Títulos')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-barcode"></i> Títulos e Mensalidades Escolares
        </h2>
        <div>
            <a href="{{ route('financeiro.baixas.create') }}" class="btn btn-green">
                <i class="fa fa-money"></i> Baixar Título no Balcão
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div style="background-color: #ffffff; border: 1px solid #cddce6; padding: 12px; margin-bottom: 15px; border-radius: 3px;">
        <form method="get" action="{{ route('financeiro.titulos.index') }}" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar por Responsável, CPF ou Observação..." class="form-input" style="max-width: 320px;">

            <label class="form" style="margin: 0; font-size: 13px;">Status:</label>
            <select name="status" class="form-input" style="max-width: 140px;">
                <option value="">Todos</option>
                <option value="aberto" {{ $status === 'aberto' ? 'selected' : '' }}>Abertos</option>
                <option value="parcial" {{ $status === 'parcial' ? 'selected' : '' }}>Parciais</option>
                <option value="pago" {{ $status === 'pago' ? 'selected' : '' }}>Pagos</option>
                <option value="vencido" {{ $status === 'vencido' ? 'selected' : '' }}>Vencidos</option>
                <option value="cancelado" {{ $status === 'cancelado' ? 'selected' : '' }}>Cancelados</option>
            </select>

            <label class="form" style="margin: 0; font-size: 13px;">Competência:</label>
            <input type="text" name="competencia" value="{{ $competencia }}" placeholder="AAAA-MM" class="form-input" style="max-width: 110px;">

            <button type="submit" class="btn btn-primary">
                <i class="fa fa-filter"></i> Filtrar
            </button>
            @if($busca || $status || $competencia)
                <a href="{{ route('financeiro.titulos.index') }}" class="btn">Limpar</a>
            @endif
        </form>
    </div>

    <!-- Tabela de Listagem -->
    <table class="table-default" border="0" cellpadding="2" cellspacing="0">
        <thead>
            <tr>
                <th colspan="7" class="formdktd">
                    <i class="fa fa-list"></i> Mensalidades ({{ $titulos->total() }})
                </th>
            </tr>
            <tr style="background-color: #dbe7ef; font-size: 13px;">
                <th width="90">Vencimento</th>
                <th width="80" style="text-align: center;">Parcela</th>
                <th>Responsável Financeiro</th>
                <th>Descrição / Contrato</th>
                <th width="120" style="text-align: right;">Saldo Atual</th>
                <th width="100" style="text-align: center;">Status</th>
                <th width="140" style="text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($titulos as $titulo)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</td>
                    <td style="text-align: center; font-weight: 700;">
                        {{ $titulo->numero_parcela == 0 ? 'Matr.' : sprintf('%02d/%02d', $titulo->numero_parcela, $titulo->total_parcelas) }}
                    </td>
                    <td>
                        <a href="{{ route('financeiro.responsaveis.show', $titulo->responsavel_financeiro_id) }}">
                            <strong>{{ $titulo->responsavel->nome ?? '-' }}</strong>
                        </a>
                    </td>
                    <td>{{ $titulo->observacoes ?? ucfirst($titulo->tipo) }}</td>
                    <td style="text-align: right; font-weight: 700; color: #47728f;">
                        R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $titulo->status }}">{{ ucfirst($titulo->status) }}</span>
                    </td>
                    <td style="text-align: center;">
                        <a href="{{ route('financeiro.titulos.show', $titulo->id) }}" class="btn btn-sm" title="Detalhes do Título">
                            <i class="fa fa-eye"></i>
                        </a>
                        @if($titulo->status === 'aberto' || $titulo->status === 'parcial' || $titulo->status === 'vencido')
                            <a href="{{ route('financeiro.titulos.notificar-whatsapp', $titulo->id) }}" target="_blank" class="btn btn-sm btn-green" title="Enviar Cobrança / Link por WhatsApp">
                                <i class="fa fa-whatsapp"></i>
                            </a>
                            <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}" class="btn btn-sm" style="background-color: #47728f; color: #fff;" title="Baixa Presencial">
                                <i class="fa fa-money"></i>
                            </a>
                        @elseif($titulo->status === 'pago')
                            <a href="{{ route('financeiro.titulos.recibo-pdf', $titulo->id) }}" class="btn btn-sm" title="Recibo" target="_blank">
                                <i class="fa fa-print"></i>
                            </a>
                        @endif
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 25px; color: #777;">
                        Nenhuma mensalidade encontrada com os filtros selecionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 15px;">
        {{ $titulos->appends(['busca' => $busca, 'status' => $status, 'competencia' => $competencia])->links() }}
    </div>
@endsection
