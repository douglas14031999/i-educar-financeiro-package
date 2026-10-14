# PRD — Módulo Financeiro i-Educar: Experiência de Uso e Produtividade

**Documento de Requisitos de Produto (PRD)**  
**Versão:** 2.0.0  
**Data:** 13/10/2026  
**Status:** Aprovado para Implementação  
**Autor:** Antigravity AI Engineering (Pair Programming)  

---

## 1. Visão Geral e Propósito

O **Módulo Financeiro do i-Educar** é o núcleo de gestão de cobranças, recebimentos, despesas e contratos das instituições escolares. O objetivo deste projeto de aprimoramento é transformar a usabilidade do sistema, tornando-o **extremamente intuitivo, ágil e focado na rotina diária da secretaria e tesouraria escolar**.

### 1.1 Objetivos de Negócio e Impacto
- **Reduzir o tempo de fila na secretaria:** Acelerar a baixa manual de mensalidades de 4 minutos para menos de 30 segundos por atendimento.
- **Ampliar a arrecadação e diminuir a inadimplência:** Disponibilizar envio instantâneo de links de pagamento e Pix via WhatsApp com um único clique.
- **Flexibilizar meios de pagamento offline e online:** Permitir tanto a cobrança automatizada via Asaas (Pix/Boleto) quanto a emissão de **Carnês Escolares impressos** com canhoto destacável para famílias sem acesso digital.
- **Eliminar fricção de navegação:** Permitir que o operador transite entre qualquer setor do Financeiro com 1 clique através de um submenu padronizado e alinhado aos processos nativos do i-Educar.

---

## 2. Personas e Cenários de Uso

### 2.1 Maria — Atendente de Secretaria / Caixa Escolar
- **Cenário:** Recebe dezenas de pais nos primeiros 10 dias do mês para pagamento presencial em dinheiro, cartão ou Pix.
- **Necessidade:** Localizar rapidamente a parcela do aluno, conferir encargos ou descontos de pontualidade, registrar a baixa e imprimir o recibo imediatamente sem ter que navegar por menus complexos.

### 2.2 Carlos — Gestor Financeiro / Tesoureiro
- **Cenário:** Precisa acompanhar a taxa de inadimplência, emitir cobranças em lote para o início do ano letivo e gerar carnês impressos para entregar aos responsáveis no ato da matrícula.
- **Necessidade:** Selecionar múltiplos títulos por turma/ano e disparar geração em lote de boletos Asaas ou carnê em PDF formatado para impressão em papel A4.

### 2.3 Responsável Financeiro (Pais e Mães)
- **Cenário:** Precisa de facilidade para pagar, recebendo a 2ª via pelo WhatsApp ou o carnê físico para pagamento na rede bancária/lotérica.

---

## 3. Requisitos Funcionais (FR)

### FR-01: Submenu Superior Persistente no Padrão i-Educar
- Deve existir uma barra de navegação secundária (Sub-Navbar) no topo de todas as páginas do Módulo Financeiro.
- A categorização deve seguir estritamente a hierarquia e os nomes de processos já estabelecidos no i-Educar:
  1. **Painel Geral** (`ProcessosFinanceiro::MENU_DASHBOARD` - 999951)
  2. **Mensalidades e Títulos** (`ProcessosFinanceiro::MENU_TITULOS` - 999955)
  3. **Caixa / Baixa de Balcão** (`ProcessosFinanceiro::MENU_BAIXA_MANUAL` - 999956)
  4. **Responsáveis Financeiros** (`ProcessosFinanceiro::MENU_RESPONSAVEIS` - 999952)
  5. **Contratos por Matrícula** (`ProcessosFinanceiro::MENU_CONTRATOS` - 999954)
  6. **Planos de Pagamento** (`ProcessosFinanceiro::MENU_PLANOS` - 999953)
  7. **Acordos e Renegociação** (`ProcessosFinanceiro::MENU_RENEGOCIACAO` - 999957)
  8. **Contas a Pagar** (`ProcessosFinanceiro::MENU_CONTAS_PAGAR` - 999958)
  9. **Central de Relatórios** (`ProcessosFinanceiro::MENU_RELATORIOS` - 999959)
  10. **Configurações** (`ProcessosFinanceiro::MENU_GATEWAY_CONFIG` - 999960)
- O submenu deve destacar visualmente a tela ativa e respeitar a paleta padrão do i-Educar (Azul Petróleo `#47728f`, cinzas suaves e sem tons roxos/violetas).

### FR-02: Tríade de Ações Rápidas Prioritárias
O sistema deve destacar visualmente, tanto no Dashboard quanto nos cabeçalhos das telas operacionais, as 3 ações centrais:
1. **Baixa no Caixa (Balcão):** Botão verde em destaque para abertura do caixa de recebimento.
2. **Cobrança por WhatsApp:** Botão verde WhatsApp com link direto formatado (`wa.me`) contendo identificação do aluno, valor, vencimento e link Pix/fatura.
3. **Novo Contrato / Matrícula:** Botão azul institucional para vincular o aluno recém-matriculado ao seu plano de anuidade e gerar suas parcelas.

### FR-03: Seleção em Lote e Operações em Massa
- Na listagem de Títulos/Mensalidades, adicionar caixa de seleção (`checkbox`) para:
  - Selecionar todos os títulos da página ou seleção individual.
  - Exibir barra flutuante de ações em lote:
    - **"Emitir Selecionados no Asaas"** (gera Boletos/Pix em lote).
    - **"Sincronizar Status com Asaas"**.
    - **"Imprimir Carnê dos Selecionados"**.

### FR-04: Emissão e Impressão de Carnê Escolar
- Criação de uma rota e visualização dedicada para impressão de **Carnê de Pagamento**:
  - Layout otimizado para impressão (folha A4 dividida em lâminas de carnê, normalmente 3 parcelas por folha com linha de corte tracejada).
  - Cada lâmina contém:
    - **Canhoto da Escola / Recibo do Pagador:** Número da parcela, vencimento, valor nominal, código do título e data de quitação.
    - **Ficha de Compensação / Boleto / QR Code Pix:** Código de barras ou linha digitável (se emitido no Asaas), QR Code Pix e instruções de pagamento (juros após vencimento, desconto pontualidade).
    - Dados do Aluno, Turma, Matrícula e Responsável Legal.

### FR-05: Navegação Cruzada Inteligente (Cross-Navigation)
- **Da Ficha do Responsável (`responsaveis.show`):**
  - Botão *"Criar Contrato Financeiro"* (já preenchendo o responsável).
  - Botão *"Ver Mensalidades deste Responsável"*.
  - Botão *"Renegociar Débitos"* (se houver parcelas vencidas).
- **Do Detalhe do Título (`titulos.show`):**
  - Botão *"Renegociar Débito"* se o título estiver vencido.
- **Do Contrato (`contratos.show`):**
  - Botão *"Ver Títulos deste Contrato"*.
  - Botão *"Imprimir Carnê Completo do Contrato"*.

---

## 4. Requisitos Não Funcionais (NFR)

1. **Aderência Visual Estrita:** O visual deve seguir as diretrizes do ecossistema i-Educar:
   - Fonte padrão: `Inter` ou `Open Sans`.
   - Paleta de cores: Azul institucional i-Educar (`#006699`), Azul Petróleo (`#47728f`), Verde esmeralda de sucesso (`#059669`) e Vermelho alerta (`#dc2626`).
   - Proibição de cores violetas/roxas.
2. **Impressão sem Perdas (Print Styling):** O carnê e os recibos devem conter CSS `@media print` garantindo margens exatas, corte limpo e quebra de página automática a cada 3 lâminas.
3. **Idempotência e Segurança:** Ações em lote e liquidações devem manter travas transacionais (`DB::transaction`) e bloqueio pessimista (`lockForUpdate`), impedindo duplicidade de baixa financeira.

---

## 5. Critérios de Aceite

- [ ] A Sub-Navbar aparece em todas as telas com os 10 itens exatamente nomeados conforme a estrutura do i-Educar.
- [ ] A tela de listagem de mensalidades possui checkboxes funcionais com botão "Ações em Lote".
- [ ] Ao clicar em "Imprimir Carnê", o sistema renderiza uma página de impressão com lâminas e canhotos individuais para as parcelas selecionadas ou para o contrato.
- [ ] O fluxo de "Baixa de Balcão" permite localizar o título instantaneamente e gera o recibo com 1 clique.
- [ ] As 3 ações principais (Caixa, WhatsApp, Contrato) estão disponíveis e operacionais em pontos estratégicos da navegação.
