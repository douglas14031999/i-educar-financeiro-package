# Guia de Estilo e Design System Oficial - Módulo Financeiro i-Educar

Este documento estabelece as diretrizes visuais, tokens e componentes reutilizáveis para o desenvolvimento de todas as telas do **Módulo Financeiro**, garantindo 100% de conformidade com a identidade visual do **i-Educar**.

---

## 1. Princípios de Design

1. **Essência do i-Educar**:
   - A interface deve parecer nativa do ecossistema i-Educar / Portabilis, integrando-se organicamente aos menus, tipografia e convenções do sistema.
   - **Proibido o uso de roxo/violeta** e layouts genéricos de SaaS modernos que destoem do padrão governamental/educacional.
   - Layout fluido, limpo, de alta legibilidade para operadores de secretaria escolar e tesouraria.

2. **Consistência de Ações**:
   - O botão de ação primária positiva (ex: cadastrar, nova baixa, salvar) sempre utiliza o verde institucional do i-Educar (`#0ac336` / `#09b031`).
   - Ações de navegação, relatórios ou consulta utilizam o azul institucional (`#47728f`).
   - Ações de cancelamento, retorno ou secundárias utilizam contorno neutro (`.btn-ieducar-outline`).

3. **Hierarquia e Espaçamento**:
   - Todo módulo é envelopado em `#financeiro-module` com padding de `15px 20px`.
   - Cabeçalhos de página padronizados com ícone FontAwesome, título em `#1b4c6b` e subtítulo descritivo em `#64748b`.

---

## 2. Tokens de Design (Paleta de Cores e Tipografia)

| Token | Valor Hex | Aplicação |
|---|---|---|
| `--color-primary` | `#47728f` | Azul clássico i-Educar (cabeçalhos de caixa, botões de consulta, links principais) |
| `--color-primary-dark` | `#1b4c6b` | Azul escuro (títulos H2, valores monetários principais, foco) |
| `--color-border` | `#cddce6` | Bordas de caixas, inputs, separadores e contornos |
| `--color-thead-bg` | `#ccdce6` | Fundo de cabeçalhos de coluna em tabelas |
| `--color-row-odd` | `#f5f9fd` | Linhas ímpares em tabelas (zebrado oficial) |
| `--color-row-even` | `#ffffff` | Linhas pares em tabelas |
| `--color-row-hover` | `#eaf2f8` | Efeito hover em linhas clicáveis |
| `--color-green` | `#0ac336` | Verde oficial i-Educar (botões de adição, confirmação e badges de pago) |
| `--color-green-hover` | `#09b031` | Hover dos botões verdes |
| `--color-red` | `#d9534f` | Vermelho de alerta (inadimplência, títulos vencidos, exclusão) |
| `--color-amber` | `#f0ad4e` | Âmbar de atenção (títulos pendentes, avisos de régua) |
| `--font-family` | `"Open Sans", Arial, sans-serif` | Tipografia padrão do sistema |

---

## 3. Componentes Padronizados

### 3.1. Cabeçalho de Tela (`.financeiro-header`)

Utilizado no topo de todas as telas (listagens, dashboards, relatórios e formulários):

```html
<div class="financeiro-header">
    <div class="financeiro-header-title">
        <h2>
            <i class="fa fa-users" style="color: #47728f;"></i> Título da Funcionalidade
        </h2>
        <span class="financeiro-header-desc">
            Breve descrição da finalidade e escopo desta funcionalidade na secretaria escolar
        </span>
    </div>
    <div class="financeiro-header-actions">
        <a href="{{ route('...') }}" class="btn-ieducar-action btn-ieducar-green">
            <i class="fa fa-plus-circle"></i> Ação Primária
        </a>
        <a href="{{ route('...') }}" class="btn-ieducar-action btn-ieducar-blue">
            <i class="fa fa-list"></i> Ação Secundária
        </a>
    </div>
</div>
```

---

### 3.2. Botões Padronizados (`.btn-ieducar-action`)

Os botões utilizam raio sutil (`border-radius: 3px`), tipografia limpa, ícone alinhado e transição suave:

```html
<!-- Botão Verde Primário (Cadastrar, Baixar, Salvar) -->
<button type="submit" class="btn-ieducar-action btn-ieducar-green">
    <i class="fa fa-check"></i> Salvar Registro
</button>

<!-- Botão Azul Institucional (Visualizar, Consultar, Filtrar) -->
<a href="#" class="btn-ieducar-action btn-ieducar-blue">
    <i class="fa fa-search"></i> Consultar
</a>

<!-- Botão Outline Neutro (Voltar, Cancelar, Limpar Filtros) -->
<a href="#" class="btn-ieducar-action btn-ieducar-outline">
    <i class="fa fa-arrow-left"></i> Voltar
</a>

<!-- Botão Vermelho de Perigo (Cancelar Contrato, Estornar) -->
<button type="button" class="btn-ieducar-action btn-ieducar-danger">
    <i class="fa fa-trash"></i> Cancelar / Excluir
</button>
```

---

### 3.3. Barra de Filtros e Busca (`.financeiro-filter-box`)

Substitui caixas cruas de busca por um painel integrado, com inputs e selects uniformizados:

```html
<div class="financeiro-filter-box">
    <form method="get" action="{{ route('...') }}" class="financeiro-filter-form">
        <div class="filter-item">
            <label class="filter-label">Buscar:</label>
            <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar por Nome, CPF..." class="ieducar-input" style="min-width: 280px;">
        </div>

        <div class="filter-item">
            <label class="filter-label">Status:</label>
            <select name="status" class="ieducar-select">
                <option value="">Todos</option>
                <option value="ativo">Ativo</option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn-ieducar-action btn-ieducar-blue">
                <i class="fa fa-filter"></i> Filtrar
            </button>
            @if(request()->anyFilled(['busca', 'status']))
                <a href="{{ route('...') }}" class="btn-ieducar-action btn-ieducar-outline">
                    Limpar
                </a>
            @endif
        </div>
    </form>
</div>
```

---

### 3.4. Caixa de Tabela (`.financeiro-table-box`)

Tabelas completas com cabeçalho azul escuro i-Educar, zebrado oficial e botões de ação compactos:

```html
<div class="financeiro-table-box">
    <div class="financeiro-box-header">
        <span>
            <i class="fa fa-list"></i> Título da Listagem ({{ $registros->total() }})
        </span>
        <span class="box-header-info">Total de registros: {{ $registros->total() }}</span>
    </div>
    <div class="financeiro-table-responsive">
        <table class="financeiro-table">
            <thead>
                <tr>
                    <th width="120" style="text-align: center;">Data</th>
                    <th>Descrição</th>
                    <th width="140" style="text-align: right;">Valor</th>
                    <th width="110" style="text-align: center;">Status</th>
                    <th width="130" style="text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registros as $item)
                    <tr>
                        <td align="center">{{ $item->data }}</td>
                        <td><strong>{{ $item->descricao }}</strong></td>
                        <td align="right" class="col-valor">R$ {{ number_format($item->valor, 2, ',', '.') }}</td>
                        <td align="center">
                            <span class="badge-status badge-{{ $item->status }}">{{ ucfirst($item->status) }}</span>
                        </td>
                        <td align="center">
                            <div class="table-actions-group">
                                <a href="{{ route('...', $item->id) }}" class="btn-table-action btn-action-blue" title="Ver Detalhes">
                                    <i class="fa fa-eye"></i> Detalhes
                                </a>
                                <a href="{{ route('...', $item->id) }}" class="btn-table-action btn-action-amber" title="Editar">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="financeiro-empty-state">
                            <i class="fa fa-folder-open-o empty-icon"></i>
                            <div class="empty-title">Nenhum registro encontrado.</div>
                            <div class="empty-desc">Utilize os filtros acima ou cadastre um novo registro.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

---

### 3.5. Badges de Status (`.badge-status`)

Tons suaves com contorno correspondente para rápida identificação visual:

```html
<span class="badge-status badge-pago">Pago / Ativo</span>
<span class="badge-status badge-pendente">Pendente / Aberto</span>
<span class="badge-status badge-vencido">Vencido</span>
<span class="badge-status badge-cancelado">Cancelado</span>
<span class="badge-status badge-parcial">Parcial</span>
```

---

### 3.6. Ações Compactas na Tabela (`.btn-table-action`)

Evitam o uso de botões gigantes desproporcionais dentro das células:

```html
<div class="table-actions-group">
    <a href="#" class="btn-table-action btn-action-blue" title="Visualizar">
        <i class="fa fa-eye"></i> Detalhes
    </a>
    <a href="#" class="btn-table-action btn-action-green" title="WhatsApp">
        <i class="fa fa-whatsapp"></i>
    </a>
    <a href="#" class="btn-table-action btn-action-amber" title="Editar">
        <i class="fa fa-pencil"></i>
    </a>
    <a href="#" class="btn-table-action btn-action-default" title="Recibo">
        <i class="fa fa-print"></i>
    </a>
</div>
```

---

### 3.7. Formulários Oficiais (`.tablecadastro` / `.formcadastro`)

Formulários de criação e edição mantêm as classes tradicionais do i-Educar (`tablecadastro`, `formdktd`, `formmdtd`, `formlttd`), mas refinados com os novos botões e campos limpos:

```html
<form id="formcadastro" method="post" action="{{ route('...') }}">
    @csrf
    <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0">
        <tbody>
            <tr>
                <td class="formdktd" colspan="2" height="28">
                    <b><i class="fa fa-edit"></i> Dados do Cadastro</b>
                </td>
            </tr>
            <tr>
                <td class="formmdtd" width="220" valign="top">
                    <label class="form">Nome do Campo <span class="campo_obrigatorio">*</span></label>
                </td>
                <td class="formmdtd">
                    <input type="text" name="campo" class="ieducar-input" required style="max-width: 450px;">
                </td>
            </tr>
            <tr>
                <td class="formlttd" colspan="2" style="padding: 16px;">
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn-ieducar-action btn-ieducar-green">
                            <i class="fa fa-check"></i> Salvar Registro
                        </button>
                        <a href="{{ route('...') }}" class="btn-ieducar-action btn-ieducar-outline">
                            Cancelar
                        </a>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</form>
```

---

## 4. Checklist para Novas Telas

Ao criar ou editar qualquer tela do módulo:
- [ ] O arquivo herda de `@extends('financeiro::layouts.app')`?
- [ ] O topo possui `.financeiro-header` com título, ícone e botões `.btn-ieducar-action`?
- [ ] Caso haja filtros, utilizou `.financeiro-filter-box` e `.ieducar-input` / `.ieducar-select`?
- [ ] A listagem está dentro de `.financeiro-table-box` com cabeçalho azul e tabela `.financeiro-table`?
- [ ] As linhas da tabela utilizam o zebrado i-Educar e hover suave?
- [ ] Os botões na tabela utilizam `.btn-table-action` (e nunca `.btn` nativo sem estilo)?
- [ ] Os status utilizam `.badge-status`?
- [ ] O *Empty State* contém mensagem amigável com orientação?
