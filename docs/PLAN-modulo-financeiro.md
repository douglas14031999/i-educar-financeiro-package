# Plano de Projeto: Módulo Financeiro para o i-Educar (Escolas Privadas)

| Informação | Valor |
| :--- | :--- |
| **Documento** | `docs/PLAN-modulo-financeiro.md` |
| **Projeto** | `i-educar-financeiro-package` |
| **Tipo de Projeto** | **BACKEND / FULL-STACK PACKAGE** (Laravel Composer Package acoplado ao i-Educar 2.10) |
| **Documentos Chave** | [PRD.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/PRD.md) \| [TASKS.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/TASKS.md) |
| **Agente Primário** | `backend-specialist` |
| **Agente de Apoio UI/UX** | `frontend-specialist` |
| **Agente de Segurança** | `security-auditor` |

---

## 1. Visão Geral
Construção de um pacote Composer Laravel (`i-educar-financeiro-package`) para suprir a demanda financeira de escolas privadas no ecossistema do **i-Educar 2.10**. O pacote reutiliza 100% dos dados pedagógicos já existentes (alunos, turmas, matrículas, escolas, cursos) sem duplicações, operando em um schema isolado do PostgreSQL (`financeiro.*`), com motor de contratos, planos de pagamento, liquidação de títulos, integração com o gateway **Asaas** (Boleto híbrido com Pix embutido, Pix dinâmico e Cartão de crédito), régua de cobrança automática por E-mail/WhatsApp, conciliação com idempotência, contas a pagar e relatórios fiscais/gerenciais (DRE, IRPF, Lei 12.007/2009).

> [!NOTE]
> **Decisões Alinhadas no Socratic Gate:**
> 1. **Gateway Principal no MVP:** Asaas (Boleto com Pix QR Code embutido, Pix instantâneo e Cartão).
> 2. **Cancelamento de Matrícula:** Cancela apenas parcelas futuras não vencidas; títulos vencidos permanecem ativos em cobrança.
> 3. **Sem Portal de Pais:** O envio de boletos e Pix é feito diretamente por links e mensagens via E-mail / WhatsApp, sem exigir portal autônomo de login para os pais.

---

## 2. Critérios de Sucesso Mensuráveis
1. **Zero Redundância:** Nenhuma tabela de aluno ou turma duplicada; 100% via Foreign Keys diretas para `pmieducar.*` e `cadastro.*`.
2. **Isolamento de Banco:** 100% das tabelas do pacote criadas e manipuladas sob o schema PostgreSQL `financeiro.*`.
3. **Idempotência em Pagamentos:** 100% dos webhooks do Asaas validados com chave única de idempotência, garantindo risco zero de baixa duplicada.
4. **Conformidade de Interface:** Menus e telas financeiras integrados de forma nativa ao layout do i-Educar 2.10 com controle de permissões por tipo de usuário.
5. **Automação Fiscal:** Emissão de informe de IRPF em PDF e declaração anual de quitação de débitos (Lei 12.007/2009) em menos de 2 segundos por aluno.

---

## 3. Stack Tecnológica e Racional Arquitetural
- **Linguagem & Framework:** PHP 7.4 / 8.1+ com Laravel Framework (compatível com a versão instalada no core do i-Educar 2.10).
- **Banco de Dados:** PostgreSQL 10+ (utilizando schema dedicado `financeiro`).
- **Padrão de Integração:** Composer Package com `FinanceiroServiceProvider` registrando migrations, rotas, views Blade, traduções e comandos de console.
- **Padrão de Gateways:** *Gateway Driver Pattern* (interface `GatewayInterface` com driver completo do Asaas no MVP).
- **Processamento Assíncrono:** Laravel Queues / Jobs para geração de parcelas em massa, disparo de notificações da régua de cobrança e processamento de webhooks.

---

## 4. Estrutura de Diretórios Planejada
```
i-educar-financeiro-package/
├── composer.json
├── config/
│   └── financeiro.php
├── database/
│   ├── migrations/
│   └── seeders/
├── src/
│   ├── FinanceiroServiceProvider.php
│   ├── Commands/
│   ├── Models/
│   ├── Services/
│   ├── Gateways/
│   │   ├── Contracts/
│   │   ├── Drivers/ (AsaasGateway.php)
│   │   └── GatewayManager.php
│   ├── Jobs/
│   ├── Events/ e Listeners/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Webhooks/
│   └── Reports/
├── resources/
│   ├── views/
│   └── lang/
├── routes/
│   ├── web.php
│   └── api.php
├── tests/
├── PRD.md
├── TASKS.md
└── docs/
    └── PLAN-modulo-financeiro.md
```

---

## 5. Relação de Tarefas e Acompanhamento

O detalhamento minucioso de cada uma das **63 tarefas** divididas em 13 fases está formalizado em:
👉 **[TASKS.md](file:///c:/Users/Douglas/Downloads/i-educar-financeiro-package/TASKS.md)**

### Status Consolidado do Projeto (100% Concluído):
| Fase | Descrição | Status |
| :--- | :--- | :---: |
| **Fase 1** | Setup do Pacote Composer e ServiceProvider | ✅ Concluído |
| **Fase 2** | Schema PostgreSQL e Migrations (`financeiro.*`) | ✅ Concluído |
| **Fase 3** | Integração de Menus e Permissões do i-Educar | ✅ Concluído |
| **Fase 4** | Models Eloquent e Cadastros Base | ✅ Concluído |
| **Fase 5** | Motor de Contratos e Geração de Parcelas | ✅ Concluído |
| **Fase 6** | Baixa Manual, Pagamentos e Descontos | ✅ Concluído |
| **Fase 7** | Gateway de Cobrança Asaas (Boleto/Pix) e Webhooks | ✅ Concluído |
| **Fase 8** | Régua de Cobrança e Gestão de Inadimplência | ✅ Concluído |
| **Fase 9** | Renegociação de Dívidas e Acordos | ✅ Concluído |
| **Fase 10** | Contas a Pagar, Plano de Contas e Fluxo de Caixa | ✅ Concluído |
| **Fase 11** | Relatórios Gerenciais, DRE e Informe IRPF / Lei 12.007 | ✅ Concluído |
| **Fase 12** | Disparo de Links de Cobrança e Comunicação (E-mail/WhatsApp) | ✅ Concluído |
| **Fase X** | Verificação, Auditoria de Segurança e Testes | ✅ Concluído |

