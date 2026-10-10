# TASKS - Roadmap de Implementação Passo a Passo (Módulo Financeiro i-Educar)

> **Documento de Controle e Acompanhamento de Execução**  
> **Status Geral do Projeto:** 🟡 Em Planejamento Aprovado / Pronto para Fase 1  
> **Referência:** [PRD.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/PRD.md) \| [PLAN-modulo-financeiro.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/docs/PLAN-modulo-financeiro.md)  
> **Convenção de Status:** `[ ]` Pendente | `[/]` Em Andamento | `[x]` Concluído | `[-]` Bloqueado

---

## 🗺️ Mapa de Fases e Progresso

| Fase | Descrição | Tarefas | Status |
| :--- | :--- | :---: | :---: |
| **Fase 1** | Setup do Pacote Composer e ServiceProvider | 5 | `[x]` |
| **Fase 2** | Schema PostgreSQL e Migrations de Estrutura | 6 | `[x]` |
| **Fase 3** | Integração com Menus e Permissões do i-Educar | 4 | `[x]` |
| **Fase 4** | Models Eloquent e Cadastros Base | 6 | `[x]` |
| **Fase 5** | Motor de Contratos e Geração de Parcelas | 6 | `[x]` |
| **Fase 6** | Baixa Manual, Pagamentos e Descontos | 5 | `[x]` |
| **Fase 7** | Gateway de Cobrança Asaas (Boleto/Pix) e Webhooks | 7 | `[]` |
| **Fase 8** | Régua de Cobrança e Gestão de Inadimplência | 5 | `[ ]` |
| **Fase 9** | Renegociação de Dívidas e Acordos | 4 | `[ ]` |
| **Fase 10** | Contas a Pagar, Plano de Contas e Fluxo de Caixa | 5 | `[ ]` |
| **Fase 11** | Relatórios Gerenciais, DRE e Informe IRPF / Lei 12.007 | 5 | `[ ]` |
| **Fase 12** | Disparo de Links de Cobrança e Comunicação (E-mail/WhatsApp) | 4 | `[ ]` |
| **Fase X** | Verificação, Auditoria de Segurança e Testes | 5 | `[ ]` |

---

## Fase 1: Setup do Pacote Composer e ServiceProvider

- [x] **TASK-1.1: Inicialização do `composer.json` e Auto-discovery do Laravel**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** Nenhuma
  - **INPUT:** Estrutura de namespace `iEducar\Financeiro\` e dependências mínimas.
  - **OUTPUT:** Arquivo `composer.json` configurado com PSR-4 e registro do `FinanceiroServiceProvider` no nó `extra.laravel.providers`.
  - **VERIFY:** `composer.json` validado com sucesso.

- [x] **TASK-1.2: Criação do `FinanceiroServiceProvider.php`**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-1.1
  - **INPUT:** Estrutura base de carregamento de migrations, rotas, views e configurações.
  - **OUTPUT:** Classe `src/FinanceiroServiceProvider.php` implementando `boot()`, `register()` e View Composer do i-Educar.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-1.3: Arquivo de Configuração `config/financeiro.php`**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-1.2
  - **INPUT:** Parâmetros padrão de juros (1% a.m.), multa (2%), dias de tolerância, credenciais Asaas e prefixo de rotas.
  - **OUTPUT:** `config/financeiro.php` completo com arrays documentados.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-1.4: Configuração de Rotas (`routes/web.php` e `routes/api.php`)**
  - **Agente:** `backend-specialist` | **Skill:** `api-patterns`
  - **Dependências:** TASK-1.2
  - **INPUT:** Definição de grupos de rota administrativa (`/financeiro/...`) e rotas de webhooks (`/financeiro/webhooks/...`).
  - **OUTPUT:** `routes/web.php` e `routes/api.php` criados com middlewares padrão (`web`, `auth` e `api`).
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-1.5: Estrutura Base de Views e Assets (`resources/views`)**
  - **Agente:** `frontend-specialist` | **Skill:** `frontend-design`
  - **Dependências:** TASK-1.2
  - **INPUT:** Layouts do i-Educar 2.10 (`layout.default`, `table-default`, cores `#ccdce6` e `#47728f`).
  - **OUTPUT:** `resources/views/layouts/standalone.blade.php`, `resources/views/layouts/app.blade.php`, `resources/views/partials/alerts.blade.php` e `resources/views/dashboard/index.blade.php`.
  - **VERIFY:** Views replicando fielmente o visual, classes CSS e componentes oficiais do i-Educar.

---

## Fase 2: Schema PostgreSQL e Migrations de Estrutura

- [x] **TASK-2.1: Migration de Criação do Schema PostgreSQL `financeiro`**
  - **Agente:** `backend-specialist` | **Skill:** `database-design`
  - **Dependências:** TASK-1.2
  - **INPUT:** `DB::statement('CREATE SCHEMA IF NOT EXISTS financeiro;');`
  - **OUTPUT:** Migration `2026_01_01_000001_create_financeiro_schema.php`.
  - **VERIFY:** Migration validada sem erros de sintaxe.

- [x] **TASK-2.2: Migration de Cadastros Base e Plano de Contas**
  - **Agente:** `backend-specialist` | **Skill:** `database-design`
  - **Dependências:** TASK-2.1
  - **INPUT:** DDL das tabelas `financeiro.contas_bancarias`, `financeiro.plano_contas` e `financeiro.centros_custo`.
  - **OUTPUT:** Migration `2026_01_01_000002_create_plano_contas_e_bancos.php`.
  - **VERIFY:** Migration validada sem erros de sintaxe.

- [x] **TASK-2.3: Migration de Responsáveis Financeiros e Rateio (N:N Aluno)**
  - **Agente:** `backend-specialist` | **Skill:** `database-design`
  - **Dependências:** TASK-2.1
  - **INPUT:** Tabelas `financeiro.responsaveis_financeiros` e `financeiro.aluno_responsavel`.
  - **OUTPUT:** Migration `2026_01_01_000003_create_responsaveis_financeiros.php`.
  - **VERIFY:** Migration validada sem erros de sintaxe.

- [x] **TASK-2.4: Migration de Planos de Cobrança e Itens**
  - **Agente:** `backend-specialist` | **Skill:** `database-design`
  - **Dependências:** TASK-2.1
  - **INPUT:** Tabelas `financeiro.planos` e `financeiro.plano_itens`.
  - **OUTPUT:** Migration `2026_01_01_000004_create_planos_pagamento.php`.
  - **VERIFY:** Migration validada sem erros de sintaxe.

- [x] **TASK-2.5: Migration de Contratos e Títulos (Parcelas)**
  - **Agente:** `backend-specialist` | **Skill:** `database-design`
  - **Dependências:** TASK-2.3, TASK-2.4
  - **INPUT:** Tabelas `financeiro.contratos` e `financeiro.titulos`.
  - **OUTPUT:** Migration `2026_01_01_000005_create_contratos_e_titulos.php`.
  - **VERIFY:** Migration validada sem erros de sintaxe.

- [x] **TASK-2.6: Migration de Pagamentos, Descontos, Cobranças de Gateway e Idempotência**
  - **Agente:** `backend-specialist` | **Skill:** `database-design`
  - **Dependências:** TASK-2.5
  - **INPUT:** Tabelas `financeiro.pagamentos`, `financeiro.descontos_convenios`, `financeiro.titulo_descontos`, `financeiro.cobrancas_gateway`, `financeiro.webhook_events`, `financeiro.lancamentos` e `financeiro.renegociacoes`.
  - **OUTPUT:** Migration `2026_01_01_000006_create_transacoes_e_gateways.php`.
  - **VERIFY:** Migration validada sem erros de sintaxe.

---

## Fase 3: Integração com Menus e Permissões do i-Educar

- [x] **TASK-3.1: Mapeamento de Processos do i-Educar para o Módulo Financeiro**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-2.1
  - **INPUT:** Faixa de IDs de processos livres no core do i-Educar (faixa `999950+`).
  - **OUTPUT:** Classe auxiliar `iEducar\Financeiro\Constants\ProcessosFinanceiro.php`.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-3.2: Migration de Injeção de Menus na Tabela `menus`**
  - **Agente:** `backend-specialist` | **Skill:** `database-design`
  - **Dependências:** TASK-3.1
  - **INPUT:** Lógica de `Menu::query()->updateOrCreate()` inspirada no `i-educar-reports-package`.
  - **OUTPUT:** Migration `2026_01_01_000010_create_financeiro_menus.php`.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-3.3: Injeção de Permissões em `menu_tipo_usuario`**
  - **Agente:** `backend-specialist` | **Skill:** `database-design`
  - **Dependências:** TASK-3.2
  - **INPUT:** Atribuição automática de visibilidade para perfis de Administrador, Direção e Tesoureiro.
  - **OUTPUT:** Rotina dentro da migration associando os menus aos tipos de usuário existentes.
  - **VERIFY:** Rotina resiliente contra ausência de tabelas em ambientes isolados.

- [x] **TASK-3.4: View Composer para Destaque de Menus Ativos**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-3.2
  - **INPUT:** Registro no `FinanceiroServiceProvider` do composer para `layout.topmenu` e `layout.menu`.
  - **OUTPUT:** Código no Service Provider resolvendo `mainmenu`, `currentMenu` e `ancestors` na barra do i-Educar.
  - **VERIFY:** O menu "Financeiro" permanece visualmente ativo ao navegar pelas telas do módulo.

---

## Fase 4: Models Eloquent e Cadastros Base

- [x] **TASK-4.1: Model `ResponsavelFinanceiro` e Validação de CPF/CNPJ**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-2.3
  - **INPUT:** Relacionamentos com `pmieducar.aluno`, sanitização de documento e escopos de busca.
  - **OUTPUT:** `src/Models/ResponsavelFinanceiro.php` com métodos de sanitização e algoritmo de validação de CPF/CNPJ.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-4.2: Model `AlunoResponsavel` com Regra de Rateio**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-2.3
  - **INPUT:** Tabela intermediária de vínculo financeiro e validação de soma de rateio (máximo 100%).
  - **OUTPUT:** `src/Models/AlunoResponsavel.php` com método `validarSomaRateio()`.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-4.3: Models `PlanoPagamento` e `PlanoItem`**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-2.4
  - **INPUT:** Definição de planos anuais e cálculo de distribuição de parcelas.
  - **OUTPUT:** `src/Models/PlanoPagamento.php` e `src/Models/PlanoItem.php`.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-4.4: Controllers e Telas de Cadastro de Responsáveis Financeiros**
  - **Agente:** `backend-specialist` & `frontend-specialist` | **Skill:** `frontend-design`
  - **Dependências:** TASK-4.1, TASK-1.5
  - **INPUT:** CRUD completo de responsáveis com busca vinculada aos alunos do i-Educar.
  - **OUTPUT:** `ResponsavelFinanceiroController.php` e views `responsaveis/index.blade.php`, `create.blade.php`, `edit.blade.php` e `show.blade.php`.
  - **VERIFY:** Views replicando fielmente as classes e formulários do i-Educar (`tablecadastro`, `formdktd`, `botaosalvar`).

- [x] **TASK-4.5: Controllers e Telas de Configuração de Planos de Pagamento**
  - **Agente:** `backend-specialist` & `frontend-specialist` | **Skill:** `frontend-design`
  - **Dependências:** TASK-4.3
  - **INPUT:** CRUD de planos por ano letivo, escola, curso e série.
  - **OUTPUT:** `PlanoPagamentoController.php` e views `planos/index.blade.php`, `create.blade.php` e `show.blade.php`.
  - **VERIFY:** Divisão exata de parcelas com ajuste de centavos na primeira parcela.

- [x] **TASK-4.6: Seeder de Plano de Contas Padrão Escolar**
  - **Agente:** `backend-specialist` | **Skill:** `database-design`
  - **Dependências:** TASK-2.2
  - **INPUT:** Estrutura contábil padrão para escolas privadas (Receitas e Despesas).
  - **OUTPUT:** `database/seeders/PlanoContasPadraoSeeder.php`.
  - **VERIFY:** Seeder cria categorias hierárquicas e centros de custo padrão com sintaxe validada.

---

## Fase 5: Motor de Contratos e Geração de Parcelas

- [x] **TASK-5.1: Model `Contrato` e Ciclo de Vida do Contrato Escolar**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-2.5
  - **INPUT:** Estados de contrato (`rascunho`, `ativo`, `suspenso`, `cancelado`, `transferido`, `quitado`).
  - **OUTPUT:** `src/Models/Contrato.php` com métodos de transição de estado e geração de número único.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-5.2: Model `Titulo` (Parcela Financeira)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-2.5
  - **INPUT:** Relacionamentos com Contrato, Aluno, Responsável, Escola e Pagamentos.
  - **OUTPUT:** `src/Models/Titulo.php` com escopos de busca e cálculo automático de juros e multas.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-5.3: `ContratoService` - Motor de Geração Automática de Parcelas**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-5.1, TASK-5.2
  - **INPUT:** Matrícula, plano selecionado, vencimento e rateios de responsáveis.
  - **OUTPUT:** `src/Services/ContratoService.php` gerando títulos atômicos com suporte a divisão de pais.
  - **VERIFY:** Transações atômicas com tratamento de rateio proporcional.

- [x] **TASK-5.4: Listener de Matrícula do i-Educar (`CriarContratoAposMatricula`)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-5.3
  - **INPUT:** Eventos de matrícula do core do i-Educar.
  - **OUTPUT:** `src/Listeners/CriarContratoAposMatricula.php`.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-5.5: Listener de Cancelamento de Matrícula (`CancelarTitulosMatriculaCancelada`)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-5.3
  - **INPUT:** Evento de cancelamento de matrícula do i-Educar.
  - **OUTPUT:** `src/Listeners/CancelarTitulosMatriculaCancelada.php` aplicando a política aprovada (cancela apenas parcelas futuras não vencidas).
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-5.6: Interface Administrativa de Gestão de Contratos e Carnê de Parcelas**
  - **Agente:** `backend-specialist` & `frontend-specialist` | **Skill:** `frontend-design`
  - **Dependências:** TASK-5.3
  - **INPUT:** Visualização de contrato por aluno/matrícula com grade de parcelas e status.
  - **OUTPUT:** `ContratoController.php` e views `contratos/index.blade.php`, `create.blade.php` e `show.blade.php`.
  - **VERIFY:** Views replicando fielmente o layout, botões e tabelas oficiais do i-Educar.

---

## Fase 6: Baixa Manual, Pagamentos e Descontos

- [x] **TASK-6.1: `BaixaTituloService` - Baixa Integral e Baixa Parcial com Auditoria**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-5.2, TASK-2.6
  - **INPUT:** Título, valor pago, forma de pagamento, descontos concedidos, data de pagamento e operador.
  - **OUTPUT:** `src/Services/BaixaTituloService.php` registrando em `financeiro.pagamentos` e atualizando `financeiro.titulos`.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-6.2: Fluxo de Estorno de Pagamento com Rastreabilidade**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-6.1
  - **INPUT:** ID do pagamento, motivo do estorno e autorização de usuário.
  - **OUTPUT:** Método `estornarBaixa()` revertendo saldo e status do título sem apagar histórico contábil.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-6.3: Motor de Descontos, Bolsas e Convênios**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-5.2
  - **INPUT:** Regras de desconto por pontualidade, desconto para irmãos e bolsas de estudo.
  - **OUTPUT:** `src/Services/DescontoService.php` calculando abatimentos aplicáveis.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

- [x] **TASK-6.4: Telas de Baixa de Balcão (Caixa Escolar / Secretaria)**
  - **Agente:** `backend-specialist` & `frontend-specialist` | **Skill:** `frontend-design`
  - **Dependências:** TASK-6.1
  - **INPUT:** Modal de baixa rápida com cálculo automático de juros e multas ao digitar a data de recebimento.
  - **OUTPUT:** `BaixaManualController.php` e view `resources/views/baixas/create.blade.php`.
  - **VERIFY:** Telas seguindo o padrão i-Educar (`tablecadastro`, `formdktd`, `botaosalvar`).

- [x] **TASK-6.5: Geração de Recibo de Pagamento em PDF/HTML**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-6.4
  - **INPUT:** Dados do pagamento liquidado, responsável, aluno, valor por extenso e autenticação digital.
  - **OUTPUT:** `TituloController@imprimirRecibo` e view `resources/views/titulos/recibo.blade.php` com autenticação digital SHA-256 e suporte nativo a impressão/PDF.
  - **VERIFY:** Sintaxe validada via PHP Lint sem erros.

---

## Fase 7: Gateway de Cobrança Asaas (Boleto/Pix) e Webhooks

- [x] **TASK-7.1: Arquitetura de Gateway e Interface Unificada (`GatewayInterface`)**
  - **Agente:** `backend-specialist` | **Skill:** `api-patterns`
  - **Dependências:** TASK-1.3
  - **INPUT:** Contrato comum para geração de cobrança, cancelamento, consulta e processamento de webhooks.
  - **OUTPUT:** `src/Gateways/Contracts/GatewayInterface.php` e `src/Gateways/GatewayManager.php`.
  - **VERIFY:** Suporta resolução dinâmica de drivers através do manager.

- [x] **TASK-7.2: Implementação Completa do Driver Asaas (`AsaasGateway.php`)**
  - **Agente:** `backend-specialist` | **Skill:** `api-patterns`
  - **Dependências:** TASK-7.1
  - **INPUT:** Integração com a API v3 do Asaas (criação de cliente, emissão de cobrança híbrida Boleto+Pix e Cartão).
  - **OUTPUT:** `src/Gateways/Drivers/AsaasGateway.php` com chamadas resilientes via `Http::asJson()` com retry e logs estruturados.
  - **VERIFY:** Criação de cobrança retornando link do boleto em PDF, código de barras, linha digitável e Pix copia-e-cola.

- [x] **TASK-7.3: Controlador de Webhook com Tabela de Idempotência (`AsaasWebhookController`)**
  - **Agente:** `backend-specialist` | **Skill:** `security-auditor`
  - **Dependências:** TASK-2.6, TASK-7.2
  - **INPUT:** Requisições POST enviadas pelo Asaas notificando eventos (`PAYMENT_RECEIVED`, `PAYMENT_CONFIRMED`).
  - **OUTPUT:** `src/Http/Webhooks/AsaasWebhookController.php` validando token de autenticação e salvando em `financeiro.webhook_events`.
  - **VERIFY:** Eventos duplicados com mesmo `event_id` são identificados imediatamente e retornam `HTTP 200` sem baixa dupla.

- [x] **TASK-7.4: Job Assíncrono de Processamento de Webhook (`ProcessarWebhookJob`)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-7.3, TASK-6.1
  - **INPUT:** Eventos enfileirados pelo webhook.
  - **OUTPUT:** `src/Jobs/ProcessarWebhookJob.php` invocando `BaixaTituloService` para liquidação automática.
  - **VERIFY:** Título é marcado como `pago`, valor líquido e taxa do Asaas registrados e recibo disponibilizado.

- [x] **TASK-7.5: Tela de Configuração e Teste de Conexão com o Asaas**
  - **Agente:** `backend-specialist` & `frontend-specialist` | **Skill:** `frontend-design`
  - **Dependências:** TASK-7.2
  - **INPUT:** Interface para inserir API Key do Asaas (produção e sandbox) e token do webhook.
  - **OUTPUT:** `GatewayConfigController.php` e views com botão "Testar Conexão".
  - **VERIFY:** Botão dispara requisição de verificação contra `/api/v3/finance/balance` do Asaas e exibe status ao vivo.

- [x] **TASK-7.6: Emissão em Lote de Cobranças no Asaas (`GerarCobrancasLoteJob`)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-7.2, TASK-5.3
  - **INPUT:** Seleção de competência (ex.: "Mensalidades de Abril/2026") para transmissão em massa.
  - **OUTPUT:** `src/Jobs/GerarCobrancasGatewayLoteJob.php` processando lotes assíncronos.
  - **VERIFY:** 100 títulos processados com geração de boletos/Pix e preenchimento de `financeiro.cobrancas_gateway`.

- [x] **TASK-7.7: Sincronização e Consulta de Status de Cobranças**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-7.2
  - **INPUT:** Botão na secretaria "Consultar no Gateway" para checar status atualizado no Asaas em tempo real.
  - **OUTPUT:** Método `sincronizarStatus()` atualizando status local se houve pagamento não capturado por webhook.
  - **VERIFY:** Atualização instantânea com log de conferência.


---

## Fase 8: Régua de Cobrança e Gestão de Inadimplência

- [x] **TASK-8.1: Configuração da Régua de Cobrança por Unidade Escolar**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-2.1
  - **INPUT:** Definição de gatilhos configuráveis: D-7, D-3, D0, D+3, D+10.
  - **OUTPUT:** Tabela `financeiro.regua_cobranca_regras` e Model `ReguaCobrancaRegra.php`.
  - **VERIFY:** Regras customizáveis por escola (habilitar/desabilitar etapas e editar modelos de mensagem).

- [x] **TASK-8.2: Comando Artisan Diário `financeiro:processar-regua`**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-8.1, TASK-5.2
  - **INPUT:** Execução agendada diária varrendo títulos abertos e vencidos.
  - **OUTPUT:** `src/Commands/ProcessarReguaCobrancaCommand.php` enfileirando notificações.
  - **VERIFY:** Dispara jobs de notificação sem repetir envios no mesmo dia para o mesmo título.

- [x] **TASK-8.3: Job de Envio de Notificações com Links Diretos de Pagamento**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-8.2, TASK-7.2
  - **INPUT:** Disparo de e-mail estruturado com dados do título, link do boleto do Asaas e código Pix copia-e-cola.
  - **OUTPUT:** `src/Jobs/EnviarNotificacaoCobrancaJob.php` e Mailable `CobrancaMensalidadeMail.php`.
  - **VERIFY:** E-mail formatado entregue com botões de ação imediata.

- [x] **TASK-8.4: Painel Gerencial de Inadimplência por Faixa de Atraso**
  - **Agente:** `backend-specialist` & `frontend-specialist` | **Skill:** `frontend-design`
  - **Dependências:** TASK-5.2
  - **INPUT:** Agrupamento por faixas de vencimento: 1-30 dias, 31-60 dias, 61-90 dias, >90 dias.
  - **OUTPUT:** Controller e Dashboard com cards de valor total em atraso, taxa de inadimplência (%) e tabela de devedores.
  - **VERIFY:** Filtros por escola, ano letivo, curso, série e turma com exportação rápida.

- [x] **TASK-8.5: Alerta da Secretaria na Rematrícula / Emissão de Documentos**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-8.4
  - **INPUT:** Alerta visual na secretaria quando o operador consulta ficha ou matrícula de aluno inadimplente.
  - **OUTPUT:** Helper/Service `InadimplenciaCheckService.php` e exibição de aviso discreto e orientativo.
  - **VERIFY:** Exibição clara orientando atendimento a direcionar ao financeiro.


---

## Fase 9: Renegociação de Dívidas e Acordos

- [x] **TASK-9.1: Modelagem e Service de Renegociação (`RenegociacaoService`)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-2.6, TASK-5.2
  - **INPUT:** Seleção de múltiplos títulos vencidos, aplicação de desconto condicional de juros/multa e novo plano de parcelamento.
  - **OUTPUT:** `src/Services/RenegociacaoService.php` consolidando débitos em termo de acordo.
  - **VERIFY:** Títulos originais assumem status `renegociado` e novos títulos são gerados vinculados ao termo de acordo.

- [x] **TASK-9.2: Geração do Termo de Confissão de Dívida e Acordo Financeiro**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-9.1
  - **INPUT:** Minuta jurídica contendo partes envolvidas, dívida consolidada, novas parcelas e penalidades por atraso.
  - **OUTPUT:** Relatório/PDF do Termo de Acordo pronto para impressão e assinatura.
  - **VERIFY:** Documento formatado com campos para assinaturas das partes e testemunhas.

- [x] **TASK-9.3: Interface de Negociação e Simulador de Parcelamento**
  - **Agente:** `backend-specialist` & `frontend-specialist` | **Skill:** `frontend-design`
  - **Dependências:** TASK-9.1
  - **INPUT:** Tela interativa onde o atendente seleciona parcelas vencidas, simula entrada + X parcelas e confere valores.
  - **OUTPUT:** `RenegociacaoController.php` e views com simulador em tempo real.
  - **VERIFY:** Recálculo instantâneo de totais antes da confirmação do termo.

- [x] **TASK-9.4: Tratamento de Quebra de Acordo**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-9.1
  - **INPUT:** Não pagamento das parcelas de acordo dentro do prazo estipulado.
  - **OUTPUT:** Rotina de cancelamento do acordo ou reativação dos títulos originais com perda dos benefícios concedidos.
  - **VERIFY:** Segurança contábil impedindo perda de valor original da dívida.


---

## Fase 10: Contas a Pagar, Plano de Contas e Fluxo de Caixa

- [x] **TASK-10.1: Modelagem e CRUD de Contas a Pagar (`financeiro.lancamentos`)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-2.2
  - **INPUT:** Registro de despesas da escola: fornecedor, vencimento, centro de custo, conta contábil, valor e comprovante anexo.
  - **OUTPUT:** `src/Models/Lancamento.php`, `ContaPagarController.php` e views Blade.
  - **VERIFY:** Listagem filtrável por período, status (a pagar, pago, vencido) e centro de custo.

- [x] **TASK-10.2: Liquidação de Contas a Pagar e Controle de Caixas/Bancos**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-10.1
  - **INPUT:** Baixa da despesa informando conta de saída e data de pagamento.
  - **OUTPUT:** Atualização de saldo da conta bancária e movimentação de extrato.
  - **VERIFY:** Débito exato no saldo da conta selecionada.

- [x] **TASK-10.3: Transferência entre Caixas e Contas Bancárias**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-10.2
  - **INPUT:** Movimentação entre conta corrente bancária e caixa físico da secretaria (sangria/suprimento).
  - **OUTPUT:** Lançamento de saída e entrada na mesma transação.
  - **VERIFY:** Saldo global mantido e extrato individualizado por conta.

- [x] **TASK-10.4: Relatório de Fluxo de Caixa Realizado vs Projetado**
  - **Agente:** `backend-specialist` & `frontend-specialist` | **Skill:** `frontend-design`
  - **Dependências:** TASK-10.1, TASK-5.2
  - **INPUT:** Consolidação de entradas e saídas por dia e mês.
  - **OUTPUT:** `FluxoCaixaReport.php` e tela com gráficos comparativos e tabela analítica.
  - **VERIFY:** Saldos diários acumulados calculados com exatidão.

- [x] **TASK-10.5: Fechamento Mensal de Caixa e Trava de Período**
  - **Agente:** `backend-specialist` | **Skill:** `security-auditor`
  - **Dependências:** TASK-10.2
  - **INPUT:** Bloqueio contábil para meses já encerrados e auditados.
  - **OUTPUT:** Tabela `financeiro.fechamentos_periodo` impedindo alterações retroativas em períodos bloqueados.
  - **VERIFY:** Bloqueio com mensagem instrutiva ao tentar lançar ou alterar registros em mês encerrado.

---

## Fase 11: Relatórios Gerenciais, DRE e Informe IRPF / Lei 12.007

- [x] **TASK-11.1: Relatório de Inadimplência Detalhado (PDF e Excel)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-8.4
  - **INPUT:** Listagem analítica por turma com dados do aluno, responsável, contatos e títulos vencidos.
  - **OUTPUT:** `src/Reports/InadimplenciaReport.php` com exportação em PDF e Excel.
  - **VERIFY:** Totalizadores consistentes por turma e totais gerais da escola.

- [x] **TASK-11.2: DRE Simplificado Escolar (Regime de Competência e Caixa)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-10.1, TASK-5.2
  - **INPUT:** Agrupamento por plano de contas (Receitas Brutas - Deduções/Bolsas = Receita Líquida - Despesas = Resultado).
  - **OUTPUT:** `src/Reports/DREReport.php` com visão anual mês a mês.
  - **VERIFY:** Apuração percentual e cálculo de margem líquida.

- [x] **TASK-11.3: Emissão de Informe de Pagamentos para Imposto de Renda (IRPF)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-6.1
  - **INPUT:** Seleção do responsável e ano-calendário (ex: 2025).
  - **OUTPUT:** `src/Reports/DemonstrativoIRPFReport.php` gerando PDF oficial para declaração de IRPF.
  - **VERIFY:** Somente pagamentos efetivamente compensados no ano-calendário são computados.

- [x] **TASK-11.4: Declaração de Quitação Anual de Débitos (Lei 12.007/2009)**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-6.1
  - **INPUT:** Verificação de adimplemento integral das parcelas do ano letivo de determinado aluno.
  - **OUTPUT:** Emissão de certidão de quitação anual individual e rotina de emissão em lote.
  - **VERIFY:** Emissão permitida apenas para alunos sem nenhuma pendência financeira no ano.

- [x] **TASK-11.5: Relatório de Previsão de Receita por Série e Curso**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-5.3
  - **INPUT:** Contratos ativos multiplicados pelo valor contratado líquido.
  - **OUTPUT:** Painel gerencial e relatório de receita prevista para o ano letivo.
  - **VERIFY:** Cruzamento com taxa de ocupação de turmas e vagas remanescentes.

---

## Fase 12: Disparo de Links de Cobrança e Comunicação (E-mail/WhatsApp)

- [x] **TASK-12.1: Envio Rápido de 2ª Via de Boleto/Pix via Secretaria**
  - **Agente:** `backend-specialist` & `frontend-specialist` | **Skill:** `frontend-design`
  - **Dependências:** TASK-7.2
  - **INPUT:** Botão na listagem de títulos da secretaria: "Copiar Pix", "Copiar Linha Digitável" e "Enviar por WhatsApp".
  - **OUTPUT:** Modal de compartilhamento rápido com link direto do Asaas e texto pronto para envio pelo WhatsApp Web da secretaria.
  - **VERIFY:** Copiar link e abrir mensagem pronta de WhatsApp com 1 clique.

- [x] **TASK-12.2: Disparo de Cobrança por E-mail com Template Customizado**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-8.3
  - **INPUT:** Disparo manual ou em lote de e-mails com identidade visual da escola e links de pagamento.
  - **OUTPUT:** Interface de reenvio de cobrança por e-mail com histórico de envios registrados em log.
  - **VERIFY:** Registro do envio na tabela de histórico de notificações.

- [x] **TASK-12.3: Painel de Histórico de Notificações Financeiras**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** TASK-12.2
  - **INPUT:** Auditoria de todas as mensagens e e-mails disparados pela régua e pela secretaria para os pais.
  - **OUTPUT:** Tela em `financeiro/notificacoes` listando data, destinatário, canal e status de entrega.
  - **VERIFY:** Atendente da secretaria visualiza se o responsável recebeu e abriu a cobrança antes de cobrar.

- [x] **TASK-12.4: Arquitetura de Webhook para Emissão de NFS-e (Módulos Terceiros)**
  - **Agente:** `backend-specialist` | **Skill:** `api-patterns`
  - **Dependências:** TASK-6.1
  - **INPUT:** Disparo de evento `TituloPago` com payload estruturado contendo dados fiscais do responsável (CPF/CNPJ, endereço) e descrição do serviço.
  - **OUTPUT:** `src/Events/TituloPago.php` e documentação de integração para emissores de NFS-e (Focus NFe, PlugNotas).
  - **VERIFY:** Payload completo pronto para consumo por serviços fiscais externos.

---

## Fase X: Verificação, Auditoria de Segurança e Testes

- [x] **TASK-X.1: Cobertura de Testes Unitários de Regras de Negócio Críticas**
  - **Agente:** `test-engineer` | **Skill:** `testing-patterns`
  - **Dependências:** Todas as fases anteriores
  - **INPUT:** Cenários de rateio percentual de pais, cálculo de juros/multa, idempotência de webhook e descontos.
  - **OUTPUT:** Suíte de testes em `tests/Unit/` com padrão AAA (Arrange, Act, Assert).
  - **VERIFY:** `vendor/bin/phpunit --testsuite Unit` com 100% de sucesso.

- [x] **TASK-X.2: Testes de Integração de Migrations e Foreign Keys com i-Educar**
  - **Agente:** `backend-specialist` | **Skill:** `testing-patterns`
  - **Dependências:** Fase 2 e Fase 3
  - **INPUT:** Ambiente de banco PostgreSQL do i-Educar.
  - **OUTPUT:** Teste de rollback e migrate (`php artisan migrate:fresh` ou execução isolada das migrations do pacote).
  - **VERIFY:** Sem violações de chave estrangeira com `pmieducar.aluno`, `pmieducar.matricula` e `pmieducar.escola`.

- [x] **TASK-X.3: Auditoria de Segurança OWASP e LGPD**
  - **Agente:** `security-auditor` | **Skill:** `vulnerability-scanner`
  - **Dependências:** Todas as fases anteriores
  - **INPUT:** Análise estática de código e checagem de autorização nos endpoints administrativos e webhooks.
  - **OUTPUT:** Execução do script `.agent/skills/vulnerability-scanner/scripts/security_scan.py .`.
  - **VERIFY:** Zero vulnerabilidades críticas (SQL Injection, vazamento de credenciais de gateway, acessos indevidos).

- [x] **TASK-X.4: Verificação de Padrões de Código e Clean Code**
  - **Agente:** `backend-specialist` | **Skill:** `clean-code`
  - **Dependências:** Todas as fases anteriores
  - **INPUT:** PHP CS Fixer / Pint e padrões PSR-12.
  - **OUTPUT:** Código formatado, documentado e tipado rigorosamente.
  - **VERIFY:** Zero alertas de estilo ou tipagem incorreta.

- [x] **TASK-X.5: Checklist Final e Homologação de Deploy**
  - **Agente:** `orchestrator` & `project-planner` | **Skill:** `deployment-procedures`
  - **Dependências:** TASK-X.1 a TASK-X.4
  - **INPUT:** Checklist de pré-produção (`python .agent/scripts/checklist.py .`).
  - **OUTPUT:** Relatório final de prontidão do pacote para publicação no Composer / Packagist / Satis interno.
  - **VERIFY:** Todas as caixas `[ ]` das fases concluídas e assinadas.

