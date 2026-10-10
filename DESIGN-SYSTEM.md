# Guia de Estilo e Design System Oficial - Módulo Financeiro i-Educar

Este documento estabelece o **padrão visual oficial e obrigatório** para o desenvolvimento e evolução de todas as telas do **Módulo Financeiro** do i-Educar. 

O padrão baseia-se na interface do Painel Geral de Gestão Financeira (`modelo tela dash`), harmonizando a identidade visual nativa do i-Educar (tom oficial **Azul i-Educar `#47728f`**, esmeralda `#059669`, ardósia `#334155`) com total integração e isolamento em relação ao sistema i-Educar.

---

## 1. Princípios de Design

1. **Fidelidade à Identidade Visual do i-Educar**:
   - A interface adota como cor primária oficial o **Azul i-Educar (`#47728f`)** — a exata tonalidade utilizada na barra de navegação superior e elementos do i-Educar — acompanhada de sua variação escura de profundidade (`#355972`), Esmeralda (`#059669`), Ardósia (`#334155`) e fundo neutro (`#f4f7f9`).
   - Tipografia moderna **Inter** com hierarquia visual clara, pesos equilibrados (400, 500, 600, 700) e legibilidade perfeita.
   - **Proibido o uso de roxo/violeta** ou temas escuros saturados.

2. **Ocupação Total da Área Útil (Full Width)**:
   - As telas ocupam **100% da largura útil** (`w-full`), sem travas restritivas de `max-w` nem centralizações artificiais (`mx-auto`) que criem calhas vazias nas laterais em monitores modernos.
   - Espaçamento interno padronizado e equilibrado: `p-4 md:p-6` com ritmo vertical uniforme (`space-y-6`).
   - O contêiner da tela deve remover o padding legado da casca i-Educar via `@section('module-style', 'padding: 0;')`.

3. **Consistência de Ações e Cores Semânticas**:
   - **Ação Primária / Positiva** (Nova Baixa, Novo Plano, Salvar): Verde Esmeralda (`bg-emerald-600` / hover `bg-emerald-700`).
   - **Ação Secundária / Navegação Global** (Ver Mensalidades, Histórico): Ardósia Escuro (`bg-slate-700` / hover `bg-slate-800`).
   - **Ação Neutra / Filtros / Linha de Tabela**: Branco com borda suave (`bg-white border border-slate-300 text-slate-700` / hover `bg-slate-50`).
   - **Ação de Atenção / Renegociação / Edição**: Âmbar / Dourado (`amber-500` / `amber-600`).
   - **Ação de Alerta / Inadimplência / Cancelamento**: Vermelho / Rosa Intenso (`rose-500` / `rose-600`).

4. **Isolamento de Estilos (Zero Leaks)**:
   - Todo o CSS deve ser estritamente escopado (ex.: `.financeiro-dashboard-container` ou `.financeiro-container`).
   - **Proibido o uso de compiladores CDN em tempo de execução** (`cdn.tailwindcss.com` é bloqueado pelo Prototype.js do i-Educar). Todo CSS deve ser estático e embutido via Blade `<style>`.
   - Todo elemento `<svg>` deve conter **obrigatoriamente atributos explícitos `width` e `height`** (`width="24" height="24"`, `width="16" height="16"`, etc.) para evitar distorções no carregamento.

---

## 2. Tokens de Design (Cores e Tipografia)

### 2.1. Paleta de Cores

| Nome / Token | Hex | Classe Tailwind / Uso | Aplicação |
|---|---|---|---|
| **Azul i-Educar Principal** | `#47728f` | `text-brand-petroleum` / `bg-brand-petroleum` / `text-brand-ieducar` | Identidade oficial do i-Educar, títulos, ícones de módulo, links |
| **Azul i-Educar Escuro** | `#355972` | `bg-brand-petroleum-dark` / `to-[#355972]` | Fim de gradientes, hover de links e botões, contrastes profundos |
| **Azul i-Educar Claro** | `#5d85a0` | `bg-brand-petroleum-light` | Focos e bordas ativas |
| **Azul i-Educar Fundo Suave** | `rgba(71,114,143,0.12)` | `bg-brand-petroleum/10` | Fundo de caixas de ícones no cabeçalho |
| **Gradiente Destaque** | `from-[#47728f] to-[#355972]` | `bg-gradient-to-r` | Cabeçalho principal de tabelas e painéis (mesma tonalidade do topo i-Educar) |
| **Fundo de Tela** | `#f4f7f9` | `bg-[#f4f7f9]` | Fundo geral da página do módulo |
| **Superfície Card** | `#ffffff` | `bg-white` | Fundo de cards, tabelas e caixas |
| **Borda Neutra Suave** | `#e2e8f0` | `border-slate-200` | Bordas de cards, divisores de tabelas e inputs |
| **Verde Esmeralda** | `#059669` | `bg-emerald-600` | Botão primário, indicador de quitação, valores recebidos |
| **Verde Badge** | `bg-emerald-50` / `text-emerald-700` | `border-emerald-200` | Status Pago / Liquidado / Ativo |
| **Azul Cyan** | `#0891b2` | `bg-cyan-600` / `text-cyan-600` | Previsões, parcelas vincendas, indicadores de fluxo |
| **Rosa / Vermelho** | `#e11d48` | `bg-rose-500` / `text-rose-700` | Inadimplência, parcelas vencidas, alertas críticos |
| **Rosa Badge** | `bg-rose-50` / `text-rose-700` | `border-rose-200` | Status Em Atraso / Cancelado |
| **Âmbar / Atenção** | `#d97706` | `bg-amber-500` / `text-amber-700` | Taxas de inadimplência, pendências operacionais |
| **Âmbar Badge** | `bg-amber-50` / `text-amber-700` | `border-amber-200/70` | Status Pendente / Aguardando |
| **Texto Título** | `#0f172a` | `text-slate-900` | Títulos H1/H2, valores numéricos principais |
| **Texto Secundário** | `#64748b` | `text-slate-500` | Descrições, subtítulos, legendas de gráficos |

### 2.2. Tipografia

- **Família da Fonte**: `'Inter', sans-serif`
- **Importação**: `<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">`
- **Hierarquia de Tamanhos**:
  - H1 de Tela: `text-xl md:text-2xl font-bold tracking-tight text-slate-900`
  - Subtítulo de Tela: `text-xs md:text-sm text-slate-500`
  - Valores / Métricas: `text-2xl font-bold tracking-tight text-slate-900`
  - Rótulos de Métricas: `text-[11px] font-bold tracking-wider uppercase text-slate-600`
  - Cabeçalho de Tabela: `text-[11px] font-bold text-slate-600 uppercase tracking-wider`
  - Célula de Tabela: `text-xs md:text-sm text-slate-700`
  - Badges: `text-xs font-medium`

---

## 3. Componentes e Estrutura de Código

### 3.1. Estrutura Base de uma Tela (Blade View)

Todas as telas do módulo financeiro devem herdar de `financeiro::layouts.app`, remover o padding legado e incluir a casca `.financeiro-dashboard-container`:

```blade
@extends('financeiro::layouts.app')

@section('title', 'Nome da Tela Financeira')
@section('breadcrumb', 'Nome no Breadcrumb')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="screen-container">
        
        <!-- 1. Cabeçalho da Tela -->
        ...

        <!-- 2. Conteúdo Principal (Cards / Filtros / Tabela / Formulário) -->
        ...

    </main>
</div>
@endsection
```

---

### 3.2. Cabeçalho de Tela com Ações

O cabeçalho traz identificação do módulo, ícone representativo, título de alto contraste e os botões de ação de maior frequência:

```html
<header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
    <div>
        <div class="flex items-center gap-2.5 text-brand-petroleum">
            <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v4M12 14v4M16 14v4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
            </div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                Título da Funcionalidade
            </h1>
        </div>
        <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
            Breve descrição operacional do escopo e objetivo desta tela
        </p>
    </div>
    
    <!-- Ações no Topo -->
    <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
        <!-- Ação Primária (Verde) -->
        <a href="{{ route('...') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm hover:shadow transition duration-150" style="text-decoration: none;">
            <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
            Nova Ação Principal
        </a>
        
        <!-- Ação Secundária (Ardósia) -->
        <a href="{{ route('...') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-800 active:bg-slate-900 text-white text-sm font-medium shadow-sm transition duration-150" style="text-decoration: none;">
            <svg aria-hidden="true" class="w-4 h-4 text-slate-300" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M4 6h16M4 10h16M4 14h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
            Listagem Geral
        </a>
    </div>
</header>
```

---

### 3.3. Cards de Indicadores / Métricas (KPI Grid)

Grid de 4 colunas com barra superior de status de 4px (`h-1`), tipografia compacta e valor em destaque:

```html
<section aria-label="Indicadores Financeiros" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" data-purpose="kpi-grid">
    <!-- Card Verde (Recebido / Sucesso) -->
    <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
        <div class="absolute top-0 inset-x-0 h-1 bg-emerald-500"></div>
        <div class="flex items-center justify-between text-slate-500 mb-2">
            <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Receita Recebida</span>
            <span class="p-1 bg-emerald-50 text-emerald-600">
                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
            </span>
        </div>
        <div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-slate-900">R$ 48.750,00</span>
            </div>
            <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Liquidações via Asaas, Pix e Caixa</p>
        </div>
    </article>

    <!-- Card Cyan (A Receber / Fluxo) -->
    <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
        <div class="absolute top-0 inset-x-0 h-1 bg-cyan-600"></div>
        <div class="flex items-center justify-between text-slate-500 mb-2">
            <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">A Receber no Mês</span>
            <span class="p-1 bg-cyan-50 text-cyan-600">
                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
            </span>
        </div>
        <div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-slate-900">R$ 15.200,00</span>
            </div>
            <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Parcelas vincendas neste período</p>
        </div>
    </article>

    <!-- Card Rose (Em Atraso / Alerta) -->
    <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
        <div class="absolute top-0 inset-x-0 h-1 bg-rose-500"></div>
        <div class="flex items-center justify-between text-slate-500 mb-2">
            <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Total em Atraso</span>
            <span class="p-1 bg-rose-50 text-rose-600">
                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
            </span>
        </div>
        <div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-slate-900">R$ 3.420,00</span>
            </div>
            <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;"><strong class="font-medium text-slate-700">12</strong> parcelas aguardando acerto</p>
        </div>
    </article>

    <!-- Card Âmbar (Taxa / Atenção) -->
    <article class="bg-white shadow-xs border border-slate-200/90 relative overflow-hidden p-4 flex flex-col justify-between hover:border-slate-300 transition">
        <div class="absolute top-0 inset-x-0 h-1 bg-amber-500"></div>
        <div class="flex items-center justify-between text-slate-500 mb-2">
            <span class="text-[11px] font-bold tracking-wider uppercase text-slate-600">Taxa de Inadimplência</span>
            <span class="p-1 bg-amber-50 text-amber-600">
                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
            </span>
        </div>
        <div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-slate-900">4,8%</span>
            </div>
            <p class="text-xs text-slate-500 mt-1" style="margin: 4px 0 0 0;">Em relação à receita faturada</p>
        </div>
    </article>
</section>
```

---

### 3.4. Tabela de Dados e Listagens

Contêiner em fundo branco, cabeçalho gradiente petróleo com texto branco, barra de abas e ações rápidas, e linhas com hover suave:

```html
<section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="table-container">
    <!-- Cabeçalho com Gradiente Azul i-Educar -->
    <div class="bg-gradient-to-r from-[#47728f] to-[#355972] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
            <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                Título da Tabela de Registros
            </h2>
        </div>
        <a class="text-xs font-medium text-cyan-100 hover:text-white transition flex items-center gap-1" href="{{ route('...') }}" style="text-decoration: none;">
            Histórico completo <span aria-hidden="true">»</span>
        </a>
    </div>
    
    <!-- Barra de Filtros Rápidos e Ações -->
    <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex p-0.5 bg-slate-200/80 text-xs font-medium text-slate-600">
            <button class="px-3 py-1 bg-white text-slate-800 shadow-xs" type="button">Todos ({{ $registros->total() ?? 0 }})</button>
            <button class="px-3 py-1 hover:text-slate-900 transition" type="button">Em Atraso</button>
            <button class="px-3 py-1 hover:text-slate-900 transition" type="button">Liquidados</button>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('...') }}" class="text-xs font-medium inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-2xs" style="text-decoration: none;">
                <svg class="w-3.5 h-3.5 text-emerald-600" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
                Nova Ação Rápida
            </a>
        </div>
    </div>

    <!-- Tabela Responsiva -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs md:text-sm m-0 border-0" style="margin: 0;">
            <thead>
                <tr class="bg-slate-100/90 text-[11px] font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                    <th class="py-3 px-4" scope="col">Data / Vencimento</th>
                    <th class="py-3 px-4" scope="col">Identificação</th>
                    <th class="py-3 px-4" scope="col">Responsável</th>
                    <th class="py-3 px-4 text-center" scope="col">Parcela</th>
                    <th class="py-3 px-4 text-right" scope="col">Valor</th>
                    <th class="py-3 px-4 text-center" scope="col">Status</th>
                    <th class="py-3 px-4 text-center" scope="col">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-slate-700">
                @forelse($registros as $item)
                    @php
                        $isAtrasado = $item->status === 'vencido';
                        $rowBg = $isAtrasado ? 'hover:bg-rose-50/40 bg-rose-50/20' : 'hover:bg-slate-50/80';
                    @endphp
                    <tr class="{{ $rowBg }} transition-colors">
                        <td class="py-3 px-4 whitespace-nowrap font-medium {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                            {{ \Carbon\Carbon::parse($item->data)->format('d/m/Y') }}
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-900">{{ $item->descricao }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $item->responsavel_nome ?? '-' }}</td>
                        <td class="py-3 px-4 text-center whitespace-nowrap font-medium">{{ $item->parcela ?? '-' }}</td>
                        <td class="py-3 px-4 text-right font-bold {{ $isAtrasado ? 'text-rose-700' : 'text-slate-900' }}">
                            R$ {{ number_format($item->valor, 2, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            <!-- Badges Pill -->
                            @if($item->status === 'pago')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Liquidado</span>
                            @elseif($isAtrasado)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Em Atraso</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200/70">Pendente</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            <a href="{{ route('...', $item->id) }}" class="text-xs font-medium text-brand-petroleum hover:underline">
                                Detalhes
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">
                            <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Nenhum registro encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Rodapé da Tabela -->
    <footer class="p-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
        <span>Mostrando {{ count($registros) }} registros carregados</span>
    </footer>
</section>
```

---

### 3.5. Badges de Status (Pill Badges)

Os badges utilizam borda arredondada completa (`rounded-full`), fonte `text-xs font-medium`, fundo suave e contorno correspondente:

```html
<!-- Liquidado / Pago / Ativo -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Liquidado</span>

<!-- Pendente / Em Aberto -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200/70">Pendente</span>

<!-- Vencido / Em Atraso -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Em Atraso</span>

<!-- Cancelado / Inativo -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">Cancelado</span>
```

---

### 3.6. Cards Inferiores (Automação, Configurações e Relatórios)

Grid responsivo de 2 colunas com borda sutil, ícones temáticos em caixas coloridas e listas de links interativos:

```html
<section class="grid grid-cols-1 md:grid-cols-2 gap-6" data-purpose="info-panels">
    <!-- Card de Automação / Integração -->
    <article class="bg-white border border-slate-200 shadow-xs p-5 flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-2.5 text-brand-petroleum mb-3 pb-2 border-b border-slate-100">
                <span class="p-1.5 bg-blue-50 text-blue-700">
                    <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                </span>
                <h3 class="font-bold text-slate-900 text-sm md:text-base" style="margin: 0;">Régua de Cobrança Automática</h3>
            </div>
            <p class="text-xs md:text-sm text-slate-600 leading-relaxed mb-4" style="margin-top: 0;">
                Disparo diário de lembretes e notificações de vencimento com links diretos de Boleto e Pix para os responsáveis por E-mail e WhatsApp.
            </p>
            <div class="flex items-center gap-2 text-xs text-emerald-700 font-medium bg-emerald-50/80 px-3 py-2 mb-4 border border-emerald-100">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Integração ativa: Lembretes automáticos enviados às 08h00.
            </div>
        </div>
        <div>
            <a class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-petroleum hover:text-brand-petroleum-dark group transition" href="{{ route('financeiro.configuracoes.gateway') }}" style="text-decoration: none;">
                <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                Configurações do Gateway Asaas e Chaves de Integração »
            </a>
        </div>
    </article>

    <!-- Card de Relatórios Gerenciais -->
    <article class="bg-white border border-slate-200 shadow-xs p-5 flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-2.5 text-brand-petroleum mb-3 pb-2 border-b border-slate-100">
                <span class="p-1.5 bg-amber-50 text-amber-700">
                    <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                </span>
                <h3 class="font-bold text-slate-900 text-sm md:text-base" style="margin: 0;">Relatórios Gerenciais</h3>
            </div>
            <p class="text-xs text-slate-500 mb-3" style="margin-top: 0;">
                Emissão de relatórios e certidões oficiais conforme legislação educacional:
            </p>
            <ul class="space-y-2 text-xs md:text-sm" style="list-style: none; padding-left: 0;">
                <li>
                    <a class="flex items-center gap-2 text-brand-petroleum hover:text-cyan-700 transition font-medium group" href="{{ route('financeiro.relatorios.inadimplencia') }}" style="text-decoration: none;">
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        Relatório de Inadimplência
                    </a>
                </li>
                <li>
                    <a class="flex items-center gap-2 text-brand-petroleum hover:text-cyan-700 transition font-medium group" href="{{ route('financeiro.relatorios.informe-irpf') }}" style="text-decoration: none;">
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-petroleum" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                        Demonstrativo de Pagamentos para IRPF
                    </a>
                </li>
            </ul>
        </div>
    </article>
</section>
```

---

### 3.7. Formulários e Inputs Modernos

<section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="form-container">
    <div class="bg-gradient-to-r from-[#47728f] to-[#355972] px-5 py-3.5 text-white">
        <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
            Dados do Cadastro
        </h2>
    </div>

    <form method="post" action="{{ route('...') }}" class="p-5 md:p-6 space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Campo de Texto -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Nome / Descrição <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="nome" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
            </div>

            <!-- Campo Select -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Status <span class="text-rose-500">*</span>
                </label>
                <select name="status" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
            </div>
        </div>

        <!-- Botões de Ação do Formulário -->
        <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
            <a href="{{ route('...') }}" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium transition shadow-2xs" style="text-decoration: none;">
                Cancelar
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm transition">
                <svg aria-hidden="true" class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
                Salvar Registro
            </button>
        </div>
    </form>
</section>

---

## 4. Diretrizes Técnicas de Implementação

1. **Nunca usar Tailwind via CDN (`cdn.tailwindcss.com`)**:
   - O Prototype.js que acompanha o i-Educar causa conflito de execução assíncrona, quebrando o CSS.
   - Sempre compile o CSS usando o script do pacote `node build_dashboard_css.js` ou declare os estilos estáticos minificados em Blade.
2. **Atributos explícitos em SVGs**:
   - Sempre declare `width="N"` e `height="N"` junto com `class="w-N h-N"`. Isso impede que ícones se expandam caso o CSS sofra qualquer atraso de renderização.
3. **Escopo do Módulo**:
   - Utilize a classe base `.financeiro-dashboard-container` envolvendo toda a view.
   - Declare `@section('module-style', 'padding: 0;')` para eliminar calhas e margens do container externo.

---

## 5. Checklist de Verificação para Telas

Ao criar ou atualizar qualquer tela do módulo financeiro:

- [ ] A tela herda de `@extends('financeiro::layouts.app')`?
- [ ] Está definido `@section('module-style', 'padding: 0;')`?
- [ ] O contêiner principal utiliza `w-full` (sem `max-w-[1440px]` nem `mx-auto`)?
- [ ] O cabeçalho possui o ícone SVG com caixa `p-2 bg-brand-petroleum/10` e título `text-xl md:text-2xl font-bold`?
- [ ] Ações primárias utilizam o botão verde esmeralda (`bg-emerald-600`)?
- [ ] Ações secundárias utilizam o botão ardósia (`bg-slate-700`)?
- [ ] Se houver tabela, o cabeçalho possui gradiente `from-[#47728f] to-[#355972]` e as linhas têm hover suave?
- [ ] Os badges de status utilizam o padrão pill `rounded-full text-xs font-medium px-2.5 py-0.5`?
- [ ] Todos os elementos `<svg>` possuem atributos nativos `width` e `height`?
- [ ] Os dados são alimentados dinamicamente através do Controller?
