# FLUXO — Arquitetura de Navegação e Fluxos Operacionais do Módulo Financeiro

**Módulo Financeiro i-Educar**  
**Data:** 13/10/2026  
**Versão:** 2.0.0  

---

## 1. Visão Global da Navegação (Sub-Navbar Persistente)

A Sub-Navbar permite ao operador transitar de qualquer tela para qualquer outra do módulo financeiro em **1 clique**, sem depender do menu principal do i-Educar:

```mermaid
graph TD
    subgraph "Sub-Navbar Superior (Presente em 100% das telas)"
        N1["📊 Painel Geral"]
        N2["📑 Mensalidades"]
        N3["💵 Caixa / Balcão"]
        N4["👥 Responsáveis"]
        N5["📄 Contratos"]
        N6["🏷️ Planos"]
        N7["🤝 Acordos"]
        N8["📉 Contas a Pagar"]
        N9["📈 Relatórios"]
        N10["⚙️ Configurações"]
    end
```

---

## 2. Fluxo 1: Recebimento Presencial no Caixa (Baixa de Balcão)

Este é o fluxo mais frequente da secretaria (atendimento diário a pais e responsáveis):

```mermaid
sequenceDiagram
    autonumber
    actor Pai as Responsável do Aluno
    actor Secretaria as Atendente da Secretaria
    participant Sistema as Módulo Financeiro
    participant Banco as Banco de Dados
    participant PDF as Gerador de Recibo

    Pai->>Secretaria: Informa nome do aluno ou CPF para pagar
    Secretaria->>Sistema: Clica no botão "Caixa / Balcão"
    Sistema-->>Secretaria: Exibe tela com busca rápida de títulos abertos
    Secretaria->>Sistema: Digita nome do aluno/responsável e clica "Selecionar"
    Sistema-->>Secretaria: Carrega dados da parcela com juros/desconto calculados
    Secretaria->>Sistema: Confere valor recebido, seleciona forma (Dinheiro/Pix/Cartão) e clica "Confirmar Recebimento"
    Sistema->>Banco: Inicia transação com Lock Pessimista
    Banco-->>Sistema: Registra Pagamento, atualiza Saldo para R$ 0,00 e Título para "Liquidado"
    Sistema->>PDF: Renderiza Recibo Oficial com Hash de Segurança
    PDF-->>Secretaria: Abre recibo instantaneamente para impressão ou entrega
    Secretaria-->>Pai: Entrega recibo impresso
```

---

## 3. Fluxo 2: Cobrança e Notificação Ágil via WhatsApp

Envio de 2ª via, lembretes de vencimento ou cobrança amigável de mensalidades em atraso:

```mermaid
graph TD
    A["Secretaria visualiza Mensalidade na Tabela"] -->|Clica no botão WhatsApp| B["Sistema detecta número do celular do Responsável"]
    B -->|Se celular válido| C["Monta texto personalizado com Aluno, Parcela, Vencimento e Link Asaas"]
    C --> D["Abre WhatsApp Web / App (wa.me) diretamente com mensagem pronta"]
    B -->|Se celular não cadastrado| E["Abre alerta: 'Cadastre o celular na ficha do responsável'"]
```

---

## 4. Fluxo 3: Matrícula, Contrato e Emissão de Carnê Escolar

Fluxo completo desde o fechamento da anuidade até a entrega do carnê impresso:

```mermaid
graph TD
    M1["Aluno matriculado no i-Educar"] --> M2["Acessa 'Contratos por Matrícula'"]
    M2 --> M3["Clica em 'Novo Contrato'"]
    M3 --> M4["Seleciona Aluno, Responsável e Plano de Pagamento (ex: 12x R$ 850)"]
    M4 --> M5["Salva Contrato"]
    M5 --> M6{"Deseja gerar os Títulos agora?"}
    M6 -->|Sim (1 clique)| M7["Sistema gera as 12 parcelas automáticas"]
    M7 --> M8{"Como a família pagará?"}
    M8 -->|Online / Pix| M9["Dispara Emissão em Lote no Asaas"]
    M8 -->|Presencial / Carnê| M10["Clica em 'Imprimir Carnê do Contrato'"]
    M10 --> M11["Sistema gera PDF em A4 com 3 lâminas por folha e canhotos"]
    M11 --> M12["Entrega do Carnê físico ao responsável"]
```

---

## 5. Fluxo 4: Seleção em Lote na Listagem de Títulos

Ações em massa que eliminam trabalho repetitivo:

```mermaid
graph TD
    L1["Acessa Mensalidades e Títulos"] --> L2["Aplica filtros: Ano, Turma ou Status"]
    L2 --> L3["Marca Checkbox 'Selecionar Todos' ou seleciona títulos específicos"]
    L3 --> L4["Barra Flutuante de Ações em Lote aparece no rodapé"]
    L4 --> B1["Botão: Emitir no Asaas em Lote"]
    L4 --> B2["Botão: Sincronizar Status Asaas"]
    L4 --> B3["Botão: Imprimir Carnê dos Selecionados"]
```

---

## 6. Fluxo 5: Renegociação e Acordo de Dívidas

Recuperação de créditos de alunos inadimplentes:

```mermaid
graph TD
    R1["Identifica Responsável com débitos vencidos"] --> R2["Acessa 'Acordos e Renegociação'"]
    R2 --> R3["Etapa 1: Seleciona o Responsável devedor"]
    R3 --> R4["Sistema lista todas as parcelas vencidas com checkbox"]
    R4 --> R5["Etapa 2: Define desconto, entrada e número de parcelas do acordo"]
    R5 --> R6["Etapa 3: Clica em 'Simular Acordo'"]
    R6 --> R7["Confere valores e datas das novas parcelas"]
    R7 --> R8["Clica em 'Confirmar e Formalizar Acordo'"]
    R8 --> R9["Títulos antigos mudam para 'Renegociado'"]
    R8 --> R10["Novos títulos parcelados são criados"]
    R8 --> R11["Gera Termo de Confissão de Dívida em PDF para assinatura"]
```
