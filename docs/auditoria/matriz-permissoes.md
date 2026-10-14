# Matriz de Permissões e Isolamento Multiescola

| Documento | Detalhe |
|---|---|
| **Auditoria** | Segurança, Controle de Acesso e Isolamento Multiescola (P0) |
| **Referência** | Seção 4 do PRD de Homologação |
| **Escopo** | Módulo Financeiro i-Educar (`douglas14031999/i-educar-financeiro-package`) |

---

## 1. Princípios Arquiteturais de Segurança

1. **Autorização Obrigatória no Servidor:** A visibilidade condicional de botões e links no Blade é apenas auxílio de usabilidade. Todas as rotas e endpoints de mutação e consulta validam a permissão do usuário no servidor antes de executar qualquer leitura ou escrita.
2. **Isolamento Multiescola por Tenant (`escola_id`):** Um usuário lotado na Escola A não pode consultar, emitir, baixar, estornar ou renegociar contratos ou títulos pertencentes exclusivamente à Escola B, mesmo manipulando IDs em parâmetros de URL, corpo JSON ou seleções em lote.
3. **Proteção Transacional e Segredos:** Segredos de gateway (chaves de API Asaas e tokens de webhook) são mascarados na interface e nunca gravados em logs de execução ou eventos de exceção.

---

## 2. Matriz de Capacidades por Perfil de Usuário

| Capacidade | Operações Abrangidas | Perfil Autorizado no i-Educar | Restrição de Escola |
|---|---|---|---|
| **Consulta** | Listagens, detalhes e relatórios (Dashboard, Títulos, Contratos, Planos, DRE, Fluxo de Caixa, IRPF, Quitação) | `Secretaria`, `Financeiro`, `Diretoria`, `Administrador` | Restrito às escolas vinculadas ao usuário logado na sessão ativa. Administrador geral pode consultar todas. |
| **Cadastro** | Cadastro e edição de Responsáveis Financeiros, vínculos de rateio com alunos e Planos de Cobrança | `Financeiro`, `Secretaria`, `Administrador` | O responsável é global ou da escola; planos e turmas são filtrados pela escola informada. |
| **Contratos** | Criação de contratos, geração de parcelas, emissão em lote no Asaas e cancelamento de contratos | `Financeiro`, `Administrador` | Estritamente restrito à escola da matrícula do aluno. Contratos de outra escola são rejeitados. |
| **Caixa** | Baixa manual de balcão (dinheiro, cheque, cartão presencial) e emissão de comprovantes/recibos | `Caixa / Balcão`, `Financeiro`, `Administrador` | A baixa só pode ser registrada para títulos da escola em que o operador do caixa está em exercício. |
| **Supervisão** | Estorno de baixas, concessão de descontos manuais excepcionais, sincronização manual de gateway e acordos de renegociação | `Supervisor Financeiro`, `Diretoria`, `Administrador` | Operação sensível com auditoria obrigatória (usuário, timestamp e motivo). Bloqueado para operador de balcão comum. |
| **Despesas** | Cadastro de contas a pagar, plano de contas, centro de custo e liquidação de pagamentos a fornecedores | `Financeiro`, `Administrador` | Contas vinculadas à escola da despesa ou sede. |
| **Administração** | Configuração de credenciais do Gateway (Asaas Sandbox/Produção), token de Webhook e parâmetros da Régua de Cobrança | `Administrador do Sistema` | Somente usuários com privilégio de superadministrador institucional. |

---

## 3. Matriz Detalhada de Rotas e Endpoints

| Rota / Endpoint | Método HTTP | Capacidade Requerida | Regra de Escola Aplicada |
|---|---|---|---|
| `financeiro.dashboard` | GET | `Consulta` | Filtra indicadores e arrecadação pela escola ativa |
| `financeiro.responsaveis.index` | GET | `Consulta` | Permite busca global ou por alunos da escola |
| `financeiro.responsaveis.store` | POST | `Cadastro` | Valida CPF único; registra usuário criador |
| `financeiro.responsaveis.update` | PUT | `Cadastro` | Valida unicidade; impede alteração se sem permissão |
| `financeiro.responsaveis.destroy` | DELETE | `Cadastro` | Rejeita se houver títulos em qualquer status ou contratos |
| `financeiro.responsaveis.vincular-aluno` | POST | `Cadastro` | Valida se o aluno pertence à escola autorizada e soma de rateio = 100% |
| `financeiro.planos.store` | POST | `Cadastro` | Valida `escola_id` com a lotação do usuário |
| `financeiro.contratos.store` | POST | `Contratos` | Valida se `matricula.ref_ref_cod_escola === request.escola_id` |
| `financeiro.contratos.gerar-titulos` | POST | `Contratos` | Valida titularidade da escola antes de gerar parcelas |
| `financeiro.contratos.cancelar` | POST | `Contratos` | Valida titularidade da escola; cancela parcelas futuras não pagas |
| `financeiro.contratos.imprimir-carne` | GET | `Consulta` | Apenas leitura; não emite cobranças |
| `financeiro.contratos.carne-asaas` | POST | `Contratos` | Emite carnê no Asaas; restrito a operadores financeiros da escola |
| `financeiro.titulos.show` | GET | `Consulta` | Bloqueia visualização se o título for de outra escola |
| `financeiro.titulos.emitir-asaas` | POST | `Contratos` | Bloqueia se título pertencer a outra escola |
| `financeiro.titulos.sincronizar-asaas` | POST | `Supervisao` | Consulta status no Asaas e reconcilia sem duplicidade |
| `financeiro.baixas.store` | POST | `Caixa` | Bloqueia baixa se operador não pertencer à escola do título |
| `financeiro.baixas.estornar` | POST | `Supervisao` | Exige perfil supervisor; valida lock; bloqueia se crédito consumido |
| `financeiro.renegociacao.simular` | POST | `Supervisao` | Valida títulos pertencentes à mesma escola e responsável |
| `financeiro.renegociacao.confirmar` | POST | `Supervisao` | Valida concorrência sob lock; bloqueia mistura de escolas |
| `financeiro.contas-pagar.store` | POST | `Despesas` | Associa despesa à escola permitida |
| `financeiro.contas-pagar.liquidar` | POST | `Despesas` | Bloqueia se despesa já estiver paga; valida escola |
| `financeiro.configuracoes.gateway.*` | ANY | `Administracao` | Apenas perfil Administrador; bloqueia outros |
| `financeiro.configuracoes.regua.*` | ANY | `Administracao` | Apenas perfil Administrador |
| `financeiro.webhook.asaas` | POST | `Webhook Externo` | Autenticado estritamente pelo token do Asaas; sem sessão web |

---

## 4. Validação de Aceite para Isolamento

- **Cenário de Teste:** Operador logado na Escola 1 envia requisição POST para `/financeiro/titulos/999/emitir-asaas` ou `/financeiro/baixas` onde o título 999 pertence à Escola 2.
- **Resultado Esperado:** HTTP 403 Forbidden com mensagem clara de autorização negada. Nenhum dado financeiro ou transação é modificado.
