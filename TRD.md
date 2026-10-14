# TRD — Especificação Técnica e Arquitetura de Implementação

**Documento de Requisitos Técnicos (TRD)**  
**Versão:** 2.0.0  
**Data:** 13/10/2026  
**Status:** Aprovado  
**Framework:** Laravel (Package `douglas14031999/i-educar-financeiro-package`)  

---

## 1. Arquitetura do Componente de Navegação (Sub-Navbar)

### 1.1 Localização e Inclusão
- **Arquivo Criado:** `resources/views/partials/subnav.blade.php`
- **Inclusão:** Injetado diretamente em `resources/views/partials/page-header.blade.php` ou em `resources/views/layouts/app.blade.php`, garantindo presença automática em todas as telas sem duplicação de código.

### 1.2 Mapeamento de Rotas e Processos i-Educar
A barra secundária mapeia diretamente as constantes de processo definidas em `iEducar\Financeiro\Constants\ProcessosFinanceiro`:

```php
$navItems = [
    [
        'route'    => 'financeiro.dashboard',
        'label'    => 'Painel Geral',
        'process'  => ProcessosFinanceiro::MENU_DASHBOARD,
        'icon'     => 'chart-bar',
        'active'   => request()->routeIs('financeiro.dashboard*'),
    ],
    [
        'route'    => 'financeiro.titulos.index',
        'label'    => 'Mensalidades e Títulos',
        'process'  => ProcessosFinanceiro::MENU_TITULOS,
        'icon'     => 'document-text',
        'active'   => request()->routeIs('financeiro.titulos*'),
    ],
    [
        'route'    => 'financeiro.baixas.create',
        'label'    => 'Caixa / Balcão',
        'process'  => ProcessosFinanceiro::MENU_BAIXA_MANUAL,
        'icon'     => 'cash',
        'active'   => request()->routeIs('financeiro.baixas*'),
    ],
    [
        'route'    => 'financeiro.responsaveis.index',
        'label'    => 'Responsáveis Financeiros',
        'process'  => ProcessosFinanceiro::MENU_RESPONSAVEIS,
        'icon'     => 'user-group',
        'active'   => request()->routeIs('financeiro.responsaveis*'),
    ],
    [
        'route'    => 'financeiro.contratos.index',
        'label'    => 'Contratos por Matrícula',
        'process'  => ProcessosFinanceiro::MENU_CONTRATOS,
        'icon'     => 'document-duplicate',
        'active'   => request()->routeIs('financeiro.contratos*'),
    ],
    [
        'route'    => 'financeiro.planos.index',
        'label'    => 'Planos de Cobrança',
        'process'  => ProcessosFinanceiro::MENU_PLANOS,
        'icon'     => 'tag',
        'active'   => request()->routeIs('financeiro.planos*'),
    ],
    [
        'route'    => 'financeiro.renegociacao.index',
        'label'    => 'Acordos e Renegociação',
        'process'  => ProcessosFinanceiro::MENU_RENEGOCIACAO,
        'icon'     => 'handshake',
        'active'   => request()->routeIs('financeiro.renegociacao*'),
    ],
    [
        'route'    => 'financeiro.contas-pagar.index',
        'label'    => 'Contas a Pagar',
        'process'  => ProcessosFinanceiro::MENU_CONTAS_PAGAR,
        'icon'     => 'credit-card',
        'active'   => request()->routeIs('financeiro.contas-pagar*'),
    ],
    [
        'route'    => 'financeiro.relatorios.index',
        'label'    => 'Central de Relatórios',
        'process'  => ProcessosFinanceiro::MENU_RELATORIOS,
        'icon'     => 'chart-pie',
        'active'   => request()->routeIs('financeiro.relatorios*'),
    ],
    [
        'route'    => 'financeiro.configuracoes.gateway',
        'label'    => 'Configurações',
        'process'  => ProcessosFinanceiro::MENU_GATEWAY_CONFIG,
        'icon'     => 'cog',
        'active'   => request()->routeIs('financeiro.configuracoes*'),
    ],
];
```

---

## 2. Especificação Técnica do Módulo de Carnê de Pagamento

### 2.1 Rotas Web
Em `routes/web.php`:
```php
// Emissão e Impressão de Carnê (individual, por contrato ou lote selecionado)
Route::match(['get', 'post'], 'titulos/imprimir-carne', 'TituloController@imprimirCarne')->name('titulos.imprimir-carne');
Route::get('contratos/{id}/imprimir-carne', 'ContratoController@imprimirCarne')->name('contratos.imprimir-carne');
```

### 2.2 Estrutura do Carnê Escolar (`titulos/carne.blade.php`)
- **Grid de Impressão:** 3 lâminas por página A4 na orientação Retrato (`portrait`).
- **Canhoto do Estabelecimento (Esquerda):**
  - Identificação da Escola, CNPJ e Telefone.
  - Aluno, Turma, Matrícula.
  - Parcela (ex: 03/12), Vencimento, Valor Nominal, Desconto, Valor Pago e Data do Recebimento.
  - Linha pontilhada de corte vertical com ícone de tesoura (`✂`).
- **Ficha de Pagamento do Responsável (Direita):**
  - Dados bancários / Asaas ou instruções de caixa.
  - Linha digitável e código de barras CSS/SVG (quando gerado via gateway).
  - QR Code Pix gerado dinamicamente (via API de QR Code ou Asaas payload).
  - Campo de autenticação mecânica ou assinatura do recebedor.
- **CSS `@media print`:**
  ```css
  @media print {
      @page {
          size: A4 portrait;
          margin: 8mm 10mm;
      }
      body {
          background: #fff !important;
          margin: 0 !important;
          padding: 0 !important;
      }
      .no-print {
          display: none !important;
      }
      .carne-page {
          page-break-after: always;
          display: flex;
          flex-direction: column;
          gap: 12px;
      }
      .carne-lamina {
          border: 1px dashed #475569;
          page-break-inside: avoid;
      }
  }
  ```

---

## 3. Seleção em Lote e Operações em Massa

### 3.1 Frontend da Tabela (`titulos/index.blade.php`)
- Checkbox `selectAll` no cabeçalho `<th>` da tabela.
- Checkbox individual `name="titulos_ids[]"` em cada linha `<tr>`.
- Barra flutuante inferior fixa (`position: sticky; bottom: 15px;`) que aparece via JavaScript quando `count(checked) > 0`:
  - Contador: *"X parcelas selecionadas"*.
  - Botão 1: **"Emitir no Asaas"** (POST para `titulos.emitir-lote-asaas`).
  - Botão 2: **"Sincronizar Asaas"** (POST para `titulos.sincronizar-lote-asaas`).
  - Botão 3: **"Imprimir Carnê"** (POST para `titulos.imprimir-carne` abrindo em nova aba com `target="_blank"`).

### 3.2 Backend Controller
Em `TituloController.php`:
```php
public function imprimirCarne(Request $request)
{
    $ids = $request->input('titulos_ids', []);
    if (empty($ids) && $request->filled('titulo_id')) {
        $ids = [$request->input('titulo_id')];
    }

    $titulos = Titulo::with(['responsavel', 'cobrancaGateway'])
        ->whereIn('id', (array)$ids)
        ->orderBy('aluno_id')
        ->orderBy('data_vencimento')
        ->get();

    return view('financeiro::titulos.carne', compact('titulos'));
}
```

---

## 4. Segurança, Permissões e Integridade Contábil

1. **Permissões de Acesso por Processo:** O middleware `CompartilharMenuMiddleware` e as rotas continuam validando as permissões do usuário logado contra a tabela `pmieducar.menu_processo`.
2. **Lock Pessimista em Lotes:** Ações em lote que alteram estado utilizam `Titulo::lockForUpdate()` dentro de `DB::transaction()` para garantir integridade sob concorrência.
3. **Audit Trail:** Cada ação de emissão em lote registra log com `usuario_id`, IP e contagem de títulos processados.
