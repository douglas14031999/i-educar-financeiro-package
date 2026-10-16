# FLUXO — Diagramas e Fluxos Operacionais de Pagamentos e Contratos

**Módulo Financeiro i-Educar**  
**Versão:** 3.1.0  
**Data:** 15/10/2026  

---

## 1. Visão Geral: O Checkout Escolar na Criação de Contrato

```mermaid
graph TD
    A["Atendente preenche 'Novo Contrato' (Aluno, Matrícula, Ano)"] --> B["Etapa de Pagamento: Seleciona Modalidade"]
    
    B --> M1["💵 1. Dinheiro à Vista"]
    B --> M2["💳 2. Cartão Presencial (Maquininha POS)"]
    B --> M3["🌐 3. Cartão Online (Link Asaas até 12x)"]
    B --> M4["⚡ 4. PIX Instantâneo (Validade 24h)"]
    B --> M5["📄 5. Boleto Bancário (1x)"]
    B --> M6["📚 6. Carnê Escolar (2x a 12x)"]

    M1 --> R1["Baixa Imediata no Caixa -> Recibo PDF na tela"]
    M2 --> R2["Coleta Bandeira + NSU -> Baixa Imediata e Recibo"]
    M3 --> R3["Gera Link Asaas (5 dias úteis) -> Disparo WhatsApp"]
    M4 --> R4["Gera QR Code dinâmico 24h -> Tela com Polling em tempo real"]
    M5 --> R5["Gera Boleto Asaas -> PDF do Boleto"]
    M6 --> R6["Gera Installment Asaas -> PDF do Carnê Completo"]
```

---

## 2. Fluxo 1: Pagamento à Vista em Dinheiro (Balcão)

```mermaid
sequenceDiagram
    autonumber
    actor Pai as Responsável Financeiro
    actor Atendente as Secretaria / Atendente
    participant Sistema as i-Educar Financeiro
    participant DB as Banco de Dados (PostgreSQL)

    Pai->>Atendente: Solicita matrícula e entrega o valor em dinheiro
    Atendente->>Sistema: Cria Contrato selecionando opção "Dinheiro (À Vista)"
    Sistema->>DB: Inicia transação: Cria Contrato e Título único
    Sistema->>DB: Executa BaixaTituloService (registra pagamento e zera saldo)
    Sistema->>DB: Marca Contrato como "QUITADO"
    Sistema-->>Atendente: Abre tela de confirmação e modal de impressão do Recibo
    Atendente-->>Pai: Entrega Recibo Oficial emitido e assinado
```

---

## 3. Fluxo 2: Cartão Presencial na Maquininha (Balcão / POS)

```mermaid
sequenceDiagram
    autonumber
    actor Pai as Responsável Financeiro
    actor Atendente as Secretaria / Atendente
    participant Maquininha as Maquininha POS da Escola
    participant Sistema as i-Educar Financeiro
    participant DB as Banco de Dados

    Pai->>Atendente: Apresenta cartão físico (crédito/débito) para pagamento no balcão
    Atendente->>Maquininha: Digita o valor e passa o cartão do responsável
    Maquininha-->>Atendente: Transação APROVADA (Emite comprovante impresso com NSU)
    Atendente->>Sistema: Seleciona "Cartão Presencial", informa Bandeira, NSU e parcelas
    Sistema->>DB: Registra baixa imediata do título vinculado ao NSU
    Sistema->>DB: Marca Contrato como "QUITADO"
    Sistema-->>Atendente: Exibe tela de sucesso e botão para imprimir Recibo Escolar
    Atendente-->>Pai: Entrega Recibo Escolar e via do cliente da maquininha
```

---

## 4. Fluxo 3: Cartão de Crédito Parcelado via Link Asaas (até 12x)

```mermaid
sequenceDiagram
    autonumber
    actor Pai as Responsável Financeiro
    actor Atendente as Secretaria
    participant Sistema as i-Educar Financeiro
    participant Asaas as API Asaas v3
    participant Webhook as Webhook Listener

    Atendente->>Sistema: Cria Contrato escolhendo "Cartão de Crédito (Link Asaas)"
    Sistema->>Asaas: POST /v3/paymentLinks (maxInstallmentCount: 12, dueDateLimitDays: 7)
    Asaas-->>Sistema: Retorna URL do Link de Pagamento (Validade de 5 dias úteis)
    Sistema->>Sistema: Salva Contrato com status "AGUARDANDO_PAGAMENTO"
    Sistema-->>Atendente: Exibe tela com QR Code do Link e botão "Enviar WhatsApp"
    Atendente->>Pai: Dispara link via WhatsApp para o celular do pai
    
    alt Pagamento Realizado no Prazo
        Pai->>Asaas: Acessa o link e paga em até 12x no cartão
        Asaas->>Webhook: Dispara evento PAYMENT_CONFIRMED
        Webhook->>Sistema: ProcessarWebhookJob baixa o título e atualiza Contrato para "QUITADO"
    else Link Expirou sem Pagamento
        Sistema->>Sistema: Contrato entra na Régua de Cobrança (Lembretes amigáveis no WhatsApp)
    end
```

---

## 5. Fluxo 4: PIX Instantâneo com QR Code em Tempo Real (24h)

```mermaid
sequenceDiagram
    autonumber
    actor Pai as Responsável Financeiro
    actor Atendente as Secretaria
    participant Sistema as i-Educar Financeiro
    participant Asaas as API Asaas v3
    participant Polling as Tela do Operador (Polling / Stream)

    Atendente->>Sistema: Cria Contrato com opção "PIX (À Vista)"
    Sistema->>Asaas: POST /v3/payments (billingType: PIX, validade 24h)
    Sistema->>Asaas: GET /v3/payments/{id}/pixQrCode
    Asaas-->>Sistema: Retorna encodedImage (QR Code) e copia-e-cola
    Sistema->>Sistema: Contrato salvo como "AGUARDANDO_PAGAMENTO"
    Sistema-->>Polling: Renderiza QR Code gigante na tela do balcão
    Polling->>Sistema: Inicia verificação periódica (/status-stream a cada 3s)
    
    Pai->>Asaas: Escaneia o QR Code pelo aplicativo do seu banco
    Asaas-->>Sistema: Webhook dispara PAYMENT_RECEIVED
    Sistema->>Sistema: Título baixado e Contrato alterado para "QUITADO"
    Polling->>Sistema: Consulta detecta status "QUITADO"
    Polling-->>Atendente: Alerta sonoro / Visual verde: "PIX RECEBIDO COM SUCESSO!"
```

---

## 6. Fluxo 5: Boleto Bancário (1x À Vista)

```mermaid
sequenceDiagram
    autonumber
    actor Pai as Responsável
    actor Atendente as Secretaria
    participant Sistema as i-Educar Financeiro
    participant Asaas as API Asaas v3
    participant Webhook as Webhook Asaas

    Atendente->>Sistema: Cria Contrato com opção "Boleto Bancário (1x)"
    Sistema->>Asaas: POST /v3/payments (billingType: BOLETO)
    Asaas-->>Sistema: Retorna bankSlipUrl e linha digitável
    Sistema->>Sistema: Salva Contrato como "AGUARDANDO_PAGAMENTO"
    Sistema-->>Atendente: Disponibiliza download do PDF e envio por WhatsApp
    Atendente-->>Pai: Imprime boleto ou envia no WhatsApp
    
    Note over Pai,Asaas: Pai efetua o pagamento na rede bancária / lotérica
    Asaas->>Webhook: Notifica compensação bancária (PAYMENT_RECEIVED)
    Webhook->>Sistema: BaixaTituloService liquida título e atualiza Contrato para "QUITADO"
```

---

## 7. Fluxo 6: Carnê Escolar Parcelado (2x a 12x via Asaas)

```mermaid
sequenceDiagram
    autonumber
    actor Pai as Responsável
    actor Atendente as Secretaria
    participant Sistema as i-Educar Financeiro
    participant Asaas as API Asaas v3
    participant Webhook as Webhook Listener

    Atendente->>Sistema: Cria Contrato selecionando "Carnê Escolar (ex: 12x)"
    Sistema->>Asaas: POST /v3/installments (12 parcelas, periodicidade mensal)
    Asaas-->>Sistema: Retorna installment_id e lista de cobranças geradas
    Sistema->>Sistema: Vincula cada cobrança ao respectivo Título
    Sistema->>Sistema: Contrato definido como "ATIVO" (aluno regularmente matriculado)
    Sistema->>Asaas: GET /v3/installments/{id}/paymentBook (PDF do Carnê)
    Sistema-->>Atendente: Entrega PDF completo do carnê para impressão imediata
    Atendente-->>Pai: Entrega carnê escolar impresso com todas as parcelas
    
    loop Todo mês (Parcelas 1 a 11)
        Pai->>Asaas: Paga a mensalidade do mês via carnê
        Asaas->>Webhook: PAYMENT_RECEIVED
        Webhook->>Sistema: Dá baixa na parcela do mês (Contrato permanece ATIVO)
    end
    
    Note over Pai,Webhook: Ao pagar a 12ª (última) parcela
    Pai->>Asaas: Paga última parcela do carnê
    Asaas->>Webhook: PAYMENT_RECEIVED
    Webhook->>Sistema: Dá baixa na 12ª parcela
    Sistema->>Sistema: Detecta saldo devedor = 0 e atualiza Contrato para "QUITADO"
```

---

## 8. Diagrama de Transições da Máquina de Estados

```mermaid
stateDiagram-v2
    [*] --> AguardandoPagamento: Criação via Cartão Online, Pix 24h ou Boleto 1x
    [*] --> Quitado: Criação à Vista em Dinheiro ou Cartão Presencial (POS)
    [*] --> Ativo: Criação via Carnê Parcelado (12x)
    
    AguardandoPagamento --> Quitado: Webhook confirma pagamento integral
    AguardandoPagamento --> Inadimplente: Prazo expirado (Direcionado para Régua de Cobrança)
    
    Ativo --> Inadimplente: Parcela do carnê vence sem pagamento
    Inadimplente --> Ativo: Regularização da parcela vencida
    Ativo --> Quitado: Todas as parcelas do carnê pagas
    
    Ativo --> Cancelado: Rescisão contratual / Transferência escolar
    Inadimplente --> Cancelado: Rescisão contratual
```
