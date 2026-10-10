@extends('financeiro::layouts.app')

@section('title', 'Mensalidades e Títulos')
@section('breadcrumb', 'Mensalidades e Títulos')

@section('financeiro-content')
    <!-- Cabeçalho -->
    <div class="financeiro-header">
        <div class="financeiro-header-title">
            <h2>
                <i class="fa fa-barcode" style="color: #47728f;"></i> Títulos e Mensalidades Escolares
            </h2>
            <span class="financeiro-header-desc">
                Gerenciamento de cobranças, mensalidades e taxas escolares
            </span>
        </div>
        <div class="financeiro-header-actions">
            <a href="{{ route('financeiro.baixas.create') }}" class="btn-ieducar-action btn-ieducar-green">
                <i class="fa fa-money"></i> Baixar Título no Balcão
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="financeiro-filter-box">
        <form method="get" action="{{ route('financeiro.titulos.index') }}" class="financeiro-filter-form">
            <div class="filter-item">
                <label class="filter-label">Buscar:</label>
                <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar por Responsável, CPF ou Observação..." class="ieducar-input" style="min-width: 320px;">
            </div>

            <div class="filter-item">
                <label class="filter-label">Status:</label>
                <select name="status" class="ieducar-select" style="min-width: 140px;">
                    <option value="">Todos</option>
                    <option value="aberto" {{ $status === 'aberto' ? 'selected' : '' }}>Abertos</option>
                    <option value="parcial" {{ $status === 'parcial' ? 'selected' : '' }}>Parciais</option>
                    <option value="pago" {{ $status === 'pago' ? 'selected' : '' }}>Pagos</option>
                    <option value="vencido" {{ $status === 'vencido' ? 'selected' : '' }}>Vencidos</option>
                    <option value="cancelado" {{ $status === 'cancelado' ? 'selected' : '' }}>Cancelados</option>
                </select>
            </div>

            <div class="filter-item">
                <label class="filter-label">Competência:</label>
                <input type="text" name="competencia" value="{{ $competencia }}" placeholder="AAAA-MM" class="ieducar-input" style="max-width: 110px;">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-ieducar-action btn-ieducar-blue">
                    <i class="fa fa-filter"></i> Filtrar
                </button>
                @if($busca || $status || $competencia)
                    <a href="{{ route('financeiro.titulos.index') }}" class="btn-ieducar-action btn-ieducar-outline">
                        Limpar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabela de Listagem -->
    <div class="financeiro-table-box">
        <div class="financeiro-box-header">
            <span>
                <i class="fa fa-list"></i> Mensalidades ({{ $titulos->total() }})
            </span>
            <span class="box-header-info">Total de registros: {{ $titulos->total() }}</span>
        </div>
        <div class="financeiro-table-responsive">
            <table class="financeiro-table">
                <thead>
                    <tr>
                        <th width="90" style="text-align: center;">Vencimento</th>
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
                            <td align="center">{{ \Carbon\Carbon::parse($titulo->data_vencimento)->format('d/m/Y') }}</td>
                            <td align="center" style="font-weight: 700;">
                                {{ $titulo->numero_parcela == 0 ? 'Matr.' : sprintf('%02d/%02d', $titulo->numero_parcela, $titulo->total_parcelas) }}
                            </td>
                            <td>
                                <a href="{{ route('financeiro.responsaveis.show', $titulo->responsavel_financeiro_id) }}">
                                    <strong>{{ $titulo->responsavel->nome ?? '-' }}</strong>
                                </a>
                            </td>
                            <td>{{ $titulo->observacoes ?? ucfirst($titulo->tipo) }}</td>
                            <td align="right" class="col-valor">
                                R$ {{ number_format($titulo->valor_saldo, 2, ',', '.') }}
                            </td>
                            <td align="center">
                                <span class="badge-status badge-{{ $titulo->status }}">{{ ucfirst($titulo->status) }}</span>
                            </td>
                            <td align="center">
                                <div class="table-actions-group">
                                    <a href="{{ route('financeiro.titulos.show', $titulo->id) }}" class="btn-table-action btn-action-blue" title="Detalhes do Título">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    @if($titulo->status === 'aberto' || $titulo->status === 'parcial' || $titulo->status === 'vencido')
                                        <a href="{{ route('financeiro.titulos.notificar-whatsapp', $titulo->id) }}" target="_blank" class="btn-table-action btn-action-green" title="Enviar Cobrança / Link por WhatsApp">
                                            <i class="fa fa-whatsapp"></i>
                                        </a>
                                        <a href="{{ route('financeiro.baixas.create', ['titulo_id' => $titulo->id]) }}" class="btn-table-action btn-action-amber" title="Baixa Presencial">
                                            <i class="fa fa-money"></i>
                                        </a>
                                    @elseif($titulo->status === 'pago')
                                        <a href="{{ route('financeiro.titulos.recibo-pdf', $titulo->id) }}" class="btn-table-action btn-action-default" title="Recibo" target="_blank">
                                            <i class="fa fa-print"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="financeiro-empty-state">
                                <i class="fa fa-folder-open-o empty-icon"></i>
                                <div class="empty-title">Nenhuma mensalidade encontrada.</div>
                                <div class="empty-desc">Utilize os filtros acima para refinar sua busca ou cadastre um novo registro.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 15px;">
        {{ $titulos->appends(['busca' => $busca, 'status' => $status, 'competencia' => $competencia])->links() }}
    </div>
@endsection
