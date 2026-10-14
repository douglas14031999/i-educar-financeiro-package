# Diagnóstico e Procedimento de Reconciliação Contábil da Base de Dados

> **Data:** 14/10/2026  
> **Módulo:** i-Educar Financeiro (`i-educar-financeiro-package`)  
> **Banco Alvo:** PostgreSQL (Schemas: `financeiro`, `pmieducar`, `cadastro`)  
> **Status de Execução:** Migrações e Scripts de Reconciliação Verificados em Ambiente Local

---

## 1. Contexto e Motivação

Durante a auditoria arquitetural e contábil do módulo financeiro (achados **FIN-002**, **FIN-010**, **FIN-013**), identificou-se que versões legadas permitiam:
1. Baixas com pagamentos excedentes gerando saldo negativo de título em vez de amortização de 100% e geração de crédito contábil ao responsável financeiro.
2. Estornos que restauravam o valor pago total acrescido de juros/multas, inflando indevidamente o saldo do título acima do valor nominal contratual original.
3. Liquidações repetidas de despesas em contas a pagar sobrescrevendo datas e contas bancárias de liquidações anteriores.
4. Falta de trilha de auditoria sobre parcelas amortizadas versus encargos moratórios.

Para sanar essas inconsistências estruturais e garantir conciliação contínua com extratos bancários e webhooks do gateway Asaas, foi introduzida a **Migration 15** e este procedimento de diagnóstico e saneamento.

---

## 2. Alterações Estruturais (Migration 15)

Arquivo: `database/migrations/2026_01_01_000015_add_amortizacao_and_creditos_responsaveis.php`

### 2.1 Colunas Adicionadas em `financeiro.pagamentos`
* `valor_amortizado numeric(14,2) default 0.00`: Representa a parcela exata do pagamento aplicada na redução do principal do título (excluindo juros, multas e valor excedente).
* `valor_credito_gerado numeric(14,2) default 0.00`: Valor pago a maior que o saldo devedor do título no balcão, convertido em haver para o responsável.

### 2.2 Nova Tabela: `financeiro.creditos_responsaveis`
Armazena créditos financeiros de responsáveis oriundos de pagamentos em excesso no balcão:
```sql
CREATE TABLE financeiro.creditos_responsaveis (
    id bigserial PRIMARY KEY,
    responsavel_financeiro_id bigint NOT NULL REFERENCES financeiro.responsaveis_financeiros(id) ON DELETE CASCADE,
    pagamento_id bigint REFERENCES financeiro.pagamentos(id) ON DELETE SET NULL,
    valor numeric(14,2) NOT NULL,
    origem varchar(50) DEFAULT 'excedente_baixa',
    descricao varchar(255),
    utilizado boolean DEFAULT false,
    cancelado boolean DEFAULT false,
    created_at timestamp,
    updated_at timestamp
);
CREATE INDEX creditos_responsaveis_resp_idx ON financeiro.creditos_responsaveis(responsavel_financeiro_id);
CREATE INDEX creditos_responsaveis_pag_idx ON financeiro.creditos_responsaveis(pagamento_id);
```

---

## 3. Scripts SQL de Diagnóstico da Base Legada

Execute as seguintes consultas para identificar títulos ou lançamentos com divergência histórica pré-migração:

### Diagnóstico D1: Títulos com Saldo Devedor Negativo
```sql
SELECT id, contrato_id, numero_parcela, valor_nominal, valor_pago, valor_saldo, status
FROM financeiro.titulos
WHERE valor_saldo < 0.00;
```
*Critério de conformidade:* Zero registros retornados.

### Diagnóstico D2: Divergência entre `valor_pago` do Título e Soma dos Pagamentos Válidos
```sql
SELECT 
    t.id AS titulo_id,
    t.valor_nominal,
    t.valor_pago AS valor_pago_registrado,
    COALESCE(SUM(p.valor_pago), 0.00) AS valor_pago_real,
    t.valor_saldo AS saldo_registrado,
    ROUND(t.valor_nominal - COALESCE(SUM(p.valor_amortizado), 0.00), 2) AS saldo_calculado
FROM financeiro.titulos t
LEFT JOIN financeiro.pagamentos p ON p.titulo_id = t.id AND p.estornado = false
GROUP BY t.id, t.valor_nominal, t.valor_pago, t.valor_saldo
HAVING ABS(t.valor_pago - COALESCE(SUM(p.valor_pago), 0.00)) > 0.01;
```

### Diagnóstico D3: Pagamentos com Amortização não Preenchida (`valor_amortizado = 0`)
```sql
SELECT id, titulo_id, valor_pago, valor_juros, valor_multa, valor_desconto_concedido, valor_amortizado
FROM financeiro.pagamentos
WHERE estornado = false AND valor_amortizado = 0.00 AND valor_pago > 0.00;
```

### Diagnóstico D4: Títulos com Status 'pago' mas Saldo > 0 ou Inverso
```sql
SELECT id, numero_parcela, status, valor_nominal, valor_pago, valor_saldo
FROM financeiro.titulos
WHERE (status = 'pago' AND valor_saldo > 0.01)
   OR (status IN ('aberto', 'vencido') AND valor_saldo <= 0.00);
```

---

## 4. Script de Saneamento e Reconciliação (Execução Segura em Transação)

Caso a base legada apresente divergências detectadas pelas consultas acima, execute o seguinte bloco de saneamento atômico:

```sql
BEGIN;

-- 1. Popula valor_amortizado para pagamentos legados não estornados onde o campo estava zerado
UPDATE financeiro.pagamentos p
SET valor_amortizado = GREATEST(
    0.00,
    ROUND((p.valor_pago + COALESCE(p.valor_desconto_concedido, 0.00)) 
          - (COALESCE(p.valor_juros, 0.00) + COALESCE(p.valor_multa, 0.00)) 
          - COALESCE(p.valor_credito_gerado, 0.00), 2)
)
WHERE p.estornado = false 
  AND p.valor_amortizado = 0.00 
  AND p.valor_pago > 0.00;

-- 2. Recalcula acumulados de pagamentos e encargos no título
WITH totais_pagamentos AS (
    SELECT 
        titulo_id,
        COALESCE(SUM(valor_pago), 0.00) AS total_pago,
        COALESCE(SUM(valor_amortizado), 0.00) AS total_amortizado,
        COALESCE(SUM(valor_juros), 0.00) AS total_juros,
        COALESCE(SUM(valor_multa), 0.00) AS total_multa,
        COALESCE(SUM(valor_desconto_concedido), 0.00) AS total_desconto
    FROM financeiro.pagamentos
    WHERE estornado = false
    GROUP BY titulo_id
)
UPDATE financeiro.titulos t
SET 
    valor_pago     = tp.total_pago,
    valor_juros    = tp.total_juros,
    valor_multa    = tp.total_multa,
    valor_desconto = tp.total_desconto,
    valor_saldo    = GREATEST(0.00, ROUND(t.valor_nominal - tp.total_amortizado, 2))
FROM totais_pagamentos tp
WHERE t.id = tp.titulo_id;

-- 3. Ajusta status de títulos de acordo com o saldo recalculado e data de vencimento
UPDATE financeiro.titulos
SET status = 'pago'
WHERE valor_saldo <= 0.00 AND status != 'cancelado';

UPDATE financeiro.titulos
SET status = 'parcial'
WHERE valor_saldo > 0.00 AND valor_pago > 0.00 AND status NOT IN ('renegociado', 'cancelado');

UPDATE financeiro.titulos
SET status = CASE 
    WHEN COALESCE(data_prorrogacao, data_vencimento) < CURRENT_DATE THEN 'vencido'
    ELSE 'aberto'
END
WHERE valor_saldo > 0.00 AND valor_pago = 0.00 AND status NOT IN ('renegociado', 'cancelado');

-- 4. Validação final pós-saneamento
DO $$
DECLARE
    v_inconsistencias integer;
BEGIN
    SELECT COUNT(*) INTO v_inconsistencias
    FROM financeiro.titulos
    WHERE valor_saldo < 0.00;

    IF v_inconsistencias > 0 THEN
        RAISE EXCEPTION 'Abortando migração: % títulos ainda possuem saldo negativo!', v_inconsistencias;
    END IF;
END $$;

COMMIT;
```

---

## 5. Procedimento de Rollback de Contingência

Caso seja necessário reverter a migração de compatibilidade 15 em ambiente de homologação:
```sql
BEGIN;
DROP TABLE IF EXISTS financeiro.creditos_responsaveis CASCADE;
ALTER TABLE financeiro.pagamentos DROP COLUMN IF EXISTS valor_amortizado;
ALTER TABLE financeiro.pagamentos DROP COLUMN IF EXISTS valor_credito_gerado;
DELETE FROM financeiro.migrations WHERE migration = '2026_01_01_000015_add_amortizacao_and_creditos_responsaveis';
COMMIT;
```
