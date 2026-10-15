# TALKS — Registro de Alinhamento e Acompanhamento do Projeto

**Módulo Financeiro i-Educar**  
**Arquivo de Acompanhamento Contínuo**  
**Início:** 13/10/2026  

---

## 📌 Histórico de Decisões e Entregas

### 📅 Sessão 1 — 13/10/2026: Diagnóstico e Correção da Integração Asaas
- **Problema:** Webhooks do Asaas retornando HTTP 405 (rota singular `/financeiro/webhook/asaas` ausente) e 503 (falta de token no `.env`).
- **Solução Implementada:**
  - Criação de suporte a rotas no singular e plural com endpoints de healthcheck (`GET/HEAD`).
  - Sincronização segura do `webhook_token` entre banco de dados local (`financeiro.gateway_configuracoes`) e API do Asaas.
  - Ampliação do `ProcessarWebhookJob` com fallback por `externalReference` e tratamento de cancelamentos/estornos.
  - Testes reais executados na VPS: Título #28 liquidado de R$ 906,82 -> R$ 0,00 com sucesso e idempotência confirmada.

### 📅 Sessão 2 — 13/10/2026: Alinhamento de Usabilidade, Navegação e Carnês
- **Entrada do Usuário:**
  1. *Submenus:* Aprovados, desde que alinhados aos menus já existentes no i-Educar.
  2. *Prioridade de Ações:* As 3 ações principais devem ser destacadas: Caixa presencial, Envio por WhatsApp e Novo Contrato/Matrícula.
  3. *Ações em Lote e Carnê:* Aprovada a seleção por checkbox na tabela de títulos + Emissão de Carnê de pagamento para impressão em papel.
  4. *Documentação solicitada:* `PRD.md`, `TRD.md`, `FLUXO.md` e `TALKS.md`.
- **Status:** Documentos criados e alinhamento concluído.

---

## 📋 Quadro de Tarefas da Implementação (Sprint Board)

### 🟢 Fase 1: Arquitetura de Navegação e Submenu Nativo
- [x] **1.1** Criar partial `resources/views/partials/subnav.blade.php` com as categorias oficiais do i-Educar (`Cadastros`, `Movimentações`, `Lançamentos`, `Relatórios`, `Documentos`, `Ferramentas`).
- [x] **1.2** Configurar `layouts/app.blade.php` com `$mainmenu`, `$currentMenu` e `$menuPaths` integrando diretamente com o `@include('layout.topmenu')` nativo do i-Educar.
- [x] **1.3** Garantir que a aba ativa receba o destaque visual (fundo suave `#c8dced` e texto `#1b4c6b` idêntico à imagem de referência).
- [x] **1.4** Reorganizar a tabela `menus` do banco de dados agrupando todos os itens sob as 5 categorias oficiais para renderização fiel do menu drop-down no i-Educar.

### 🟡 Fase 2: Tríade de Ações Rápidas (Caixa, WhatsApp, Contrato)
- [x] **2.1** Reforçar os botões das 3 ações prioritárias no cabeçalho de Títulos (`Baixa de Balcão (Caixa)`, `Novo Contrato` e `Imprimir Carnê Geral`).
- [x] **2.2** Na listagem de Títulos, incluir botões de ação com ícones e tooltips nas linhas da tabela (*"Baixa Presencial"*, *"Notificar WhatsApp"*, *"Recibo"*, *"Ver Detalhes"*).
- [x] **2.3** Botão de ação rápida adicionado no detalhe do contrato (`contratos.show`) para emissão do carnê do aluno.

### 🔵 Fase 3: Seleção em Lote e Emissão de Carnê Escolar
- [x] **3.1** Implementar seleção múltipla com checkboxes (`#check-todos-titulos` e `.check-titulo-item`) em `titulos/index.blade.php`.
- [x] **3.2** Criar barra flutuante inferior fixa (*Sticky Bottom*) com contador dinâmico, total em R$ e ações (*"Imprimir Carnê"*, *"Emitir no Asaas"*, *"Desmarcar"*).
- [x] **3.3** Criar a rota e os métodos `TituloController@imprimirCarne` e `TituloController@emitirLoteAsaas`.
- [x] **3.4** Criar a view `resources/views/titulos/carne.blade.php` em folha A4 com 3 lâminas por página, recibo do sacado, instruções bancárias, código de barras e QR Code Pix, com estilos `@media print`.
- [x] **3.5** Criar rota e botão de impressão de carnê completo na visualização do Contrato (`contratos.show`).

### 🟣 Fase 4: Deploy, Sincronização na VPS e Validação
- [x] **4.1** Executar checagem de sintaxe e estrutura de views.
- [x] **4.2** Sincronizar arquivos na VPS (`/var/www/financeiro-package` e `/var/www/ieducar/vendor/...`).
- [x] **4.3** Limpar caches do Laravel (`route:clear`, `view:clear`, `cache:clear`).
- [x] **4.4** Recarregar `php8.4-fpm` e testar a renderização visual das páginas e da impressão de carnê no navegador.
- [x] **4.5** Commit e push no repositório GitHub.

---

## 💬 Notas de Comunicação e Registro
- *13/10/2026 23:25:* Submenus alinhados 100% com o padrão oficial do i-Educar (mesmas classes `.ieducar-menu-container`, `.ieducar-menu`, `.ieducar-sub-menu` e visual fiel à imagem do usuário).
- *13/10/2026 23:30:* View de carnê pronta para impressão em folha A4 com 3 carnês por página e QR Code Pix dinâmico.
- *13/10/2026 23:32:* Seleção em lote com barra flutuante e emissão de carnê integrada à tabela de títulos e contratos.
- *13/10/2026 23:40:* Testes de renderização automatizados concluídos na VPS (181 KB de HTML nativo validado, rotas registradas e `php8.4-fpm` ativo).
- *13/10/2026 23:50:* Ajustada a paleta de cores do submenu para o padrão oficial nativo do i-Educar (fundo da barra em `#e9eff7`, links em `#47728f`, aba ativa/hover em `#cddce6` e dropdown em `#FFF` com `#f3f3f3`), além da remoção definitiva dos itens órfãos na raiz.

### 📅 Sessão 3 — 14/10/2026: Carnê via Asaas Installments (decisão do usuário)

- **Diretriz do Usuário:** "o carnê é para ser gerado no asaas" — os carnês **não devem** ser apenas HTML imprimível local; devem ser PDFs oficiais gerados pelo Asaas via `/v3/installments/{id}/paymentBook`.
- **Arquitetura Implementada:**
  1. **`POST /v3/installments`** — Cria parcelamento no Asaas com todos os títulos em aberto do contrato. O Asaas gera N cobranças internas e retorna um `installment_id`.
  2. **`GET /v3/installments/{id}/paymentBook`** — Retorna o PDF oficial do carnê. O sistema faz streaming do binário PDF diretamente para o browser.
  3. **Fallback inteligente** — Se o gateway não estiver configurado ou falhar, renderiza a view HTML de impressão manual sem perda de funcionalidade.
  4. **Idempotência** — Se o contrato já possui `installment_id` cadastrado, reutiliza sem criar novamente. A rota `/carne-asaas` força regeneração quando necessário.
- **Arquivos Alterados:**
  - `GatewayInterface.php` — Novos métodos `criarInstallment()` e `gerarCarneUrl()`
  - `AsaasGateway.php` — Implementação de `criarInstallment`, `gerarCarneUrl` e `baixarCarnePdf`
  - `CobrancaGateway.php` (model) — Campos `installment_id`, `carne_url`, `contrato_id`
  - `ContratoController.php` — `imprimirCarne` com integração Asaas completa + fallback + `carneAsaas` para regeneração
  - `TituloController.php` — `imprimirCarne` delega para `ContratoController` quando títulos são do mesmo contrato
  - `routes/web.php` — Nova rota `GET contratos/{id}/carne-asaas`
  - `migrations/000014` — Colunas `installment_id`, `carne_url`, `contrato_id` na tabela `cobrancas_gateway`
- **Commit:** `622836f` — Push em `main` realizado com sucesso.

---

### 🟠 Fase 5: Testes de Carnê Asaas em Produção (Pendente)

- [ ] **5.1** Executar `php artisan migrate --force` na VPS para aplicar migration `000014`.
- [ ] **5.2** Acessar `/financeiro/contratos/{id}/imprimir-carne` com um contrato real e validar que o PDF do Asaas é retornado.
- [ ] **5.3** Verificar no painel do Asaas Sandbox que o installment foi criado com as parcelas corretas.
- [ ] **5.4** Testar a rota de regeneração `/financeiro/contratos/{id}/carne-asaas` após alteração de valores.
- [ ] **5.5** Validar fallback HTML quando gateway estiver desabilitado ou sem API key.

---

### 📅 Sessão 4 — 15/10/2026: Auditoria Profunda, Correções Estruturais e Novo TASKS.md

- **Objetivo:** Superar a auditoria técnica de `PRD-CORRECOES-MODULO-FINANCEIRO.md`, estruturar um novo `TASKS.md` definitivo com rastreamento integral (Fases 0 a 6) e concluir a Fase 2 (Cobranças Asaas e Carnê) e T4.1.
- **Entregas Realizadas:**
  1. **Reescrita Integral do [TASKS.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/TASKS.md):** Substituição do roadmap antigo v1 pela matriz completa de correções, bugs P0/P1 e novos requisitos de conformidade com o i-Educar 2.10.
  2. **Conclusão da Fase 2 (100%):**
     - `T2.6`: Implementação real do comando `financeiro:sincronizar-asaas` (`SincronizarCobrancasAsaasCommand.php`) com rate limiting de 100ms, consulta à API do Asaas e conciliação automática com `BaixaTituloService`.
     - `T2.6`: Criação da rota `POST titulos/sincronizar-lote-asaas` e integração com a barra flutuante de seleção em lote na view `resources/views/titulos/index.blade.php`.
     - `T2.7`: Registro de todas as rotinas agendadas no `FinanceiroServiceProvider.php` via `callAfterResolving(Schedule::class)` (`processar-regua` 08:00, `sincronizar-asaas` de hora em hora, `atualizar-status` 00:10 e `reprocessar-webhooks` a cada 5 min).
  3. **Avanço em Fase 4 (T4.1):**
     - Criação do comando `financeiro:atualizar-status` (`AtualizarStatusTitulosCommand.php`) para transição de títulos vencidos e quitação de saldos residuais em rotina noturna, eliminando mutações em requisições GET no `DashboardController`.
  4. **Bateria de Testes:**
     - Implementação dos testes `test_t35_sincronizacao_cobrancas_comando_e_rota_lote` e `test_t36_comando_atualizar_status_titulos`.
     - Total: **36 testes passing, 257 asserções, 0 falhas** no PostgreSQL/SQLite via PHPUnit.
