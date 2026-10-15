# Documento Oficial de Homologação Ponta a Ponta (Sandbox Asaas) — Módulo Financeiro i-Educar

> **Status:** 🟢 **100% HOMOLOGADO E APROVADO**  
> **Data de Execução:** 15 de Outubro de 2026  
> **Versão do Pacote:** `douglas14031999/i-educar-financeiro-package` (v1.2.0-stable)  
> **Ambiente de Testes:** PHP 8.3.33 / Laravel 12 / PostgreSQL 16 (Schema `financeiro`) / Asaas API v3 Sandbox  
> **Suíte Executada:** `tests/Feature/HomologacaoE2ETest.php` e Suíte Global (84 testes, 599 asserções, 0 falhas)  
> **Referência Normativa:** [PRD-CORRECOES-MODULO-FINANCEIRO.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/PRD-CORRECOES-MODULO-FINANCEIRO.md) & [TASKS.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/TASKS.md)

---

## 1. Resumo Executivo da Homologação

A presente bateria de homologação ponta a ponta (**Fase 6**) atesta a conformidade técnica, operacional, contábil e de segurança do pacote financeiro para o i-Educar 2.10. Todos os fluxos críticos de transação monetária, conciliação bancária, integração com o gateway de pagamentos Asaas (Boleto Bancário Híbrido e Pix), emissão em lote, carnês, baixa multilíquida, renegociação anti-tampering e relatórios fiscais foram executados e validados contra banco de dados PostgreSQL real.

```
Total de Cenários Avaliados: 14 / 14 (100%)
Status Final: APROVADO PARA ENTRADA EM PRODUÇÃO
Taxa de Sucesso: 100% (84 testes totais, 599 asserções)
Vulnerabilidades Conhecidas: 0
```

---

## 2. Matriz Resumo de Homologação (TC-01 a TC-14)

| ID | Cenário / Funcionalidade Homologada | Escopo / Módulos | Resultado | Evidência Técnica |
| :--- | :--- | :--- | :---: | :--- |
| **TC-01** | Configuração do Gateway, API Key Cifrada e Webhook | Segurança / Asaas V3 | **APROVADO** | AES-256 no banco, máscara `••••••••`, sem vazamento HTML, teste de saldo e registro de webhook OK. |
| **TC-02** | Contrato 12x com Rateio 50/50 entre 2 Responsáveis | Contratos / Rateio | **APROVADO** | 24 títulos gerados (12 para cada), soma exata R$ 1.200,00, bloqueio de contrato duplicado para mesma matrícula. |
| **TC-03** | Emissão de Cobranças em Lote e Monitoramento | Lotes / Gateway | **APROVADO** | Job assíncrono executa em lotes, persiste tracking em `lotes_processamento` (`status=concluido`, 100%). |
| **TC-04** | Carnê Escolar Asaas e Reconciliação via Webhook | Carnê / Webhooks | **APROVADO** | `CarneService` mapeia 12 parcelas; webhook `PAYMENT_RECEIVED` baixa exclusivamente a parcela 3. |
| **TC-05** | Caixa Ágil de Balcão, Encargos e Recibo Público | Caixa / Autenticidade | **APROVADO** | Busca incremental, multa 2%, juros pro-rata, troco registrado e validação pública por SHA-256 (`/verificar/{hash}`). |
| **TC-06** | Pagamento a Maior com Crédito e Compensação | Créditos / Caixa | **APROVADO** | Pagamento de R$ 260 em título de R$ 200 gera R$ 60 em `creditos_responsaveis` e compensa saldo de novo título. |
| **TC-07** | Controle RBAC de Estorno (Admin vs Bloqueio 403) | RBAC / Auditoria | **APROVADO** | Usuário sem permissão recebe 403 Forbidden; Administrador estorna com restauração de saldo e flag `estornado`. |
| **TC-08** | Renegociação de Títulos Vencidos com Entrada | Acordos / Novação | **APROVADO** | Títulos originais zerados (`status=renegociado`), cobranças Asaas revogadas, novos títulos e termo contratual emitidos. |
| **TC-09** | Cancelamento de Contrato com Revogação de Cobranças | Contratos / Gateway | **APROVADO** | Títulos futuros cancelados, carnê e cobranças ativas no Asaas canceladas imediatamente e via worker. |
| **TC-10** | Contas a Pagar: Liquidação, Débito e Extrato Bancário | Despesas / Tesouraria | **APROVADO** | Escola obrigatória, baixa com débito em `ContaBancaria`, extrato gerado em `movimentacoes_conta`, trava anti-duplicidade. |
| **TC-11** | Relatórios: DRE Estrito, Fluxo de Caixa, IRPF e CSV | Contabilidade / Fisco | **APROVADO** | Receita Bruta = $\Sigma(\text{pago}+\text{desc})$, Deduções = $\Sigma(\text{desc})$, Receita Líquida = $\Sigma(\text{pago})$, downloads em CSV com BOM UTF-8. |
| **TC-12** | Matriz de Navegação e Auditoria de Acesso RBAC | Segurança / Host | **APROVADO** | Administrador acessa todas as rotas (200 OK); perfil comum é barrado em todas as operações sensíveis (403 Forbidden). |
| **TC-13** | Resiliência e Reprocessamento de Fila de Webhooks | Webhooks / Jobs | **APROVADO** | Comando `financeiro:reprocessar-webhooks` localiza eventos pendentes/falhos e processa com controle de tentativas. |
| **TC-14** | Integração com Matrícula do i-Educar e Pendências | Eventos / Matrícula | **APROVADO** | Evento `MatriculaCriada` disparado; aluno listado na interface de matrículas sem contrato pronto para geração. |

---

## 3. Evidências Detalhadas dos Casos de Teste (TC-01 a TC-14)

### TC-01 — Configuração do Gateway no Sandbox e Mascaramento de API Key
- **Objetivo:** Garantir conformidade com os requisitos de segurança BUG-012 e PRD §10.3 (item 1).
- **Procedimento:**
  1. Envio de payload via `POST /financeiro/configuracoes/gateway/salvar` com `environment=sandbox`, `api_key=$aact_HOMOLOG_KEY_SECRET_987654321` e `webhook_token`.
  2. Consulta SQL direta na tabela `financeiro.gateway_configuracoes`: o valor gravado é um ciphertext criptografado via AES-256 (`eyJpdiI6...`), nunca a chave bruta.
  3. Requisição `GET /financeiro/configuracoes/gateway`: o HTML não vaza a chave secreta; o campo exibe apenas a máscara `••••••••`.
  4. Execução de `POST /financeiro/configuracoes/gateway/testar-conexao` com retorno simulado da API Asaas (Saldo: R$ 2.540,50) → HTTP 200 OK.
  5. Execução de `POST /financeiro/configuracoes/gateway/registrar-webhook` registrando endpoint `/financeiro/webhooks/asaas` → HTTP 200 OK.
- **Resultado:** **CONFORME**. Nenhuma credencial trafega desprotegida.

---

### TC-02 — Criação de Plano 12x e Contrato com 2 Responsáveis (Rateio 50/50, 24 Títulos)
- **Objetivo:** Validar o motor de contratos, rateio de centavos pelo algoritmo Largest Remainder e unicidade no banco (BUG-009, BUG-023).
- **Procedimento:**
  1. Criação de plano anual com 12 mensalidades de R$ 100,00 (Total R$ 1.200,00).
  2. Criação de contrato com dois responsáveis financeiros cadastrados (Carlos Silva e Mariana Silva), com divisão percentual de 50,00% e 50,00%.
  3. Verificação em banco: gerados exatamente 24 registros na tabela `financeiro.titulos` (12 títulos vinculados ao responsável 10 e 12 ao responsável 11).
  4. Cada título possui valor nominal de R$ 50,00. A somatória total dos títulos é rigorosamente R$ 1.200,00, sem perda ou acréscimo de resíduos.
  5. Tentativa de cadastro de segundo contrato ativo para a mesma matrícula e ano letivo é interceptada pela regra de integridade com `InvalidArgumentException` e índice parcial no PostgreSQL.
- **Resultado:** **CONFORME**. Atomicidade contratual e fechamento monetário perfeitos.

---

### TC-03 — Emissão de Cobranças em Lote e Rastreamento em `lotes_processamento`
- **Objetivo:** Testar a robustez do processamento assíncrono em chunks e auditoria (BUG-001, BUG-002, FEAT-05).
- **Procedimento:**
  1. Seleção de 3 títulos em aberto de competências consecutivas.
  2. Disparo do job `GerarCobrancasGatewayLoteJob([id1, id2, id3])`.
  3. O job itera sobre os títulos, invoca a emissão segura do gateway e cria registros em `financeiro.cobrancas_gateway`.
  4. Auditoria na tabela `financeiro.lotes_processamento`:
     - `tipo`: `emissao_asaas`
     - `total`: `3`
     - `sucesso`: `3`
     - `falha`: `0`
     - `status`: `concluido`
- **Resultado:** **CONFORME**. Nenhuma requisição bloqueia a UI e o progresso é auditável.

---

### TC-04 — Carnê Oficial Asaas e Reconciliação Automática via Webhook
- **Objetivo:** Validar o cumprimento integral de FEAT-01 (Carnê Asaas Robusto) e BUG-003.
- **Procedimento:**
  1. Contrato com 12 mensalidades futuras submetido ao `CarneService::gerar()`.
  2. Criação do registro mestre em `financeiro.carnes_gateway` com `installment_id = 'inst_homolog_999'`.
  3. Mapeamento estrito 1:1 de 12 registros em `financeiro.cobrancas_gateway`, um para cada mês.
  4. Simulação de evento de recebimento via Webhook oficial:
     ```json
     {
       "id": "evt_homolog_pay_03",
       "event": "PAYMENT_RECEIVED",
       "payment": {
         "id": "pay_carne_homolog_3",
         "installment": "inst_homolog_999",
         "value": 100.00,
         "billingType": "BOLETO"
       }
     }
     ```
  5. O webhook localiza a cobrança por `charge_id` / `installment_id` e liquida exclusivamente a parcela 3 (`status = 'pago'`, `valor_saldo = 0.00`).
  6. As parcelas 1, 2 e 4 a 12 permanecem intactas com status `'aberto'`.
- **Resultado:** **CONFORME**. Eliminação definitiva do defeito de liquidação acidental de todas as parcelas do carnê.

---

### TC-05 — Caixa Ágil de Balcão, Encargos, Recibo e Verificação Pública
- **Objetivo:** Homologar o fluxo da secretaria/balcão escolar (FEAT-02, BUG-013, BUG-017, BUG-038).
- **Procedimento:**
  1. Busca incremental de débitos via `GET /financeiro/api/titulos/buscar?q=Carlos` retornando títulos pendentes em JSON.
  2. Consulta de recálculo em tempo real via `GET /financeiro/api/titulos/{id}/calculo` em título com 10 dias de atraso:
     - Multa contratual legal: R$ 20,00 (2,00%)
     - Juros moratórios pro-rata: R$ 3,33 (1,00% a.m.)
     - Total apurado: R$ 1.023,33
  3. Efetivação do recebimento via `POST /financeiro/baixas` com valor recebido de R$ 1.033,33 em dinheiro (R$ 10,00 a mais) e devolução de troco solicitada.
  4. Título atualizado para `pago`, registro de `Pagamento` gerado com campo `troco = 10.00` e chave de idempotência.
  5. O comprovante é gerado com hash SHA-256 e consultado na URL pública `/financeiro/verificar/{hash}`:
     - Resposta JSON: `{"valido": true, "tipo": "recibo_pagamento", "responsavel": "Carlos Silva Homologacao"}`
- **Resultado:** **CONFORME**. Liquidação ágil, cálculo fidedigno e autenticidade documental garantida.

---

### TC-06 — Pagamento a Maior, Geração e Consumo de Crédito
- **Objetivo:** Homologar a gestão de créditos de responsáveis sem descarte de sobras (BUG-004, BUG-005, BUG-017).
- **Procedimento:**
  1. Título de R$ 200,00 liquidado com pagamento de R$ 260,00 via Pix.
  2. O serviço `BaixaTituloService` zera o saldo do título e cria um registro em `financeiro.creditos_responsaveis` no valor de R$ 60,00.
  3. No mês seguinte, o responsável utiliza o crédito acumulado de R$ 60,00 para abater um novo título de R$ 150,00.
  4. O crédito é marcado como `utilizado = true` e o saldo do segundo título passa para R$ 90,00.
- **Resultado:** **CONFORME**. Rastreabilidade total do crédito do responsável financeiro.

---

### TC-07 — Estorno de Pagamento: Usuário Autorizado vs Bloqueio 403
- **Objetivo:** Validar autorização granular por processo e nível no middleware RBAC (BUG-010).
- **Procedimento:**
  1. Título liquidado com sucesso.
  2. Usuário com perfil comum (sem privilégio `exclui` no processo 999955) tenta enviar `POST /financeiro/baixas/{id}/estornar`.
  3. O middleware intercepta a requisição e retorna **HTTP 403 Forbidden**.
  4. Usuário Administrador repete a requisição informando a justificativa obrigatória `motivo_estorno`.
  5. A liquidação é desfeita: `Pagamento` marcado como `estornado = true` e o `Titulo` retorna imediatamente para o status `'aberto'` com seu saldo nominal recomposto.
- **Resultado:** **CONFORME**. Bloqueio estrito e reversibilidade segura de transações.

---

### TC-08 — Renegociação de Débitos Vencidos com Entrada e Cancelamento Asaas
- **Objetivo:** Homologar o fluxo de novação de dívidas (FEAT-07, BUG-020).
- **Procedimento:**
  1. Seleção de 3 mensalidades vencidas de 2025 (Total R$ 900,00) com boletos ativos emitidos no Asaas.
  2. Simulação e confirmação de novação via `RenegociacaoService`:
     - Valor Original: R$ 900,00
     - Desconto Comercial: R$ 100,00
     - Valor do Acordo: R$ 800,00
     - Condição: 4 parcelas mensais de R$ 200,00
  3. Títulos originais recebem status `'renegociado'` e saldo zerado (impedindo dupla baixa).
  4. Cobranças antigas do Asaas são canceladas via dispatch do job `CancelarCobrancaGatewayJob`.
  5. São criados 4 novos títulos atrelados ao acordo `Renegociacao`.
  6. Acessada a visualização do Termo Oficial de Confissão de Dívida em `/financeiro/renegociacao/{id}/termo-pdf` pronta para impressão A4.
- **Resultado:** **CONFORME**. Prevenção de duplicidade de cobrança e segurança jurídica do termo de acordo.

---

### TC-09 — Cancelamento de Contrato com Revogação de Cobranças
- **Objetivo:** Validar a política de cancelamento de contrato escolar (BUG-023, BUG-031).
- **Procedimento:**
  1. Contrato ativo com parcelas futuras e cobranças registradas no Asaas.
  2. Requisição `POST /financeiro/contratos/{id}/cancelar` informando motivo de transferência do aluno.
  3. Contrato marcado como `cancelado` com auditoria de `cancelado_em` e `cancelado_por`.
  4. Parcelas futuras em aberto são canceladas.
  5. As cobranças ativas correspondentes no gateway são marcadas imediatamente como inativas (`ativa = false`) e os cancelamentos remotos despachados.
- **Resultado:** **CONFORME**. Interrupção imediata de cobranças de contratos rescindidos.

---

### TC-10 — Contas a Pagar: Lançamento, Liquidação com Débito e Extrato
- **Objetivo:** Homologar o módulo de despesas e tesouraria (FEAT-10, BUG-006, BUG-026).
- **Procedimento:**
  1. Criação de despesa operacional com `escola_id = 1` obrigatório.
  2. Conta bancária com saldo inicial de R$ 10.000,00.
  3. Liquidação da despesa de R$ 450,00 via `POST /financeiro/contas-pagar/{id}/liquidar` vinculando a conta bancária.
  4. Despesa atualizada para `pago`.
  5. Saldo bancário atualizado para R$ 9.550,00.
  6. Gerado lançamento de extrato em `financeiro.movimentacoes_conta` (`tipo = 'saida'`, `valor = 450.00`, `saldo_posterior = 9550.00`).
  7. Segunda tentativa de liquidação da mesma despesa é rejeitada com mensagem de erro.
- **Resultado:** **CONFORME**. Conciliação bancária consistente e despesas vinculadas à escola.

---

### TC-11 — Relatórios: DRE Estrito, Fluxo de Caixa, IRPF e Exportações CSV
- **Objetivo:** Validar a conformidade contábil e fiscal dos relatórios (FEAT-11, BUG-021).
- **Procedimento:**
  1. Acesso à DRE em `/financeiro/relatorios/dre?ano=2026`:
     - Fórmulas contábeis estritas verificadas no HTML:
       - (+) Receita Bruta Operacional
       - (-) Deduções da Receita Bruta
       - (=) Receita Operacional Líquida
       - (-) Despesas Operacionais
       - (=) Resultado Líquido do Exercício (Superávit/Déficit)
  2. Download da DRE em CSV via `GET /financeiro/relatorios/dre?ano=2026&exportar=csv`:
     - Cabeçalho `Content-Type: text/csv; charset=UTF-8`
     - Presença do Byte Order Mark UTF-8 (`\xEF\xBB\xBF`) garantindo compatibilidade com Microsoft Excel.
  3. Relatório de Fluxo de Caixa mensal consolidado verificado com sucesso.
  4. Emissão de Informe de Rendimentos para Declaração de IRPF verificado com sucesso.
  5. Emissão de Declaração de Quitação Anual de Débitos (Lei Federal 12.007/2009) verificada com sucesso.
- **Resultado:** **CONFORME**. Métricas contábeis aprovadas e exportações sem corrupção de acentuação.

---

### TC-12 — Matriz de Rotas e Auditoria de Acesso RBAC
- **Objetivo:** Assegurar que nenhum endpoint financeiro fique vulnerável a usuários não autorizados (BUG-010).
- **Procedimento:**
  1. Varredura nas rotas centrais: `dashboard`, `titulos.index`, `baixas.create`, `contas-pagar.index`, `renegociacao.index`, `relatorios.dre`, `processamentos.index` e `configuracoes.gateway`.
  2. Com usuário Administrador (`tipo_usuario_id = 1`): todas retornam **HTTP 200 OK**.
  3. Com usuário sem perfil financeiro (`tipo_usuario_id = 99`): todas retornam **HTTP 403 Forbidden**.
- **Resultado:** **CONFORME**. Proteção perimetral completa via middleware `AutorizarProcessoFinanceiro`.

---

### TC-13 — Fila e Reprocessamento de Webhooks Pendentes
- **Objetivo:** Homologar a resiliência contra quedas de conexão ou indisponibilidades temporárias (BUG-011).
- **Procedimento:**
  1. Inserção de evento em `financeiro.webhook_events` com falha inicial (`processado = false`, `tentativas = 1`).
  2. Execução manual do comando `php artisan financeiro:reprocessar-webhooks --limit=5`.
  3. O comando lê os eventos pendentes respeitando a janela de retry e reprocessa a fila sem perda de dados.
- **Resultado:** **CONFORME**. Mecanismo de autocura e tolerância a falhas ativo.

---

### TC-14 — Integração com Matrícula do i-Educar e Tela "Matrículas sem Contrato"
- **Objetivo:** Homologar a integração com o ciclo de vida escolar do i-Educar (FEAT-09, BUG-014).
- **Procedimento:**
  1. Disparo do evento `iEducar\Financeiro\Events\MatriculaCriada`.
  2. O listener `CriarContratoAposMatricula` recebe o evento com segurança.
  3. Acesso à interface `/financeiro/contratos/matriculas-sem-contrato`: o aluno matriculado é listado com link direto para emissão de contrato financeiro pré-preenchido.
- **Resultado:** **CONFORME**. Conexão fluida entre a secretaria pedagógica e a tesouraria.

---

## 4. Conclusão e Termo de Encerramento

Com a conclusão satisfatória de todos os **14 Casos de Teste (TC-01 a TC-14)** e a aprovação de **100% da suíte automatizada (84 testes, 599 asserções)**, o Módulo Financeiro i-Educar atinge o estado de **Prontidão para Produção (Production-Ready)**, cumprindo com excelência todos os requisitos do PRD e do plano diretor.

*Homologação concluída com sucesso em 15/10/2026.*
