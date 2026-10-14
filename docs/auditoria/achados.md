# Relatório de Achados, Evidências e Situação

| Metadado | Detalhe |
|---|---|
| **Projeto** | i-Educar Financeiro Package |
| **Commit Base** | `188ad485db4ad02b85599e47b26e0c9466a46123` |
| **Data** | 2026-10-14 |
| **Classificação** | P0 (Crítico/Segurança/Integridade), P1 (Fluxo/Consistência), P2 (Usabilidade/Qualidade) |

---

## 1. Quadro Consolidado de Achados

| ID | Título do Achado | Prioridade | Classificação | Situação no Código Atual |
|---|---|---|---|---|
| **FIN-001** | Webhook aceita requisições sem token e aprende segredo externo | **P0** | Confirmado no código | **Vulnerável**: Linhas 37-43 do controller salvam no banco o token recebido de terceiros se não houver token configurado. |
| **FIN-002** | Estorno pode restaurar saldo superior à dívida original | **P0** | Confirmado no código | **Vulnerável**: Recalcula amortização sem subtrair crédito gerado; não bloqueia `$pagamento`; não trata crédito consumido. |
| **FIN-003** | Impressão de carnê cria cobranças externas via GET | **P0** | Confirmado no código | **Vulnerável**: `ContratoController::imprimirCarne()` chama `$gateway->criarInstallment()` em rota `GET`. |
| **FIN-004** | Impressão desrespeita seleção de títulos do contrato | **P1** | Confirmado no código | **Vulnerável**: `TituloController::imprimirCarne()` descarta `$ids` se pertencerem a 1 contrato e busca até 12 títulos arbitrários se vazio. |
| **FIN-005** | Regeneração de títulos diverge da criação inicial | **P1** | Confirmado no código | **Vulnerável**: `ContratoController::gerarTitulos()` recria parcelas com desconto 0.00 e sem rateio percentual. |
| **FIN-006** | Validação da matrícula contornada informando escola | **P1** | Confirmado no código | **Vulnerável**: `if (!$escolaId && $matriculaId)` salta a conferência de pertencimento ao aluno se `escola_id` estiver presente. |
| **FIN-007** | Planos > 12 parcelas geram mês 0 e omitem taxa de matrícula | **P1** | Confirmado no código | **Vulnerável**: `$i % 12` gera 0 para a parcela 24; `valor_matricula` não se agrega ao total do plano. |
| **FIN-008** | Rotas REST apontam para métodos inexistentes nos controllers | **P1** | Confirmado no código | **Vulnerável**: `Route::resource` registra `edit`/`update`/`destroy` em Contratos, `edit`/`update` em Planos, e `show` em Contas a Pagar. |
| **FIN-009** | Renegociação usa dados defasados e atribui ao primeiro aluno | **P0** | Confirmado no código | **Vulnerável**: `confirmar()` não relê títulos sob bloqueio; novos títulos assumem aluno/contrato/escola do primeiro item. |
| **FIN-010** | Estados financeiros têm elegibilidade divergente entre operações | **P0** | Confirmado no código | **Vulnerável**: `isLiquidado()` exclui renegociados e cancelados; baixas e emissões não têm matriz centralizada de transição de estado. |
| **FIN-011** | Recibo não identifica o pagamento recém-criado | **P1** | Confirmado no código | **Vulnerável**: `imprimirRecibo($id)` recebe id do título e pega o primeiro pagamento por data descrescente (baixa retroativa pega recibo errado). |
| **FIN-012** | Liquidação de despesa permite sobrescrever dados e não restringe tipo | **P1** | Confirmado no código | **Vulnerável**: `ContaPagarController::liquidar()` não valida se já foi pago, permitindo sobrescrita de data e conta bancária; não filtra `tipo='despesa'`. |
| **FIN-013** | Relatórios usam composição contábil divergente com dupla contagem | **P1** | Confirmado no código | **Vulnerável**: DRE soma `valor_pago + valor_multa + valor_juros` e deduz desconto, quando `valor_pago` já inclui encargos e desconto. |
| **FIN-014** | Prorrogação não é aplicada no `scopeVencidos()` | **P1** | Confirmado no código | **Vulnerável**: `Titulo::isVencido()` checa `data_prorrogacao`, mas `scopeVencidos()` consulta apenas `data_vencimento`. |
| **FIN-015** | Exclusão de responsável ignora títulos quitados, parciais e contratos | **P1** | Confirmado no código | **Vulnerável**: `destroy()` só checa `where('status', 'aberto')`, violando integridade referencial com histórico. |
| **FIN-016** | Botões de copiar anunciam sucesso sem aguardar clipboard | **P2** | Confirmado no código | **Vulnerável**: `navigator.clipboard.writeText` executado de forma síncrona com `alert()` imediato sem `.then()/.catch()`. |
| **FIN-017** | Régua de cobrança interpreta presença do campo como true | **P2** | Confirmado no código | **Vulnerável**: `$request->has('ativo')` grava verdadeiro mesmo quando o operador envia 0 ou falso. |
| **SEC-001** | Ausência de autorização granular e tenant multiescola no servidor | **P0** | Confirmado no código | **Vulnerável**: Os controllers não validam se o registro acessado pertence à escola em que o usuário está lotado. |

---

## 2. Detalhamento e Plano de Resolução por Achado

### FIN-001 — Webhook Asaas e Autenticação de Segredo
- **Arquivo:** `src/Http/Webhooks/AsaasWebhookController.php`
- **Causa Raiz:** Fallback indevido que gravava o cabeçalho recebido no banco de dados se `$expectedToken` estivesse vazio.
- **Correção:**
  1. Se não houver token configurado no banco nem em config, retornar imediatamente HTTP 401 Unauthorized e registrar log de alerta.
  2. Remover completamente o trecho de auto-aprendizagem de token por requisição externa.
  3. Rejeitar payloads malformados ou sem `event` e sem `payment.id`.
  4. Nunca expor segredos em logs de auditoria.

### FIN-002 — Integridade de Amortização e Estorno de Baixa
- **Arquivo:** `src/Services/BaixaTituloService.php`
- **Causa Raiz:** O estorno recalculava `amortizacao = (valor_pago + desconto) - encargos` sem subtrair `valor_credito_gerado`. Além disso, o `$pagamento` não era bloqueado com `lockForUpdate()`, permitindo requisições concorrentes de estorno.
- **Correção:**
  1. No momento da baixa, persistir a `valor_amortizado` real no registro de pagamento (ou deduzir `$pagamento->valor_credito_gerado` explicitamente no estorno).
  2. Bloquear tanto o `Titulo` quanto o `Pagamento` sob `lockForUpdate()` na mesma transação.
  3. Validar se o crédito gerado já foi consumido (`CreditoResponsavel::where('pagamento_id', $pagamento->id)->where('utilizado', true)->exists()`). Conforme alinhado no Socratic Gate, **bloquear o estorno com mensagem explícita** para resolução manual se o crédito já tiver sido utilizado.
  4. Garantir idempotência: rejeitar com erro se `$pagamento->estornado` já for verdadeiro.

### FIN-003 — Separação de Emissão de Carnê vs Impressão
- **Arquivo:** `src/Http/Controllers/ContratoController.php`
- **Causa Raiz:** O método `imprimirCarne($id)` executava mutação externa (POST /v3/installments) durante uma requisição HTTP GET de visualização.
- **Correção:**
  1. Manter `imprimirCarne` estritamente como leitura (renderização de carnê local ou streaming de PDF previamente emitido).
  2. Criar método explícito `emitirCarneAsaas($id)` acionado por requisição POST autenticada para a criação de carnê externo.
  3. Exibir na interface o estado do carnê: "Não emitido", "Emitido em DD/MM/AAAA", com botão de emissão separado do botão de impressão.

### FIN-004 — Preservação da Seleção de Títulos para Carnê
- **Arquivo:** `src/Http/Controllers/TituloController.php`
- **Causa Raiz:** Delegação ao `ContratoController::imprimirCarne($cid)` descartava o array de IDs selecionados pelo usuário, imprimindo todas as parcelas do contrato.
- **Correção:**
  1. Exigir seleção válida de IDs: se a lista estiver vazia, redirecionar com aviso sem buscar títulos aleatórios.
  2. Ao gerar a impressão, filtrar estritamente pelos IDs recebidos no formulário.
  3. Separar a impressão por responsável caso títulos de múltiplos responsáveis sejam selecionados.

### FIN-005 — Unificação do Serviço de Geração de Parcelas
- **Arquivo:** `src/Http/Controllers/ContratoController.php` e `src/Services/ContratoService.php`
- **Causa Raiz:** `gerarTitulos()` recriava títulos manualmente ignorando descontos e rateios de múltiplos responsáveis.
- **Correção:**
  1. Centralizar a regeneração de parcelas faltantes em método dedicado do `ContratoService` (`regenerarParcelasFaltantes($contrato)`).
  2. Preservar as condições contratuais gravadas (percentual de desconto e tabela de rateio do contrato).
  3. Executar sob transação e bloquear títulos existentes para evitar duplicação concorrente.

### FIN-006 — Blindagem de Vínculo de Matrícula e Escola
- **Arquivo:** `src/Services/ContratoService.php`
- **Causa Raiz:** A verificação de pertencimento da matrícula ao aluno estava encapsulada em `if (!$escolaId && $matriculaId)`.
- **Correção:**
  1. Sempre verificar se a matrícula existe, se pertence ao aluno informado e se está vinculada à escola do contrato.
  2. Se `escola_id` for informado e divergir da escola da matrícula, lançar `InvalidArgumentException`.
  3. Validar se o ano letivo da matrícula é compatível com o contrato.

### FIN-007 — Suporte a Planos > 12 Parcelas e Taxa de Matrícula
- **Arquivo:** `src/Http/Controllers/PlanoPagamentoController.php`
- **Causa Raiz:** Cálculo com `$i % 12` resultava em mês 0 para a 24ª parcela; taxa de matrícula não era somada ao total.
- **Correção:**
  1. Calcular competência de forma contínua usando `Carbon::create($anoBase, 1, 1)->addMonths($i - 1)`: parcelas de 1 a 12 ficam no ano N, de 13 a 24 avançam para o ano N+1 com mês de 1 a 12.
  2. Se a opção de taxa de matrícula for marcada como integrante do plano, somar o valor da matrícula ao `valor_total` gravado e validar que a soma de todos os itens do plano seja exatamente igual ao total contratual.

### FIN-008 — Restrição de Rotas a Métodos Existentes
- **Arquivo:** `routes/web.php`
- **Causa Raiz:** Uso irrestrito de `Route::resource()` criando endpoints fantasmas para ações não implementadas.
- **Correção:**
  1. Substituir `Route::resource('contratos')` por `Route::resource('contratos')->only(['index', 'create', 'store', 'show'])`.
  2. Substituir `Route::resource('planos')` por `Route::resource('planos')->only(['index', 'create', 'store', 'show', 'destroy'])`.
  3. Substituir `Route::resource('contas-pagar')` por `Route::resource('contas-pagar')->except(['show'])`.

### FIN-009 — Renegociação Transacional e Proteção Multialuno
- **Arquivo:** `src/Services/RenegociacaoService.php`
- **Causa Raiz:** Simulação reaproveitava títulos fora de transação sem lock; atribuição silenciosa ao primeiro aluno/escola.
- **Correção:**
  1. Dentro da transação de confirmação, reler e bloquear todos os títulos com `lockForUpdate()`.
  2. Verificar se o saldo atual de cada título é idêntico ao da simulação e se todos continuam elegíveis (`whereIn('status', ['aberto', 'parcial', 'vencido'])`).
  3. Validar que todos os títulos pertençam à mesma escola e ao mesmo responsável.
  4. Se envolver títulos de múltiplos alunos do mesmo responsável, permitir novação gerando títulos vinculados ao responsável, registrando claramente a relação de origem em cada parcela.
  5. Numeração com locking sequencial seguro contra concorrência.

### FIN-010 — Centralização de Elegibilidade e Estados Financeiros
- **Arquivo:** `src/Models/Titulo.php` e Controllers
- **Causa Raiz:** Inconsistência entre `isLiquidado()`, `isVencido()`, baixa e emissão no gateway.
- **Correção:**
  1. Definir explicitamente métodos no model `Titulo`:
     - `isElegivelBaixa()`: apenas `aberto`, `parcial`, `vencido`. Títulos `renegociado`, `cancelado` e `pago` são bloqueados.
     - `isElegivelEmissaoGateway()`: apenas `aberto`, `parcial`, `vencido` e sem liquidação.
     - `isElegivelRenegociacao()`: apenas `aberto`, `parcial`, `vencido` com saldo > 0.
  2. Aplicar essas travas no servidor antes de qualquer mutação.

### FIN-011 — Identificação Única do Recibo por Pagamento
- **Arquivo:** `src/Http/Controllers/TituloController.php` e `src/Http/Controllers/BaixaManualController.php`
- **Causa Raiz:** O recibo recebia apenas o ID do título e pegava o primeiro pagamento não estornado ordenado por data decrescente.
- **Correção:**
  1. Criar rota `financeiro.pagamentos.recibo` apontando para `PagamentoController@imprimirRecibo($pagamentoId)` ou `TituloController@imprimirReciboPagamento($pagamentoId)`.
  2. Redirecionar após a baixa utilizando o ID do `$pagamento` retornado.
  3. Na listagem de histórico do título, exibir o link para o comprovante específico de cada baixa realizada.

### FIN-012 — Liquidação Segura de Contas a Pagar
- **Arquivo:** `src/Http/Controllers/ContaPagarController.php`
- **Causa Raiz:** Consulta de `Lancamento` sem filtro de `tipo='despesa'` e sem proteção contra liquidação repetida.
- **Correção:**
  1. Buscar lançamentos restringindo estritamente `where('tipo', 'despesa')`.
  2. Bloquear com exceção/erro caso a despesa já esteja com `status === 'pago'`.
  3. Executar a liquidação dentro de transação com `lockForUpdate()`.

### FIN-013 — Reconciliação Contábil em Relatórios (DRE e Fluxo de Caixa)
- **Arquivo:** `src/Http/Controllers/RelatorioFinanceiroController.php`
- **Causa Raiz:** Somatória duplicada de encargos e descontos sobre `valor_pago`.
- **Correção:**
  1. Padronizar:
     - **Fluxo de Caixa (Visão de Caixa):** Soma estritamente `valor_pago` recebido em dinheiro/banco.
     - **DRE (Visão de Competência/Arrecadação Líquida):** Receita bruta = soma de `(valor_pago - valor_juros - valor_multa + valor_desconto_concedido)`. Encargos (juros/multa) = classificados como Receitas Financeiras separadas. Deduções = `valor_desconto_concedido`. Receita Líquida = Receita Bruta - Deduções + Receitas Financeiras, fechando exatamente com o valor total arrecadado.

### FIN-014 — Harmonização de Vencimento com Prorrogação
- **Arquivo:** `src/Models/Titulo.php`
- **Causa Raiz:** `scopeVencidos()` usava apenas `data_vencimento` em vez do vencimento efetivo.
- **Correção:**
  1. Atualizar o `scopeVencidos()` para usar `COALESCE(data_prorrogacao, data_vencimento) < CURRENT_DATE`.
  2. Garantir que títulos prorrogados para datas futuras não entrem na lista de vencidos nem componham o cálculo de inadimplência atual.

### FIN-015 — Validação de Exclusão de Responsável Financeiro
- **Arquivo:** `src/Http/Controllers/ResponsavelFinanceiroController.php`
- **Causa Raiz:** Checagem restrita a títulos em status `aberto`.
- **Correção:**
  1. Bloquear exclusão física se o responsável possuir qualquer título (aberto, parcial, vencido, pago ou renegociado) ou contrato vinculado.
  2. Disponibilizar a inativação lógica (`ativo = false`) como operação padrão para responsáveis com histórico financeiro.

### FIN-016 — Cópia de Linha Digitável e Pix com Feedback Assíncrono
- **Arquivo:** `resources/views/titulos/show.blade.php`
- **Causa Raiz:** Ausência de tratamento de Promises na Clipboard API.
- **Correção:**
  1. Implementar função JavaScript assíncrona com `navigator.clipboard.writeText().then(...).catch(...)`.
  2. Exibir toast visual acessível de sucesso ou erro, com fallback para seleção manual do input em caso de recusa de permissão do navegador.

### FIN-017 — Booleano de Ativação da Régua de Cobrança
- **Arquivo:** `src/Http/Controllers/ReguaCobrancaController.php`
- **Causa Raiz:** Uso de `$request->has('ativo')` em vez de converter valor booleano.
- **Correção:**
  1. Tratar o campo com `$request->boolean('ativo')`.
