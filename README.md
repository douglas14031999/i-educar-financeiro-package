# Módulo Financeiro para o i-Educar (Escolas Privadas e Comunitárias)

[![Latest Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/douglas14031999/i-educar-financeiro-package)
[![License: GPL-2.0](https://img.shields.io/badge/License-GPL%202.0-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.3-blue.svg)](composer.json)
[![Laravel Compatible](https://img.shields.io/badge/Laravel-12.x-red.svg)](composer.json)
[![Tests Passing](https://img.shields.io/badge/tests-51%20passed-brightgreen.svg)](tests)

Pacote oficial e desacoplado desenvolvido para adicionar gestão financeira profissional e robusta ao **i-Educar 2.10**, atendendo escolas privadas, conveniadas e comunitárias.

O pacote se integra de forma **Plug-and-Play** ao i-Educar:
- **Zero Redundância Pedagógica**: Reutiliza 100% dos dados já cadastrados no i-Educar (Alunos, Matrículas, Turmas, Séries e Escolas) via queries em lote e cache em memória (`AlunoLookup`) eliminando problemas de N+1 queries.
- **Isolamento e Segurança de Dados**: Opera em schema PostgreSQL dedicado (`financeiro.*`), mantendo a integridade estrita do banco do i-Educar.
- **RBAC Estrito por Processo Financeiro**: Controle granular de acesso baseado no middleware `financeiro.can:{processo},{acao}` integrado aos menus e perfis do i-Educar.
- **Criptografia de Credenciais**: API Keys do Asaas cifradas com algoritmo AES-256-GCM (`encrypted`).
- **Design System Nativo do i-Educar**: 100% das telas no padrão visual oficial Azul i-Educar (`#47728f`) e Esmeralda (`#059669`), com tipografia do sistema (sem CDN externa em tempo de execução, garantindo conformidade com a LGPD e redes isoladas).

---

## 🎯 Principais Funcionalidades

1. **Responsáveis Financeiros e Rateio Proporcional**:
   - Vínculo de responsáveis com múltiplos alunos.
   - Suporte a rateio proporcional de custos (ex: 50%/50% com carnês e títulos independentes calculados via algoritmo *Largest Remainder*).
   - Gestão de crédito automático por pagamento a maior (`creditos_responsaveis`).

2. **Planos de Pagamento e Motor de Contratos**:
   - Tabela de preços por curso, série e ano letivo com congelamento de *snapshot* no contrato.
   - Geração atômica de carnês de mensalidades com controle de centavos.
   - Conexão nativa com a matrícula do i-Educar (`MatriculaCriada`, `MatriculaCancelada`) e tela dedicada "Matrículas sem Contrato".
   - Trava de duplicidade com índice único parcial por matrícula e ano letivo.

3. **Gateway de Pagamento Asaas v3 (Boletos Híbridos & Carnê Oficial)**:
   - Emissão de Boletos Híbridos com QR Code Pix dinâmico e Pix Copia-e-Cola.
   - Carnês oficiais do Asaas via endpoint `/installments/{id}/paymentBook`.
   - Webhook blindado com validação estrita via `hash_equals`, rate limiting e tabela de idempotência (`webhook_events`).
   - Sincronização em massa com controle de taxa de requisições.

4. **Caixa Ágil de Balcão e Recibos Digitais**:
   - Busca incremental rápida por aluno, responsável, CPF ou contrato (`api/titulos/buscar`).
   - Recálculo dinâmico de juros, multas e troco em tempo real (`api/titulos/{id}/calculo`).
   - Baixa multilíquida com proteção contra duplo envio via `idempotency_key`.
   - Recibos oficiais com autenticação criptográfica SHA-256 e endpoint público de validação (`/financeiro/verificar/{hash}`).

5. **Régua de Cobrança e Gestão de Inadimplência**:
   - Régua com canais plugáveis (`MailCanal`, `WhatsAppLinkCanal`, `AsaasNotificacaoCanal`).
   - Janela de tolerância retroativa e respeito estrito ao horário comercial (07h às 22h).
   - Painel de Inadimplência por faixas de atraso (Aging List: 1-30, 31-60, 61-90, >90 dias - PCLD).
   - Bloqueio consultivo de rematrícula de inadimplentes com respaldo no **Artigo 5º da Lei Federal nº 9.870/1999** via API `FinanceiroInadimplencia`.

6. **Renegociação de Dívidas e Acordos**:
   - Transação atômica com pessimistic locking (`lockForUpdate`) e validação anti-tampering por hash.
   - Concessão de descontos com teto, parcelamento e cancelamento de cobranças Asaas originais.
   - Emissão do Termo de Acordo e Confissão de Dívida.

7. **Contas a Pagar e Conciliação Bancária**:
   - Liquidação de despesas com atualização em tempo real de `contas_bancarias.saldo_atual`.
   - Extrato automatizado de lançamentos em `movimentacoes_conta`.
   - Suporte a estorno com recomposição de saldo bancário.

8. **Relatórios Fiscais, DRE e Exportações**:
   - **DRE Escolar**: Regime de Caixa e Competência com fórmulas contábeis estritas (separação de receita bruta, deduções e encargos).
   - **Fluxo de Caixa**: Visão detalhada de realizado vs previsto para os 12 meses do ano.
   - **Informe de IRPF**: Declaração anual com identificação e CPF do aluno.
   - **Quitação Anual**: Certidão oficial em cumprimento à **Lei Federal nº 12.007/2009** com código verificador SHA-256.
   - **Exportações Nativas em CSV**: Todos os relatórios possuem exportação direta com codificação UTF-8 (BOM).

9. **Painel de Processamentos e Pendências**:
   - Monitoramento de lotes assíncronos e divergências de webhooks com resolução em 1 clique.

---

## 🚀 Instalação e Configuração

### Requisitos Mínimos
- **PHP**: ≥ 8.3 (extensões: `pdo_pgsql`, `bcmath`, `curl`, `json`)
- **Laravel**: 12.x
- **Banco de Dados**: PostgreSQL 12+ (com schema `financeiro`)
- **i-Educar**: Versão 2.10

### Instalação via Composer

1. Adicione o repositório no `composer.json` do seu i-Educar:
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/douglas14031999/i-educar-financeiro-package.git"
    }
]
```

2. Execute o require:
```bash
composer require douglas14031999/i-educar-financeiro-package
```

3. Execute as migrações:
```bash
php artisan migrate
```

4. (Opcional) Publique as configurações e traduções:
```bash
php artisan vendor:publish --tag=financeiro-config
php artisan vendor:publish --tag=financeiro-translations
```

---

## ⚙️ Configuração do Gateway Asaas

1. Acesse o i-Educar com perfil administrativo financeiro.
2. Acesse **Financeiro > Configurações > Gateway**.
3. Informe a **API Key** e o **Webhook Token** do Asaas (Sandbox ou Produção) e clique em **Testar Conexão**.
4. Configure a URL do Webhook no painel do Asaas:
   - **URL**: `https://seu-ieducar.com.br/financeiro/webhooks/asaas`
   - **Eventos Recomendados**: `PAYMENT_RECEIVED`, `PAYMENT_CONFIRMED`, `PAYMENT_OVERDUE`, `PAYMENT_DELETED`, `PAYMENT_REFUNDED`, `PAYMENT_UPDATED`.

---

## ⏰ Rotinas Automatizadas (Laravel Scheduler)

O pacote registra nativamente todas as rotinas no agendador do Laravel. No `crontab` do servidor, mantenha apenas a chamada padrão do Laravel:

```bash
* * * * * cd /caminho/do/ieducar && php artisan schedule:run >> /dev/null 2>&1
```

**Rotinas executadas automaticamente:**
- `financeiro:processar-regua` (Diário às 08:00) — Processa réguas ativas.
- `financeiro:sincronizar-asaas` (A cada 1 hora) — Concilia títulos pendentes.
- `financeiro:atualizar-status` (Diário às 00:10) — Atualiza títulos vencidos.
- `financeiro:reprocessar-webhooks` (A cada 5 minutos) — Reprocessa webhooks pendentes.

---

## 🧪 Bateria de Testes Automatizados e Homologação

O pacote inclui uma suíte completa de testes de regressão, conformidade com o PRD e homologação E2E:

```bash
# Execução completa da suíte de testes (84 testes, 599 asserções)
php vendor/phpunit/phpunit/phpunit

# Testes de conformidade e integridade com o PRD
php vendor/phpunit/phpunit/phpunit tests/Feature/PrdComplianceTest.php

# Homologação ponta a ponta (Checklist TC-01 a TC-14 Sandbox Asaas)
php vendor/phpunit/phpunit/phpunit tests/Feature/HomologacaoE2ETest.php
```

Consulte o relatório oficial de homologação com evidências detalhadas em:  
👉 [docs/HOMOLOGACAO-EVIDENCIAS.md](docs/HOMOLOGACAO-EVIDENCIAS.md)

---

## 📄 Licença

Distribuído sob os termos da licença [GNU General Public License v2.0 (GPL-2.0)](LICENSE), em total conformidade com o ecossistema **i-Educar**.
