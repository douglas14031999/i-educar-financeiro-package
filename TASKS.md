# TASKS — Plano de Ação e Controle de Execução (Módulo Financeiro i-Educar)

> **Documento Oficial de Acompanhamento do Projeto**  
> **Status Geral:** 🟢 100% Concluído (Fases 0 a 6 Homologadas e Aprovadas em Sandbox Asaas) | Production-Ready  
> **Referência Primária:** [PRD-CORRECOES-MODULO-FINANCEIRO.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/PRD-CORRECOES-MODULO-FINANCEIRO.md)  
> **Relatório de Homologação:** [docs/HOMOLOGACAO-EVIDENCIAS.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/docs/HOMOLOGACAO-EVIDENCIAS.md)  
> **Baterias de Testes:** `tests/Feature/PrdComplianceTest.php` e `tests/Feature/HomologacaoE2ETest.php` (84 testes, 599 asserções, 0 falhas)  
> **Convenção de Status:** `[ ]` Pendente | `[/]` Em Andamento | `[x]` Concluído | `[-]` Bloqueado / Opcional  

---

## 📊 Matriz de Fases e Progresso

| Fase | Foco Principal | Prioridade | Total Tarefas | Status |
| :--- | :--- | :---: | :---: | :---: |
| **Fase 0** | Baseline, Testbench, CI & Validação de Premissas | Pré-req | 6 | `[x]` Concluída (6/6) |
| **Fase 1** | Segurança e Integridade (RBAC, Criptografia, Webhooks, Baixa Segura, Rateio) | P0 | 7 | `[x]` Concluída (7/7) |
| **Fase 2** | Cobranças Asaas, Emissão em Lote e Carnê Robusto (FEAT-01) | P0 / P1 | 7 | `[x]` Concluída (7/7) |
| **Fase 3** | Fluxos Operacionais, Caixa Ágil (FEAT-02), Navegação Cruzada (FEAT-03) e Acordos | P1 | 9 | `[x]` Concluída (9/9) |
| **Fase 4** | Relatórios (DRE/Fluxo), Régua de Cobrança, Otimização de Performance e Dashboard | P1 / P2 | 8 | `[x]` Concluída (8/8) |
| **Fase 5** | Limpeza de Código Morto, Qualidade, Documentação e Migrations Finais | P2 / P3 | 6 | `[x]` Concluída (6/6) |
| **Fase 6** | Homologação Ponta a Ponta no Sandbox do Asaas (Checklist E2E) | Homologação | 14 itens | `[x]` Concluída (14/14) |

---

## 🛠️ Detalhamento das Fases e Tarefas

### Fase 0: Baseline, Validação e Harness de Testes `[CONCLUÍDA]`

- [x] **T0.1: Levantamento de Premissas e Validações Iniciais**
  - Mapeamento das tabelas legadas do i-Educar (`pmieducar.*`, `cadastro.*`, `menus`).
  - Documentação das decisões de compatibilidade com PostgreSQL e PHP 8.3+.
- [x] **T0.2: Montagem do Testbench e Suporte a Testes Automatizados**
  - Configuração do `orchestra/testbench` e `phpunit.xml`.
  - Criação da base de testes em `tests/TestCase.php` com suporte a SQLite in-memory e PostgreSQL.
- [x] **T0.3: Unificação de Workflows de Integração Contínua**
  - Normalização da validação do Composer e execução do PHPUnit.
- [x] **T0.4: Ajuste do `composer.json` e Compatibilidade PHP 8.3 / Laravel 12**
  - Restrições de dependências alinhadas e `.gitattributes` configurado.
- [x] **T0.5: Teste Contratual de Resolução de Rotas do Pacote**
  - Verificação de rotas `financeiro.*` evitando referências a métodos inexistentes.
- [x] **T0.6: Criação da Suíte de Regressão `PrdComplianceTest`**
  - Implementação inicial cobrindo cenários do PRD de correções.

---

### Fase 1: Segurança e Integridade Transacional (P0) `[CONCLUÍDA]`

- [x] **T1.1 (BUG-010): Controle de Acesso e RBAC por Processo Financeiro**
  - Middleware `AutorizarProcessoFinanceiro` (`financeiro.can:{processo},{acao}`).
  - Rotas de `routes/web.php` protegidas com matriz de permissões.
  - Migration corretiva `2026_10_15_000016_permissoes_financeiro_corretivas.php` para tabela `menu_tipo_usuario`.
  - Tratamento com página amigável e resposta 403 para acessos não autorizados.
- [x] **T1.2 (BUG-012): Proteção e Criptografia da API Key do Asaas**
  - Cast `'encrypted'` no atributo `api_key` do model `GatewayConfiguracao`.
  - Mascaramento em tela com `••••••••` no formulário `resources/views/gateway.blade.php`.
  - Log de auditoria no `GatewayConfigController` quando as credenciais forem alteradas.
- [x] **T1.3 (BUG-011): Fortalecimento e Resiliência do Webhook Asaas**
  - Validação estrita do token via `hash_equals` (`asaas-access-token`).
  - Rejeição de webhooks quando token não estiver configurado no banco (503/401).
  - Rate limiting no endpoint `/financeiro/webhooks/asaas`.
  - Bloqueio de baixa automática quando o valor recebido diferir do valor esperado (marcação de divergência).
  - Migration `2026_10_15_000018_update_pagamentos_and_webhook_events.php` adicionando `requer_acao`, `erro` e `idempotency_key`.
  - Criação do comando `financeiro:reprocessar-webhooks` (`ReprocessarWebhooksCommand.php`).
- [x] **T1.4 (BUG-004 / BUG-005): Bloqueio de Baixa Indevida e Gestão de Créditos**
  - Criação do Model `CreditoResponsavel` e migration `2026_10_15_000017_create_creditos_responsavel_and_update_tables.php`.
  - `BaixaTituloService` atualizado para gerar crédito automaticamente quando o valor recebido superar o saldo devedor.
  - Bloqueio estrito de baixa em títulos `renegociado`, `cancelado` ou `pago` com `TituloNaoBaixavelException`.
- [x] **T1.5 (BUG-006 / BUG-007): Persistência de Escola em Despesas e Cadastro Rápido Flexível**
  - `ContaPagarController` agora persiste `escola_id` recebido no formulário de contas a pagar.
  - Inclusão do `<select name="escola_id">` em `contas-pagar/create.blade.php`.
  - Colunas de endereço e contato flexibilizadas para `NULL` no cadastro rápido de responsáveis.
  - Método `cadastroCompleto()` adicionado em `ResponsavelFinanceiro`.
- [x] **T1.6 (BUG-008): Eliminação de Rotas Órfãs e Métodos Inexistentes**
  - Implementação dos métodos faltantes em `PlanoPagamentoController` (`edit`, `update`), `ContratoController` (`edit`, `update`) e `ContaPagarController` (`show`, `liquidar`).
- [x] **T1.7 (BUG-009): Motor de Rateio entre Responsáveis Financeiros**
  - Criação do `RateioResponsavelService` com validação de soma exata de 100% e garantia de um único responsável principal.
  - Criação de `AlunoResponsavel` sincronizada com o i-Educar.

---

### Fase 2: Cobranças Asaas, Emissão em Lote e Carnê Robusto (P0/P1) `[EM ANDAMENTO]`

- [x] **T2.1 (BUG-032): Refatoração do Driver `AsaasGateway`**
  - Implementação de retry com backoff exponencial para lidar com HTTP 429 e indisponibilidades momentâneas.
  - Criação ou recuperação inteligente de clientes (`asaas_customer_id`) no Asaas.
  - Mapeamento estrito de parâmetros e enum de cancelamento de cobranças.
- [x] **T2.2 (BUG-016): Suporte a Múltiplas Cobranças e Histórico por Título**
  - Migration `2026_10_15_000020_add_multi_cobranca_and_lotes_processamento.php` adicionando coluna `ativa` em `cobrancas_gateway`.
  - Relação `Titulo::cobrancaGateway()` retornando a cobrança ativa e `cobrancas()` para histórico completo.
  - Cancelamento da cobrança anterior antes da emissão de nova cobrança ativa.
- [x] **T2.3 (BUG-001 / BUG-002): Emissão de Cobranças em Lote e Acompanhamento de Processamentos**
  - Refatoração do `GerarCobrancasGatewayLoteJob` com suporte tanto a lista de IDs quanto a competência/escola.
  - Criação da tabela `financeiro.lotes_processamento` para monitorar progresso assíncrono.
  - Correção de `TituloController@emitirLoteAsaas` utilizando `GatewayManager` diretamente.
- [x] **T2.4 (BUG-003 / FEAT-01): Carnê Asaas Robusto (Installments)**
  - Implementação do `CarneService` com validações de elegibilidade de parcelamento (vencimentos futuros, valores iguais, sem títulos vencidos).
  - Criação do Model `CarneGateway` com tracking de `installment_id` e link do carnê.
  - Fallback para impressão de carnê local caso o gateway não suporte ou falhe.
- [x] **T2.5 (BUG-031): Cancelamento Idempotente de Cobranças e Parcelamentos**
  - Job `CancelarCobrancaGatewayJob` tratando idempotência e erros 404 (cobrança já inexistente no gateway).
  - Cancelamento de carnê Asaas ao cancelar contrato ou renegociar títulos.
- [x] **T2.6 (BUG-019 / BUG-027.1): Comando de Sincronização e Ação de Lote na UI**
  - Implementação real de `SincronizarCobrancasAsaasCommand.php` para conciliar status de títulos pendentes em chunks com rate limiting.
  - Criação da rota `POST titulos/sincronizar-lote-asaas` no `TituloController` para disparo em lote pela interface.
  - Botão e ação "Sincronizar Asaas" integrados à barra flutuante de seleção em lote em `titulos/index.blade.php`.
- [x] **T2.7 (BUG-027.2): Registro de Rotinas Agendadas no `FinanceiroServiceProvider`**
  - Registro no `callAfterResolving(Schedule::class)` das rotinas:
    - `financeiro:processar-regua` (diário às 08:00)
    - `financeiro:sincronizar-asaas` (horário)
    - `financeiro:atualizar-status` (diário às 00:10)
    - `financeiro:reprocessar-webhooks` (a cada 5 minutos)

---

### Fase 3: Fluxos Operacionais, Caixa e Navegação Cruzada (P1) `[x] 100% Concluída`

- [x] **T3.1 (FEAT-02 / BUG-017 / BUG-013): Caixa / Baixa Ágil de Balcão**
  - Criação do endpoint `GET financeiro/api/titulos/buscar?q=` (busca incremental por aluno, responsável, CPF, nº título/contrato).
  - Criação do endpoint `GET financeiro/api/titulos/{id}/calculo` usando `Support/CalculoEncargos`.
  - Baixa de múltiplos títulos do mesmo responsável em transação única com `idempotency_key`.
  - Recálculo dinâmico na tela de caixa (`baixas/create.blade.php`), campo de troco/crédito e recibo por `pagamento_id`.
- [x] **T3.2 (FEAT-03 / BUG-018): Botões de Navegação Cruzada e Filtros Avançados**
  - Adição de filtros em `titulos.index`: responsável, contrato, aluno, ano letivo e turma (com joins necessários).
  - Inclusão de atalhos rápidos entre telas relacionadas (Ver mensalidades do responsável, Ver títulos do contrato, Renegociar a partir do título).
- [x] **T3.3 (BUG-023): Geração de Títulos Faltantes e Unicidade de Contratos**
  - Implementação de `ContratoService::gerarTitulosFaltantes()` respeitando rateio e snapshot do plano (`plano_snapshot`).
  - Bloqueio de duplicidade de contratos para a mesma matrícula no mesmo ano letivo via trava e índice parcial `idx_contratos_matricula_ano_ativo`.
  - Auditoria de cancelamento (`cancelado_em`, `cancelado_por`) e cancelamento síncrono de carnês de gateway.
- [x] **T3.4 (BUG-024 / FEAT-08): Edição e Cópia de Planos de Pagamento**
  - Permitir edição de planos sem contratos vinculados (`edit` / `update`) e cópia/duplicação de plano para novo ano letivo (`planos.copiar`).
  - Seleção de escola por dropdown e controle correto de competências.
- [x] **T3.5 (BUG-025): Gestão de Responsáveis Financeiros e Autocomplete de Alunos**
  - Exclusão com verificação de impedimentos (contratos ou títulos pendentes ou créditos).
  - Alternância de status ativo/inativo na edição e suporte à restauração de soft-delete para reaproveitamento de CPF.
- [x] **T3.6 (BUG-033): Disparo Manual de WhatsApp com Registro de Auditoria**
  - Endpoint `titulos/{id}/notificar-whatsapp` sanitizando número E.164 (DDI 55), gerando URL oficial e log de auditoria.
  - Resposta amigável com erro 422 JSON caso responsável não possua celular válido, sem crash 500.
- [x] **T3.7 (FEAT-07 / BUG-020): Renegociação de Débitos Completa**
  - Transação com `lockForUpdate` ordenado e validação anti-tampering via `valor_original_esperado` contra concorrência.
  - Numeração sequencial de acordos por sequence de banco e cancelamento de cobranças Asaas de títulos originais.
- [x] **T3.8 (FEAT-10 / BUG-026): Contas a Pagar Completo e Conciliação de Saldo**
  - Atualização do saldo da conta bancária na liquidação e criação de extrato em `financeiro.movimentacoes_conta`.
  - Bloqueio de liquidação duplicada, estorno de liquidação (`contas-pagar.estornar`) e tela completa `contas-pagar/show.blade.php`.
- [x] **T3.9 (BUG-014 / FEAT-09): Integração com Matrícula do i-Educar e Tela "Matrículas sem Contrato"**
  - Registro de listeners de matrícula (`MatriculaCriada`, `MatriculaCancelada`) no `FinanceiroServiceProvider`.
  - Interface dedicada `contratos/matriculas-sem-contrato.blade.php` com pré-preenchimento do formulário de novo contrato.

---

### Fase 4: Relatórios, Régua de Cobrança e Performance (P1/P2) `[x] 100% Concluída`

- [x] **T4.1 (BUG-022): Dashboard Otimizado e Comando `atualizar-status`**
  - Eliminar escrita em GET (`UPDATE titulos SET status = 'pago'`) no `DashboardController`.
  - Agregações em SQL puro (`COUNT/SUM` com `FILTER`) e paginação no dashboard.
  - Criação do comando `financeiro:atualizar-status` para rotina noturna (registrado no scheduler e testado via T36).
- [x] **T4.2 (BUG-030): Eager Loading e Resolução em Lote de Alunos e Turmas**
  - Implementação de `Support/AlunoLookup` com memoização estática de request e busca em lote.
  - Eliminação de N+1 queries em listagens de títulos e dashboard.
- [x] **T4.3 (BUG-021 / FEAT-11): Correção de Conceitos DRE, Fluxo de Caixa e Relatórios**
  - Fórmulas contábeis estritas: Receita Bruta = $\Sigma(\text{pago} + \text{desconto})$, Deduções = $\Sigma(\text{desconto})$, Receita Líquida = $\Sigma(\text{pago})$, linha separada de encargos.
  - Exportação em CSV de DRE, Fluxo de Caixa, Inadimplência, IRPF e Quitação Anual.
  - Endpoint de verificação pública `/financeiro/verificar/{hash}` para recibos e certidões.
- [x] **T4.4 (BUG-028): Relatório de Inadimplência e API de Verificação de Rematrícula**
  - Remoção de relação inexistente `with(['aluno'])` e uso de `Titulo::vencidos()` com filtro de tolerância.
  - Criação da API pública `FinanceiroInadimplencia::alunoInadimplente($alunoId)` para bloqueio consultivo de rematrícula.
- [x] **T4.5 (BUG-027.3-10): Régua de Cobrança Multi-canal e Janela de Tolerância**
  - Abstração de canais plugáveis (`CanalNotificacaoInterface`), drivers `MailCanal`, `WhatsAppLinkCanal` e `AsaasNotificacaoCanal`.
  - Janela de tolerância de dias contra falhas do agendador e controle estrito de horário comercial (07h às 22h).
- [x] **T4.6 (BUG-029): Evento `TituloPago` com Payload Fiscal Consistente**
  - Mapeamento correto de campos do responsável (`telefone_celular`, `logradouro + numero + complemento`, `estado`) e criação de `EmitirNfseListener`.
- [x] **T4.7 (FEAT-06): Gestão de Descontos e Convênios com Escopo**
  - Migration `000022` adicionando `escopo`, `alvo_id`, `teto_valor` e `teto_percentual`.
  - `DescontoService` filtrando por escopo e registrando auditoria histórica em `financeiro.titulo_descontos`.
- [x] **T4.8 (FEAT-05): Tela de Processamentos e Pendências**
  - Interface centralizada `financeiro.processamentos.index` para monitorar lotes assíncronos e webhooks.
  - Ações administrativas para reprocessar e ignorar webhooks com justificativa.

---

### Fase 5: Qualidade, Limpeza e Documentação (P2/P3) `[x] 100% Concluída`

- [x] **T5.1 (BUG-035): Limpeza de Configurações Mortas**
  - Remoção de chaves não utilizadas (`gateways.efi`, etc.) e consolidação de `config/financeiro.php`.
- [x] **T5.2 (BUG-038): Índices e Otimizações de Banco de Dados**
  - Migration `2026_10_15_000023_fase5_indices_e_checks.php` adicionando índices compostos e CHECK constraints.
- [x] **T5.3 (BUG-037): Remoção de Dependências Externas (Fontes Locais)**
  - Remoção de links externos do Google Fonts em `styles.blade.php`, `compiled_dashboard.css` e `recibo.blade.php` para total conformidade com LGPD e redes isoladas.
- [x] **T5.4 (BUG-039 / BUG-040): Consolidação de ServiceProvider, Middlewares e i18n**
  - Eliminação de resoluções redundantes no View Composer e criação de `resources/lang/pt_BR/mensagens.php` para mensagens padronizadas.
- [x] **T5.5 (BUG-041): Correções Menores e Ajustes Visuais**
  - Mapeamento estrito de eventos no `ProcessarWebhookJob`, suporte a cartão de crédito/débito em `sincronizarStatus`, trava em `imprimirCarne` contra seleções vazias.
- [x] **T5.6: Atualização Completa da Documentação do Pacote**
  - Atualização do `README.md`, `TASKS.md` com status real, guias do scheduler e instruções de instalação.

---

### Fase 6: Homologação Ponta a Ponta no Sandbox Asaas `[x] 100% Concluída`

> **Evidências Formais:** Registradas em [docs/HOMOLOGACAO-EVIDENCIAS.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/docs/HOMOLOGACAO-EVIDENCIAS.md)  
> **Suíte Automatizada:** Executada via `tests/Feature/HomologacaoE2ETest.php` (14 cenários, 100% aprovados)

- [x] **TC-01 a TC-14: Execução da Checklist E2E de Homologação**
  - [x] **TC-01:** Configuração do Gateway no Sandbox Asaas com criptografia AES-256 no banco, máscara `••••••••` sem vazamento na view, teste de conexão HTTP e registro automatizado de webhook.
  - [x] **TC-02:** Criação de plano 12x e contrato com 2 responsáveis (rateio 50/50), gerando 24 parcelas exatas de R$ 50,00 (soma R$ 1.200,00) e bloqueio contra duplo submit de matrícula/ano.
  - [x] **TC-03:** Emissão em lote assíncrona com rastreabilidade total em `financeiro.lotes_processamento` (`status = 'concluido'`, `total = 3`, `sucesso = 3`).
  - [x] **TC-04:** Geração de carnê oficial com 12 cobranças vinculadas e baixa atômica via webhook `PAYMENT_RECEIVED` com idempotência e conciliação da parcela 3.
  - [x] **TC-05:** Caixa ágil de balcão com busca incremental (`/api/titulos/buscar`), cálculo de encargos em tempo real (`/api/titulos/{id}/calculo`), baixa com troco e verificação de autenticidade documental por hash SHA-256 (`/financeiro/verificar/{hash}`).
  - [x] **TC-06:** Pagamento a maior com geração automática de crédito em `financeiro.creditos_responsaveis` e compensação posterior no saldo de novo título.
  - [x] **TC-07:** Estorno de pagamento protegido por RBAC: rejeição 403 para usuário comum e execução autorizada para administrador restaurando o saldo do título.
  - [x] **TC-08:** Renegociação de títulos vencidos com entrada, cancelamento das cobranças Asaas antigas, geração do termo de confissão de dívida e novos títulos renegociados.
  - [x] **TC-09:** Cancelamento de contrato com rescisão das parcelas futuras e cancelamento imediato das cobranças no gateway Asaas.
  - [x] **TC-10:** Lançamento de despesa em Contas a Pagar com `escola_id` obrigatório, liquidação com débito em `ContaBancaria`, extrato em `financeiro.movimentacoes_conta` e bloqueio de liquidação duplicada.
  - [x] **TC-11:** Validação contábil estrita da DRE (Receita Bruta = $\Sigma(\text{pago}+\text{desconto})$, Deduções = $\Sigma(\text{desconto})$, Receita Líquida = $\Sigma(\text{pago})$), Fluxo de Caixa, IRPF, Quitação Anual e exportação CSV com UTF-8 BOM (`\xEF\xBB\xBF`).
  - [x] **TC-12:** Matriz de navegação e auditoria RBAC testando todas as rotas com administrador (200 OK) vs usuário não autorizado (403 Forbidden).
  - [x] **TC-13:** Fila e reprocessamento de webhooks com falha temporária via comando `financeiro:reprocessar-webhooks --limit=50`.
  - [x] **TC-14:** Integração de eventos de matrícula do i-Educar (`MatriculaCriada`, `MatriculaCancelada`) e exibição de alunos sem contrato na tela "Matrículas sem Contrato".
  - [x] **Documentação de Homologação:** Gerado o documento oficial [docs/HOMOLOGACAO-EVIDENCIAS.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/docs/HOMOLOGACAO-EVIDENCIAS.md).
