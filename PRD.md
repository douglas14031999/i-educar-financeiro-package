# PRD - Módulo Financeiro para o i-Educar (Escolas Privadas)

| Metadado | Detalhe |
| :--- | :--- |
| **Projeto** | `i-educar-financeiro-package` |
| **Tipo de Arquitetura** | Pacote Modular Laravel (Composer Package) |
| **Alvo Core** | i-Educar 2.10 (PostgreSQL, PHP 7.4/8.1, Laravel Ecosystem) |
| **Status** | Aprovado após Socratic Gate \| Pronto para Implementação |
| **Responsável** | Agente `project-planner` & `backend-specialist` |

---

## 1. Visão Geral e Objetivos do Produto

### 1.1 Contexto
O **i-Educar** é uma plataforma consolidada de gestão escolar voltada majoritariamente para redes públicas. Para atender escolas privadas e comunitárias, o pacote `i-educar-financeiro-package` introduz uma camada nativa de **Gestão Financeira Escolar Completa**, acoplada diretamente aos dados pedagógicos existentes de alunos, turmas, cursos e matrículas, sem duplicá-los.

### 1.2 Objetivo Geral
Desenvolver um pacote Composer desacoplado (`i-educar-financeiro-package`) que injeta ao i-Educar 2.10 todos os recursos financeiros necessários para escolas privadas (mensalidades, contratos, emissão de boletos híbridos/Pix via Asaas, régua de cobrança automática por e-mail/WhatsApp, conciliação de pagamentos com idempotência, contas a pagar, plano de contas e relatórios gerenciais/fiscais).

> [!NOTE]
> **Decisão Estratégica (Socratic Gate):** O acesso a boletos, linhas digitáveis e chaves Pix será realizado por **envio direto e links de pagamento (E-mail / WhatsApp)** gerados via gateway. Não haverá portal autônomo de login para pais/responsáveis, simplificando o fluxo, eliminando gestão de credenciais e reduzindo custos operacionais da escola.

### 1.3 Princípios de Engenharia
1. **Zero Duplicação de Entidades:** Alunos (`pmieducar.aluno`), matrículas (`pmieducar.matricula`), turmas (`pmieducar.turma`), cursos (`pmieducar.curso`), séries (`pmieducar.serie`) e escolas (`pmieducar.escola`) são consumidos via Foreign Keys diretas.
2. **Isolamento em Schema PostgreSQL Dedicado:** Todas as tabelas do pacote residirão no schema `financeiro.*` (ex.: `financeiro.titulos`), garantindo separação física dos objetos e evitando colisões com updates do core do i-Educar.
3. **Precisão Financeira Estrita:** Valores monetários armazenados em centavos (`BIGINT`) ou `DECIMAL(14, 2)` com arredondamento bancário (`ROUND_HALF_EVEN`), sem ponto flutuante.
4. **Idempotência e Tolerância a Falhas:** Webhooks de gateways de pagamento e rotinas de conciliação utilizam chave única de idempotência, logs de auditoria e transações seguras.
5. **Gateway Prioritário:** **Asaas** como gateway principal no MVP (suporte nativo a Boleto híbrido com Pix embutido, Pix dinâmico instantâneo e Cartão de crédito).
6. **Política de Cancelamento de Matrícula:** Ao cancelar matrícula no i-Educar, cancelam-se automaticamente apenas parcelas futuras não vencidas, mantendo títulos já vencidos em cobrança ativa.
7. **Aderência ao Padrão do i-Educar:** Registro automático de menus e permissões através de migrations (`App\Menu` e `App\Process`), conforme padrão observado no core e nos pacotes comunitários.

---

## 2. Personas e Casos de Uso

| Persona | Papel | Principais Dores & Objetivos |
| :--- | :--- | :--- |
| **Diretor / Gestor Geral** | Estratégico | Visualizar DRE, fluxo de caixa projetado vs realizado, curva de inadimplência por turma/série e relatórios executivos. |
| **Tesoureiro / Analista Financeiro** | Operacional | Gerar mensalidades em lote, aplicar bolsas/descontos com aprovação, emitir cobranças no Asaas, dar baixa manual e conciliar retornos de webhook. |
| **Secretária Escolar** | Atendimento | Matricular aluno com emissão automática de contrato financeiro, emitir 2ª via de boleto/Pix em PDF ou reencaminhar por WhatsApp, lançar taxas avulsas. |
| **Responsável Financeiro** (Pais) | Cliente Final | Receber lembretes de cobrança via E-mail e WhatsApp com link direto do boleto/Pix (Asaas) e solicitar declaração de IRPF na secretaria. |

---

## 3. Arquitetura do Pacote Laravel

### 3.1 Estrutura de Diretórios
```
i-educar-financeiro-package/
├── composer.json                     # Metadados do pacote, PSR-4 e auto-discovery Laravel
├── config/
│   └── financeiro.php                # Configurações gerais, gateway Asaas, juros/multas, schema
├── database/
│   ├── migrations/                   # DDL do schema financeiro + menus/permissões
│   └── seeders/                      # Plano de contas padrão, formas de pagamento
├── src/
│   ├── FinanceiroServiceProvider.php # Boot de rotas, migrations, views e commands
│   ├── Commands/                     # Artisan: cobrança automática, conciliação, fechamento
│   ├── Models/                       # Eloquent models com table='financeiro.xyz'
│   │   ├── ResponsavelFinanceiro.php
│   │   ├── AlunoResponsavel.php
│   │   ├── PlanoPagamento.php
│   │   ├── PlanoItem.php
│   │   ├── Contrato.php
│   │   ├── Titulo.php
│   │   ├── Desconto.php
│   │   ├── Pagamento.php
│   │   ├── CobrancaGateway.php
│   │   ├── ContaBancaria.php
│   │   ├── PlanoConta.php
│   │   ├── CentroCusto.php
│   │   ├── Lancamento.php
│   │   ├── Renegociacao.php
│   │   └── WebhookEvent.php
│   ├── Services/                     # Lógica de negócio pura
│   │   ├── ContratoService.php       # Criação de contrato e geração de parcelas
│   │   ├── TituloService.php         # Cálculo de juros, multas, descontos e vencimentos
│   │   ├── BaixaTituloService.php    # Processamento de baixa integral/parcial/estorno
│   │   ├── RenegociacaoService.php   # Acordo de dívidas, unificação e reparcelamento
│   │   ├── ReguaCobrancaService.php  # Automação de réguas (D-7, D-3, D0, D+3, etc.)
│   │   └── ConciliacaoService.php    # Conciliação de extrato e webhooks
│   ├── Gateways/                     # Camada de abstração de cobrança
│   │   ├── Contracts/
│   │   │   └── GatewayInterface.php  # Contrato comum para qualquer gateway
│   │   ├── Drivers/
│   │   │   ├── AsaasGateway.php      # Driver Asaas (Prioridade MVP: Pix, Boleto Híbrido, Cartão)
│   │   │   ├── EfiGateway.php        # Driver Efí / Gerencianet
│   │   │   └── CnabGateway.php       # Driver CNAB 240/400 (bancos legados)
│   │   └── GatewayManager.php        # Fábrica de drivers (Manager pattern)
│   ├── Jobs/                         # Filas assíncronas
│   │   ├── GerarParcelasLoteJob.php
│   │   ├── EnviarNotificacaoCobrancaJob.php
│   │   ├── SincronizarCobrancaGatewayJob.php
│   │   └── ProcessarWebhookJob.php
│   ├── Events/
│   │   ├── TituloCriado.php
│   │   ├── TituloPago.php
│   │   ├── TituloCancelado.php
│   │   └── RenegociacaoEfetivada.php
│   ├── Listeners/
│   │   ├── CriarContratoAposMatricula.php   # Ouve evento nativo de matrícula do i-Educar
│   │   └── CancelarTitulosMatriculaCancelada.php # Cancela apenas parcelas futuras não vencidas
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php
│   │   │   ├── ResponsavelFinanceiroController.php
│   │   │   ├── PlanoPagamentoController.php
│   │   │   ├── ContratoController.php
│   │   │   ├── TituloController.php
│   │   │   ├── BaixaManualController.php
│   │   │   ├── RenegociacaoController.php
│   │   │   ├── ContaPagarController.php
│   │   │   ├── GatewayConfigController.php
│   │   │   └── RelatorioFinanceiroController.php
│   │   ├── Requests/
│   │   └── Webhooks/
│   │       └── AsaasWebhookController.php
│   └── Reports/                      # Geradores de relatórios PDF/Excel
│       ├── InadimplenciaReport.php
│       ├── FluxoCaixaReport.php
│       ├── DREReport.php
│       └── DemonstrativoIRPFReport.php
├── resources/
│   ├── views/                        # Telas Blade integradas ao layout padrão do i-Educar
│   │   ├── dashboard/
│   │   ├── responsaveis/
│   │   ├── contratos/
│   │   ├── titulos/
│   │   ├── baixas/
│   │   ├── renegociacao/
│   │   ├── contas-pagar/
│   │   └── relatorios/
│   └── lang/                         # Traduções pt-BR
├── routes/
│   ├── web.php                       # Rotas da interface administrativa no i-Educar
│   └── api.php                       # Webhooks dos gateways
└── tests/
    ├── Unit/
    └── Feature/
```

---

## 4. Modelagem de Dados e Esquema de Banco (Schema `financeiro`)

### 4.1 Criação do Schema Dedicado
```sql
CREATE SCHEMA IF NOT EXISTS financeiro;
```

### 4.2 Dicionário de Tabelas

#### 4.2.1 `financeiro.responsaveis_financeiros`
Responsável civil e legal pelo pagamento.
- `id`: BIGSERIAL PRIMARY KEY
- `pessoa_id`: BIGINT NULL FK (`cadastro.pessoa(id)`)
- `nome`: VARCHAR(255) NOT NULL
- `cpf_cnpj`: VARCHAR(20) NOT NULL UNIQUE
- `rg_ie`: VARCHAR(30) NULL
- `email`: VARCHAR(255) NOT NULL
- `telefone_celular`: VARCHAR(20) NOT NULL
- `telefone_fixo`: VARCHAR(20) NULL
- `cep`: VARCHAR(10) NOT NULL
- `logradouro`: VARCHAR(255) NOT NULL
- `numero`: VARCHAR(20) NOT NULL
- `complemento`: VARCHAR(100) NULL
- `bairro`: VARCHAR(100) NOT NULL
- `cidade`: VARCHAR(100) NOT NULL
- `estado`: VARCHAR(2) NOT NULL
- `observacoes`: TEXT NULL
- `ativo`: BOOLEAN DEFAULT TRUE
- `created_at`, `updated_at`, `deleted_at`

#### 4.2.2 `financeiro.aluno_responsavel`
Relacionamento N:N entre aluno e responsáveis, com divisão percentual (ex: 50% mãe e 50% pai).
- `id`: BIGSERIAL PRIMARY KEY
- `aluno_id`: INTEGER NOT NULL FK (`pmieducar.aluno(cod_aluno)`)
- `responsavel_id`: BIGINT NOT NULL FK (`financeiro.responsaveis_financeiros(id)`)
- `parentesco`: VARCHAR(50) NOT NULL
- `percentual_rateio`: DECIMAL(5,2) DEFAULT 100.00
- `responsavel_principal`: BOOLEAN DEFAULT TRUE
- `created_at`, `updated_at`, `deleted_at`

#### 4.2.3 `financeiro.planos` e `financeiro.plano_itens`
Modelos de cobrança (ex: "Fundamental II - Anual em 12x").
- `financeiro.planos`:
  - `id`: BIGSERIAL PRIMARY KEY
  - `escola_id`: INTEGER NOT NULL FK (`pmieducar.escola(cod_escola)`)
  - `ano_letivo`: INTEGER NOT NULL
  - `curso_id`: INTEGER NULL FK (`pmieducar.curso(cod_curso)`)
  - `serie_id`: INTEGER NULL FK (`pmieducar.serie(cod_serie)`)
  - `nome`: VARCHAR(150) NOT NULL
  - `descricao`: TEXT NULL
  - `valor_total`: DECIMAL(14,2) NOT NULL
  - `qtd_parcelas`: INTEGER NOT NULL DEFAULT 12
  - `ativo`: BOOLEAN DEFAULT TRUE
  - `created_at`, `updated_at`, `deleted_at`
- `financeiro.plano_itens`:
  - `id`: BIGSERIAL PRIMARY KEY
  - `plano_id`: BIGINT NOT NULL FK (`financeiro.planos(id)`)
  - `tipo_item`: VARCHAR(50) NOT NULL -- matricula, mensalidade, material, taxa_evento
  - `descricao`: VARCHAR(150) NOT NULL
  - `numero_parcela`: INTEGER NOT NULL
  - `valor`: DECIMAL(14,2) NOT NULL
  - `mes_competencia`: INTEGER NOT NULL
  - `dia_vencimento_padrao`: INTEGER NOT NULL DEFAULT 10
  - `created_at`, `updated_at`

#### 4.2.4 `financeiro.contratos`
Contrato financeiro formal vinculado à matrícula.
- `id`: BIGSERIAL PRIMARY KEY
- `numero_contrato`: VARCHAR(50) NOT NULL UNIQUE
- `matricula_id`: INTEGER NOT NULL FK (`pmieducar.matricula(cod_matricula)`)
- `aluno_id`: INTEGER NOT NULL FK (`pmieducar.aluno(cod_aluno)`)
- `responsavel_financeiro_id`: BIGINT NOT NULL FK (`financeiro.responsaveis_financeiros(id)`)
- `plano_id`: BIGINT NULL FK (`financeiro.planos(id)`)
- `ano_letivo`: INTEGER NOT NULL
- `data_inicio`: DATE NOT NULL
- `data_termino`: DATE NOT NULL
- `valor_total_bruto`: DECIMAL(14,2) NOT NULL
- `valor_total_descontos`: DECIMAL(14,2) DEFAULT 0.00
- `valor_total_liquido`: DECIMAL(14,2) NOT NULL
- `dia_vencimento`: INTEGER NOT NULL DEFAULT 10
- `status`: VARCHAR(30) NOT NULL DEFAULT 'ativo' -- rascunho, ativo, suspenso, cancelado, transferido, quitado
- `created_at`, `updated_at`, `deleted_at`

#### 4.2.5 `financeiro.titulos` (Parcelas e Cobranças)
- `id`: BIGSERIAL PRIMARY KEY
- `uuid`: UUID NOT NULL UNIQUE
- `contrato_id`: BIGINT NULL FK (`financeiro.contratos(id)`)
- `aluno_id`: INTEGER NOT NULL FK (`pmieducar.aluno(cod_aluno)`)
- `responsavel_financeiro_id`: BIGINT NOT NULL FK (`financeiro.responsaveis_financeiros(id)`)
- `escola_id`: INTEGER NOT NULL FK (`pmieducar.escola(cod_escola)`)
- `tipo`: VARCHAR(40) NOT NULL -- mensalidade, matricula, material, taxa_avulsa, renegociacao
- `numero_parcela`: INTEGER NOT NULL DEFAULT 1
- `total_parcelas`: INTEGER NOT NULL DEFAULT 1
- `competencia`: VARCHAR(7) NOT NULL -- Formato 'YYYY-MM'
- `data_vencimento`: DATE NOT NULL
- `data_prorrogacao`: DATE NULL
- `valor_nominal`: DECIMAL(14,2) NOT NULL
- `valor_desconto`: DECIMAL(14,2) DEFAULT 0.00
- `valor_multa`: DECIMAL(14,2) DEFAULT 0.00
- `valor_juros`: DECIMAL(14,2) DEFAULT 0.00
- `valor_pago`: DECIMAL(14,2) DEFAULT 0.00
- `valor_saldo`: DECIMAL(14,2) NOT NULL
- `status`: VARCHAR(30) NOT NULL DEFAULT 'aberto' -- aberto, pago, parcial, vencido, cancelado, renegociado, estornado
- `observacoes`: TEXT NULL
- `created_at`, `updated_at`, `deleted_at`

#### 4.2.6 `financeiro.pagamentos` (Baixas e Liquidações)
- `id`: BIGSERIAL PRIMARY KEY
- `titulo_id`: BIGINT NOT NULL FK (`financeiro.titulos(id)`)
- `forma_pagamento`: VARCHAR(40) NOT NULL -- pix, boleto, cartao_credito, dinheiro, transferencia
- `data_pagamento`: DATE NOT NULL
- `data_compensacao`: DATE NULL
- `valor_pago`: DECIMAL(14,2) NOT NULL
- `valor_juros`: DECIMAL(14,2) DEFAULT 0.00
- `valor_multa`: DECIMAL(14,2) DEFAULT 0.00
- `valor_desconto_concedido`: DECIMAL(14,2) DEFAULT 0.00
- `origem_baixa`: VARCHAR(30) NOT NULL -- manual, webhook_asaas, cnab
- `usuario_id`: INTEGER NULL
- `estornado`: BOOLEAN DEFAULT FALSE
- `motivo_estorno`: TEXT NULL
- `data_estorno`: TIMESTAMP NULL
- `created_at`, `updated_at`

#### 4.2.7 `financeiro.cobrancas_gateway`
- `id`: BIGSERIAL PRIMARY KEY
- `titulo_id`: BIGINT NOT NULL FK (`financeiro.titulos(id)`)
- `gateway`: VARCHAR(50) NOT NULL DEFAULT 'asaas'
- `gateway_charge_id`: VARCHAR(150) NOT NULL UNIQUE
- `status_externo`: VARCHAR(50) NOT NULL
- `linha_digitavel`: VARCHAR(100) NULL
- `codigo_barras`: VARCHAR(100) NULL
- `link_boleto_pdf`: VARCHAR(500) NULL
- `link_pagamento`: VARCHAR(500) NULL
- `pix_copia_cola`: TEXT NULL
- `pix_qr_code_base64`: TEXT NULL
- `payload_requisicao`: JSONB NULL
- `payload_resposta`: JSONB NULL
- `created_at`, `updated_at`

#### 4.2.8 `financeiro.webhook_events` (Idempotência)
- `id`: BIGSERIAL PRIMARY KEY
- `gateway`: VARCHAR(50) NOT NULL DEFAULT 'asaas'
- `event_id`: VARCHAR(150) NOT NULL UNIQUE
- `event_type`: VARCHAR(100) NOT NULL
- `payload`: JSONB NOT NULL
- `processado`: BOOLEAN DEFAULT FALSE
- `processado_em`: TIMESTAMP NULL
- `erro`: TEXT NULL
- `created_at`: TIMESTAMP

#### 4.2.9 Contabilidade Básica e Contas a Pagar
- `financeiro.contas_bancarias`, `financeiro.plano_contas`, `financeiro.centros_custo`, `financeiro.lancamentos` e `financeiro.renegociacoes`.

---

## 5. Integração com o Core do i-Educar 2.10

### 5.1 Registro de Menus e Permissões
1. Injeção de registros na tabela `menus` referenciando pais lógicos:
   - Menu Raiz: **Financeiro** (`order: 10`, `process: 999950`).
   - Submenus: Cadastros, Contratos & Planos, Cobrança (Asaas/Boleto/Pix), Baixa Manual, Contas a Pagar, Inadimplência & Relatórios, Configurações.
2. Atribuição de permissões em `menu_tipo_usuario` para Administradores, Tesouraria e Direção.
3. View composer mantendo o item ativo no menu superior e barra lateral do i-Educar.

### 5.2 Event Listeners do Ciclo de Vida Pedagógico
- `MatriculaCriada`: Prepara ou gera o contrato financeiro em rascunho/ativo vinculado à matrícula do aluno.
- `MatriculaCancelada`: **Política aprovada:** cancela imediatamente apenas as parcelas futuras com vencimento posterior à data de cancelamento; parcelas vencidas e não pagas permanecem registradas para cobrança administrativa.

---

## 6. Funcionalidades Detalhadas

### 6.1 Integração Asaas
- Geração de cobranças unificadas (Boleto com Pix QR Code acoplado + link para cartão de crédito).
- Retorno em tempo real via Webhook (`PAYMENT_RECEIVED`, `PAYMENT_CONFIRMED`, `PAYMENT_OVERDUE`).
- Criação e atualização de clientes no Asaas associados ao CPF do Responsável Financeiro.

### 6.2 Régua de Cobrança Automatizada (E-mail / WhatsApp)
- Executada via cron diário (`financeiro:processar-regua`):
  - **D-7 e D-3:** Lembrete amigável com botão direto para Pix copia-e-cola e boleto em PDF.
  - **D0:** Lembrete de vencimento no dia.
  - **D+3 e D+10:** Notificação de atraso com valor atualizado com juros e multa.

### 6.3 Relatórios e Obrigações Legais
- **Informe de Rendimentos para Imposto de Renda (IRPF):** Emissão em PDF consolidando os pagamentos do ano-calendário por responsável e aluno.
- **Declaração Anual de Quitação de Débitos (Lei 12.007/2009):** Certidão automática para alunos sem pendências.
- **Relatório de Inadimplência:** Painel por faixa de atraso (1-30, 31-60, 61-90, >90 dias) filtrável por escola, turma e série.
- **DRE e Fluxo de Caixa Realizado vs Projetado.**
