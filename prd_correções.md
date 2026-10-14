Execute este PRD no checkout local do módulo financeiro. Comece registrando o commit e inventariando o repositório. Reconfirme cada achado, corrija os P0 primeiro e preserve as regras financeiras já válidas. Complete a inspeção dos arquivos pendentes, mapeie todos os botões às rotas e aos serviços, e valide os fluxos no i-Educar em ambiente de testes. Entregue código, testes, evidências visuais e relatório de cobertura. Não declare uma funcionalidade validada apenas por leitura de código, comentário ou existência de teste não executado. "PRD — Correção e validação integral do módulo financeiro para i-Educar

1. Objetivo e condição deste documento

Preparar o módulo para operação consistente, com:

Valores financeiros reconciliáveis.
Cobranças e pagamentos sem duplicidade.
Autorização por operação e por escola.
Botões coerentes com as operações executadas.
Navegação consistente entre cadastros, contratos, títulos, pagamentos e relatórios.
Integração recuperável diante de falhas do gateway.
Evidências automatizadas e visuais das correções.
Este documento é um PRD de saneamento e homologação. Não substitui a auditoria integral do checkout local.

O Antigravity deverá registrar o SHA do commit analisado antes de começar. As referências desta análise foram consultadas na branch main, sem fixação de commit; cada achado deve ser reconfirmado no checkout.

Classificação das evidências:

| Classificação | Significado |
|---|---|
| Confirmado no código | Comportamento diretamente identificável nos arquivos consultados. |
| Risco identificado | Há uma condição problemática, mas o efeito completo depende de outras camadas ou do ambiente. |
| Pendente | Exige inspeção adicional, execução, navegador ou integração externa. |

Prioridades:

| Prioridade | Critério |
|---|---|
| P0 | Segurança de pagamentos, corrupção de saldo ou risco de cobrança indevida. |
| P1 | Fluxo principal quebrado, inconsistência de dados ou operação financeira ambígua. |
| P2 | Usabilidade, desempenho, observabilidade e manutenção. |

2. Instruções de execução para o Antigravity

Executar o trabalho nesta ordem:

Inventariar todos os arquivos versionados e registrar o commit-base.
Ler as instruções locais e os documentos existentes.
Identificar a versão real do i-Educar hospedeiro e suas dependências.
Reproduzir os achados deste documento.
Implementar as correções P0.
Corrigir os fluxos P1.
Completar a inspeção dos arquivos e funcionalidades ainda não auditados.
Validar as telas em navegador.
Executar testes de integração, concorrência e regressão.
Entregar relatório de cobertura, mudanças e pendências.
Não considerar comentários como “corrigido”, “idempotente” ou “auditado” evidência suficiente. A conclusão deve se apoiar no comportamento implementado e em testes.

Não substituir o PRD original. Manter este documento como complemento e atualizar a documentação existente quando houver divergências.

3. Achados comprovados e requisitos de correção

FIN-001 — Webhook aceita requisições quando não há token configurado

Prioridade: P0.

Classificação: confirmado no código.

Em src/Http/Webhooks/AsaasWebhookController.php, handle(), quando não existe token esperado, o processamento continua. Se vier um token na requisição e existir configuração no banco, o controller grava esse token como configuração. Uma requisição externa pode, portanto, definir o segredo nessa condição. (raw.githubusercontent.com)

Requisitos:

Rejeitar eventos quando a autenticação não estiver configurada.
Remover a aprendizagem de token por requisição recebida.
Permitir cadastrar e alterar o segredo somente pelo fluxo administrativo autorizado.
Validar estrutura, tipo de evento e identificador do pagamento antes de persistir.
Não registrar segredos em logs.
Aceite:

Sem configuração: nenhum evento financeiro é aceito.
Token ausente ou incorreto: nenhum pagamento ou evento processável é criado.
Token enviado por terceiro nunca altera configurações.
Eventos autenticados e válidos seguem para processamento.
FIN-002 — Estorno pode restaurar saldo superior à dívida original

Prioridade: P0.

Classificação: confirmado no código.

Em BaixaTituloService, a baixa limita a amortização ao saldo e transforma o excedente em crédito. O estorno recalcula a amortização pelo pagamento bruto, sem descontar esse crédito. Exemplo derivado do código: dívida de R$ 100, pagamento de R$ 120, crédito de R$ 20; o estorno pode restaurar R$ 120. A verificação de estorno também usa o objeto recebido, sem reler o pagamento sob bloqueio. (raw.githubusercontent.com)

Requisitos:

Persistir a amortização efetivamente aplicada.
Estornar exatamente essa amortização.
Bloquear e reler pagamento e título dentro da transação.
Definir tratamento explícito para crédito já utilizado.
Garantir idempotência do estorno.
Aceite:

O cenário R$ 100/R$ 120 restaura saldo de R$ 100.
Duas solicitações simultâneas produzem um único estorno.
Crédito utilizado não permanece disponível após reversão inconsistente.
Pagamento parcial com desconto e encargos também é revertido corretamente.
FIN-003 — Impressão de carnê cria cobranças externas

Prioridade: P0.

Classificação: confirmado no código.

ContratoController::imprimirCarne() pode chamar criarInstallment() quando não encontra parcelamento existente. Assim, uma operação apresentada como impressão possui efeito financeiro externo. (raw.githubusercontent.com)

Requisitos:

Separar “Emitir cobranças” de “Imprimir carnê”.
Exigir operação autenticada de escrita para emissão.
Manter impressão e reimpressão sem criação de cobranças.
Apresentar prévia com responsáveis, parcelas e valores.
Tratar emissão anterior, timeout e retentativa sem duplicar cobranças.
Aceite:

Abrir, atualizar ou reimprimir um carnê não cria cobranças.
A emissão repetida retorna o resultado da operação existente.
Falha de impressão não dispara nova emissão.
FIN-004 — Impressão desrespeita a seleção de títulos

Prioridade: P1.

Classificação: confirmado no código.

TituloController::imprimirCarne() recebe IDs selecionados, mas, quando pertencem a um único contrato, delega a impressão ao controller do contrato sem preservar esses IDs. Sem seleção ou contrato, utiliza até 12 títulos elegíveis. (raw.githubusercontent.com)

Requisitos:

Preservar exatamente a seleção enviada.
Rejeitar seleção vazia ou inválida.
Informar títulos inelegíveis.
Separar documentos por responsável quando necessário.
Aceite:

Selecionar duas parcelas de um contrato com doze imprime apenas duas.
Seleção vazia não imprime títulos arbitrários.
Nenhum documento agrega dados de outro responsável indevidamente.
FIN-005 — Regeneração diverge da criação inicial do contrato

Prioridade: P1.

Classificação: confirmado no código.

ContratoController::gerarTitulos() cria títulos diretamente com valor integral do item, desconto zero e responsável principal. A identificação de existentes usa somente numero_parcela. O fluxo inicial utiliza um serviço distinto. (raw.githubusercontent.com)

Requisitos:

Centralizar geração inicial e complementar no mesmo serviço.
Utilizar condições financeiras preservadas do contrato.
Identificar parcelas por item, responsável e competência, conforme o modelo efetivo.
Proteger contra geração concorrente e persistência parcial.
Aceite:

Repetir a geração não altera o total contratado.
Recuperar parcela ausente respeita rateio e desconto.
Alterar o plano posteriormente não modifica contratos antigos implicitamente.
FIN-006 — Validação da matrícula pode ser contornada com escola informada

Prioridade: P1.

Classificação: confirmado no código.

Em ContratoService::criarContratoEParcelas(), a consulta da matrícula e a verificação de pertencimento ao aluno ficam dentro da condição de ausência de escola_id. Informar uma escola evita esse trecho de validação. (raw.githubusercontent.com)

Requisitos:

Sempre verificar matrícula, aluno, escola e ano letivo.
Verificar compatibilidade do plano com esses dados.
Validar responsável ativo e autorizado para o vínculo.
Rejeitar combinações inconsistentes antes de persistir.
Aceite:

Matrícula de outro aluno é rejeitada mesmo com escola preenchida.
Escola divergente da matrícula é rejeitada.
Falha não deixa contrato ou parcelas parciais.
FIN-007 — Planos com mais de 12 parcelas têm competências inconsistentes

Prioridade: P1.

Classificação: confirmado no código.

PlanoPagamentoController::store() aceita até 24 parcelas, mas calcula o mês por índice e módulo 12. A parcela 24 resulta em mês zero. A taxa de matrícula adicional também é criada separadamente sem acréscimo ao valor_total gravado nesse método. (raw.githubusercontent.com)

Requisitos:

Definir se o produto permite planos superiores a 12 meses.
Se permitir, representar corretamente mês e ano.
Definir se a matrícula integra ou acrescenta o total.
Fazer total do plano, soma dos itens e total contratual coincidirem segundo essa regra.
Aceite:

Testar 1, 12, 13 e 24 parcelas.
Nenhuma competência contém mês zero.
Não há regressão para o mesmo ano quando deveria ocorrer avanço.
A prévia explica claramente a taxa de matrícula.
FIN-008 — Rotas apontam para métodos não implementados

Prioridade: P1.

Classificação: confirmado nos arquivos consultados.

As rotas usam Route::resource() integralmente. Entretanto, ContratoController não implementa edit, update e destroy; PlanoPagamentoController não implementa edit e update; ContaPagarController não implementa show. (raw.githubusercontent.com)

Requisitos:

Restringir os recursos às operações suportadas.
Implementar operações adicionais somente quando fizerem parte do produto.
Revisar links, formulários e menus associados.
Aceite:

Toda rota registrada resolve para uma ação existente.
Nenhum botão aponta para ação ausente.
Operações não suportadas retornam resposta controlada.
FIN-009 — Renegociação usa dados obtidos antes da transação

Prioridade: P0.

Classificação: risco identificado no código.

RenegociacaoService::confirmar() simula antes de iniciar a transação e reutiliza os títulos obtidos. Também gera número por contagem e atribui os novos títulos ao aluno, contrato e escola do primeiro título, embora a seleção seja validada apenas por responsável. (raw.githubusercontent.com)

Requisitos:

Reler e bloquear títulos dentro da confirmação.
Revalidar saldo, elegibilidade e autorização.
Invalidar simulação quando o saldo tiver mudado.
Utilizar numeração segura sob concorrência.
Definir política para acordos envolvendo diferentes alunos, contratos e escolas.
Preservar a origem dos valores.
Coordenar cancelamento de cobranças antigas.
Aceite:

Pagamento ocorrido após a simulação exige nova confirmação.
Dois acordos simultâneos não consomem a mesma dívida.
Títulos de contextos incompatíveis são rejeitados.
Nenhuma dívida é atribuída silenciosamente ao primeiro aluno.
FIN-010 — Estados financeiros não têm elegibilidade uniforme

Prioridade: P0.

Classificação: confirmado no código, com efeito externo a validar.

Titulo::isLiquidado() retorna falso para cancelados e renegociados. A emissão individual usa esse método como bloqueio. A baixa bloqueia pagos e cancelados, mas não renegociados. Isso deixa condições de entrada diferentes entre operações. (raw.githubusercontent.com)

Requisitos:

Definir regras específicas para receber, emitir, renegociar, cancelar e notificar.
Aplicá-las no servidor e refletir seu resultado na interface.
Tratar recebimento externo tardio de dívida cancelada ou renegociada como reconciliação excepcional.
Aceite:

Título renegociado não recebe nova baixa ordinária.
Título cancelado não gera nova cobrança.
Ocultar botão não é a única proteção.
Eventos tardios não reativam dívidas automaticamente.
FIN-011 — Recibo não identifica o pagamento recém-criado

Prioridade: P1.

Classificação: confirmado no código.

A baixa redireciona para recibo por título. O recibo busca o primeiro pagamento não estornado, e a relação ordena por data de pagamento decrescente. Uma baixa retroativa pode, portanto, apresentar recibo de outro pagamento. (raw.githubusercontent.com)

Requisitos:

Identificar o recibo por pagamento.
Redirecionar usando o pagamento retornado pela baixa.
Disponibilizar recibo individual no histórico.
Identificar visualmente comprovantes estornados.
Aceite:

Duas baixas parciais possuem recibos distintos.
Uma baixa retroativa abre seu próprio comprovante.
O documento informa valor recebido, desconto, encargos e saldo resultante.
FIN-012 — Liquidação de despesa permite sobrescrever dados

Prioridade: P1.

Classificação: confirmado no código.

ContaPagarController::liquidar() atualiza status, data e conta bancária sem verificar liquidação anterior. Os métodos de acesso por ID consultam Lancamento sem restringir explicitamente o tipo a despesa. (raw.githubusercontent.com)

Requisitos:

Restringir a operação a despesas autorizadas.
Bloquear nova liquidação de lançamento pago.
Usar transação e controle de concorrência.
Criar reversão explícita para correções.
Aceite:

Repetição não altera conta ou data originais.
ID de receita não pode ser manipulado pela tela de despesas.
Edição concorrente com liquidação não modifica lançamento já pago.
FIN-013 — Relatórios usam composição monetária divergente

Prioridade: P1.

Classificação: inconsistência identificada; regra contábil precisa de definição.

O fluxo de caixa soma valor_pago. A DRE soma valor_pago + valor_juros + valor_multa e subtrai descontos. No serviço de baixa, a amortização já considera pagamento menos encargos. Essas fórmulas precisam ser reconciliadas para evitar dupla contagem. (raw.githubusercontent.com)

Requisitos:

Definir principal recebido, encargos, desconto e crédito excedente.
Distinguir visão de caixa e visão de competência.
Documentar o significado de cada relatório.
Definir critérios de informes e quitação sem inferir regras fiscais apenas pelo nome da tela.
Aceite:

Fixture com principal R$ 100, encargos R$ 3 e recebimento R$ 103 apresenta decomposição consistente.
Pagamentos estornados não aparecem como recebimento efetivo.
Crédito excedente não é reconhecido indevidamente como mensalidade.
FIN-014 — Prorrogação não é aplicada igualmente aos vencidos

Prioridade: P1.

Classificação: confirmado no código.

Titulo::isVencido() considera data_prorrogacao, mas scopeVencidos() consulta o vencimento original. Um título prorrogado pode aparecer como vencido na listagem e ser considerado não vencido em outro fluxo. (raw.githubusercontent.com)

Requisitos e aceite:

Centralizar a definição de vencimento efetivo.
Aplicar o mesmo critério em filtros, encargos, notificações e indicadores.
Título prorrogado para o futuro não deve integrar a inadimplência atual.
Cobrir virada de dia e datas limítrofes.
FIN-015 — Exclusão de responsável verifica apenas títulos abertos

Prioridade: P1.

Classificação: confirmado no código.

ResponsavelFinanceiroController::destroy() bloqueia somente quando encontra status aberto; não contempla explicitamente outros vínculos financeiros relevantes. (raw.githubusercontent.com)

Requisitos:

Definir inativação como operação preferencial para cadastro com histórico.
Preservar relações necessárias a documentos, créditos e pagamentos.
Verificar títulos parciais, vencidos e acordos ativos.
Evitar que alteração de vínculo reescreva a responsabilidade histórica.
Aceite:

Inativar responsável não quebra detalhes, recibos ou relatórios.
Obrigações existentes continuam identificáveis.
Exclusão física, quando permitida, exige ausência de vínculos impeditivos.
FIN-016 — Botões de copiar anunciam sucesso sem verificar resultado

Prioridade: P2.

Classificação: confirmado no código.

Em resources/views/titulos/show.blade.php, os botões de copiar chamam navigator.clipboard.writeText() e exibem sucesso imediatamente, sem aguardar a operação. (raw.githubusercontent.com)

Requisitos e aceite:

Aguardar a conclusão da cópia.
Exibir erro quando houver falha.
Oferecer seleção manual do conteúdo.
Validar navegação por teclado e feedback acessível.
FIN-017 — Configuração da régua interpreta presença como verdadeiro

Prioridade: P2.

Classificação: confirmado no código.

ReguaCobrancaController::atualizar() valida ativo como booleano, mas grava $request->has('ativo'). Um campo presente com valor falso é tratado como ativo. (raw.githubusercontent.com)

Requisitos e aceite:

Interpretar o valor booleano.
Validar canais permitidos.
Testar campo ausente, 0, 1, falso e verdadeiro.
A tela deve refletir o valor efetivamente salvo.
4. Segurança e isolamento — auditoria obrigatória

Prioridade: P0 para confirmação.

As rotas web consultadas usam middleware configurável e compartilhamento de menu. Nos controllers analisados não há verificações explícitas de autorização por operação. Isso exige inspeção do ambiente hospedeiro e de eventuais proteções globais; não comprova, sozinho, acesso indevido em produção. (raw.githubusercontent.com)

O Antigravity deverá definir uma matriz de permissões para:

| Capacidade | Operações |
|---|---|
| Consulta | Listagens, detalhes e relatórios autorizados |
| Cadastro | Responsáveis, vínculos e planos |
| Contratos | Criar, gerar parcelas e cancelar |
| Caixa | Baixa e emissão de recibo |
| Supervisão | Estorno, descontos excepcionais e renegociação |
| Despesas | Cadastro e liquidação |
| Administração | Gateway, segredos e régua |

Requisitos:

Aplicar autorização no servidor.
Restringir consultas às escolas permitidas.
Validar IDs recebidos em URL, corpo, seleção em lote e endpoints de busca.
Aplicar a mesma proteção a documentos e exportações.
Não usar visibilidade do menu como controle de acesso.
Mascarar segredos em formulários e respostas.
Aceite: um usuário autorizado na escola A não consulta nem altera registros exclusivos da escola B por manipulação de IDs.

5. Fluxos de telas e comportamento esperado dos botões

A tabela abaixo é a especificação de comportamento a homologar. Não representa execução visual já realizada.

| Origem | Ação | Destino/resultado esperado | Validação principal |
|---|---|---|---|
| Dashboard | Abrir indicador | Listagem correspondente | Mesma escola, período e filtros |
| Responsáveis | Novo/salvar | Detalhe do responsável | Documento e duplicidade |
| Responsável | Vincular aluno | Detalhe atualizado | Aluno autorizado e rateio válido |
| Responsável | Desvincular | Confirmação e atualização | Preservação do histórico |
| Planos | Criar | Prévia e detalhe | Soma, calendário e escola |
| Contratos | Novo | Seleção contextual | Matrícula, plano e responsável coerentes |
| Contrato | Salvar | Detalhe com parcelas | Operação atômica |
| Contrato | Gerar títulos | Resultado da geração | Sem duplicidade |
| Contrato | Cancelar | Prévia das consequências | Motivo e títulos afetados |
| Contrato | Imprimir | Documento existente | Sem emissão externa |
| Títulos | Filtrar/paginar | Lista filtrada | Preservação dos filtros |
| Títulos | Selecionar parcelas | Resumo da seleção | Quantidade e total corretos |
| Títulos | Emitir em lote | Acompanhamento | Resultado por título |
| Título | Emitir cobrança | Estado da emissão | Elegibilidade e idempotência |
| Título | Sincronizar | Estado reconciliado | Sem pagamento duplicado |
| Título | Baixa | Formulário contextual | Saldo atualizado |
| Baixa | Confirmar | Recibo do pagamento | Persistência única |
| Pagamento | Estornar | Confirmação com motivo | Saldo e crédito consistentes |
| Renegociação | Simular | Prévia sem gravação | Seleção íntegra |
| Renegociação | Confirmar | Acordo e títulos novos | Revalidação transacional |
| Despesas | Liquidar | Comprovante/detalhe | Uma única liquidação |
| Gateway | Testar conexão | Resultado explícito | Ambiente e segredo corretos |
| Régua | Executar | Resumo e acompanhamento | Não confundir fila com entrega |
| Relatórios | Abrir detalhe | Registros de origem | Totais reconciliáveis |

Para cada botão existente, registrar:

Tela e arquivo de origem.
Rótulo e identificador.
Condição de exibição.
Rota e método HTTP.
Permissão.
Payload.
Serviço chamado.
Efeito no banco e no gateway.
Comportamento de sucesso e erro.
Destino após conclusão.
Teste e evidência associados.
6. Requisitos transversais de interface

Preservar filtros ao retornar dos detalhes.
Preservar dados válidos após erro de formulário.
Exibir erro próximo ao campo correspondente.
Exigir confirmação contextual para cancelamento, estorno e renegociação.
Informar valores e registros afetados antes de operações financeiras.
Bloquear submissões repetidas enquanto houver processamento.
Manter proteção de idempotência no servidor.
Distinguir “solicitado”, “processando”, “concluído” e “falhou”.
Evitar mensagens técnicas brutas para o operador.
Oferecer estados vazios com orientação.
Garantir acesso por teclado, foco visível e rótulos compreensíveis.
Homologar layout com o tema real do i-Educar.
A paginação de títulos já preserva busca, status e competência no template consultado; esse comportamento deve ser mantido. (raw.githubusercontent.com)

7. Integrações e processamento assíncrono

O recebimento do webhook persiste o evento, tenta enfileirá-lo e ainda responde sucesso se o despacho falhar. Eventos existentes recebem resposta de duplicidade. Isso exige verificar como eventos pendentes são recuperados operacionalmente. (raw.githubusercontent.com)

Requisitos propostos:

Recuperar automaticamente eventos persistidos e não processados.
Separar idempotência de evento da idempotência do recebimento financeiro.
Reconciliar webhook, sincronização manual e tarefas agendadas.
Registrar tentativas, último erro e próxima tentativa.
Tratar resposta perdida após criação de cobrança.
Impedir mistura entre sandbox e produção.
Manter associação verificável entre cada cobrança e seu título.
Revalidar elegibilidade antes do envio de notificações.
Testar eventos duplicados e fora de ordem.
8. Instalação e compatibilidade

O pacote declara PHP >=8.3 e componentes Illuminate ^12.0; a compatibilidade deverá ser comprovada contra a instalação real do i-Educar. (raw.githubusercontent.com)

O Antigravity deverá validar:

Instalação limpa.
Atualização de instalação existente.
Descoberta do service provider.
Carregamento de rotas e views.
Migrations e seeders.
Criação e atualização dos menus.
Cache de configuração, rotas e views.
Agendador e workers.
Configuração do PostgreSQL e schemas.
Publicações de views antigas que sobrescrevam templates novos.
Recuperação após falha parcial de instalação.
Não executar testes financeiros em produção.

9. Banco de dados e preservação histórica

Requisitos propostos:

Definir restrições de unicidade coerentes com rateios e parcelas.
Garantir unicidade dos identificadores externos relevantes.
Verificar chaves estrangeiras e índices.
Usar cálculos monetários determinísticos.
Preservar condições do contrato independentemente de alterações futuras no plano.
Registrar autor, motivo e data de operações financeiras.
Manter histórico de reversões.
Evitar exclusão de registros necessários à reconciliação.
Criar diagnóstico dos dados existentes antes de aplicar correções retroativas.
A correção de dados deverá produzir:

Relatório de inconsistências.
Simulação da alteração.
Critérios de seleção.
Procedimento recuperável.
Reconciliação posterior.
10. Testes mínimos obrigatórios

| ID | Cenário | Resultado esperado |
|---|---|---|
| T01 | Webhook sem token configurado | Rejeitado, sem mutação |
| T02 | Token incorreto | Rejeitado |
| T03 | Mesmo evento repetido | Uma aplicação |
| T04 | Webhook e sincronização simultâneos | Um recebimento |
| T05 | Pagamento excedente e estorno | Saldo original correto |
| T06 | Dois estornos simultâneos | Uma reversão |
| T07 | Crédito excedente já utilizado | Tratamento explícito |
| T08 | Baixa retroativa | Recibo correto |
| T09 | Impressão de carnê | Nenhuma cobrança criada |
| T10 | Seleção de duas parcelas | Apenas duas impressas |
| T11 | Geração repetida | Nenhuma duplicidade |
| T12 | Parcela ausente com rateio | Recomposição correta |
| T13 | Matrícula de outro aluno | Rejeitada |
| T14 | Plano de 24 parcelas | Calendário válido |
| T15 | Pagamento após simulação | Acordo exige revalidação |
| T16 | Renegociações concorrentes | Sem dívida duplicada |
| T17 | Título cancelado/renegociado | Ações incompatíveis bloqueadas |
| T18 | Liquidação repetida de despesa | Sem sobrescrita |
| T19 | Título prorrogado | Consistência em todas as telas |
| T20 | Usuário de outra escola | Acesso negado |
| T21 | Régua com ativo=false | Regra desativada |
| T22 | Falha ao copiar Pix | Feedback verdadeiro |
| T23 | Gateway cria cobrança, resposta falha | Retentativa sem duplicação |
| T24 | Evento salvo, fila indisponível | Recuperação posterior |
| T25 | Relatórios após estorno | Totais reconciliados |
| T26 | Todas as rotas registradas | Ações existentes |

Os testes de concorrência devem usar conexões independentes e banco compatível com produção. Mocks isolados não demonstram comportamento de bloqueios no PostgreSQL.

11. Cobertura ainda pendente

Esta análise não encerra a inspeção de:

Todas as migrations e suas reversões.
Todos os modelos, observers e listeners.
Jobs de gateway, cancelamento e processamento de webhook.
Driver completo do Asaas.
Serviço completo da régua.
Testes existentes e pipeline.
Todas as views e scripts.
Integração efetiva com matrícula e rematrícula.
Impressão real dos documentos.
Desempenho com volume.
Acessibilidade e responsividade.
Proteções herdadas do i-Educar hospedeiro.
O Antigravity deverá classificar cada arquivo como revisado, gerado/terceiro, não aplicável com justificativa ou pendente. Arquivo não lido não deve constar como auditado.

12. Entregáveis

Produzir no repositório:

| Arquivo sugerido | Conteúdo |
|---|---|
| docs/auditoria/inventario.md | Arquivos, componentes e cobertura |
| docs/auditoria/achados.md | Evidências, severidade e situação |
| docs/auditoria/matriz-telas-acoes.csv | Todos os botões e destinos |
| docs/auditoria/matriz-permissoes.md | Ações por perfil e escola |
| docs/auditoria/regras-financeiras.md | Estados, fórmulas e invariantes |
| docs/auditoria/testes.md | Cenários, comandos e resultados |
| docs/auditoria/reconciliacao.md | Diagnóstico e correções de dados |
| docs/auditoria/homologacao.md | Evidências visuais e conclusão |

Para cada achado, registrar: reprodução, causa, arquivos alterados, teste de regressão e evidência de resolução.

13. Critérios de conclusão

A entrega só poderá ser marcada como concluída quando:

Todos os P0 estiverem corrigidos e testados.
Os P1 estiverem corrigidos ou explicitamente registrados como impedimentos.
Todas as rotas tiverem ações válidas.
Todos os botões estiverem inventariados e homologados.
Os testes de isolamento por escola estiverem aprovados.
Os cenários de concorrência não duplicarem efeitos financeiros.
Impressão não emitir cobranças.
Relatórios e registros de origem forem reconciliáveis.
Instalação e atualização forem verificadas.
Nenhum arquivo relevante permanecer sem classificação.
O relatório diferenciar claramente o que foi inspecionado, executado, aprovado e bloqueado."