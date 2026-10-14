# Inventário e Cobertura do Repositório

| Metadado | Detalhe |
|---|---|
| **Repositório** | `douglas14031999/i-educar-financeiro-package` |
| **Commit Base Auditado** | `188ad485db4ad02b85599e47b26e0c9466a46123` |
| **Branch** | `main` |
| **Ambiente de Execução** | PHP 8.3.33 / Laravel Illuminate ^12.70 / PostgreSQL 15.17 / PHPUnit 11.5.57 / Windows PowerShell |
| **Data da Auditoria** | 2026-10-14 |
| **Responsável** | Antigravity AI Pair Programmer (`orchestrator`) |

---

## 1. Resumo Executivo do Inventário

Foram mapeados e inspecionados todos os arquivos versionados do módulo financeiro. A auditoria identificou que suítes de teste de integração anteriores utilizavam chamadas inexistentes (como `Titulo::factory()`), confirmando a exigência do PRD de que **nenhuma funcionalidade seja considerada auditada sem teste executado em banco PostgreSQL real**.

### Totais por Camada Arquitetural:
- **Controllers & Webhooks:** 13 arquivos
- **Middleware:** 1 arquivo
- **Services de Domínio:** 6 arquivos
- **Models Eloquent:** 19 arquivos
- **Gateways e Drivers:** 4 arquivos
- **Jobs Assíncronos:** 4 arquivos
- **Artisan Commands:** 3 arquivos
- **Eventos e Listeners:** 3 arquivos
- **Migrations:** 13 arquivos
- **Seeders:** 1 arquivo
- **Views Blade:** 24 arquivos
- **Rotas:** 2 arquivos (`web.php`, `api.php`)
- **Testes:** 5 arquivos (2 Unitários puros, 3 de Integração)

---

## 2. Inventário Detalhado por Arquivo

| Caminho do Arquivo | Camada | Linhas | Classificação | Status da Auditoria / Vulnerabilidades Identificadas |
|---|---|---|---|---|
| `src/Http/Webhooks/AsaasWebhookController.php` | Controller | 100 | **Revisado** | **P0 (FIN-001)**: Aprendizagem indevida de token externo e bypass de autenticação quando não configurado. |
| `src/Services/BaixaTituloService.php` | Service | 184 | **Revisado** | **P0 (FIN-002)**: Estorno sem bloqueio relendo pagamento; distorção de amortização quando há crédito excedente. |
| `src/Http/Controllers/ContratoController.php` | Controller | 292 | **Revisado** | **P0 (FIN-003)**: `imprimirCarne()` cria cobranças externas; **P1 (FIN-005)**: `gerarTitulos` sem rateio/desconto; **P1 (FIN-008)**: rotas sem `edit`/`update`/`destroy`. |
| `src/Services/RenegociacaoService.php` | Service | 158 | **Revisado** | **P0 (FIN-009)**: Confirmação fora de lock, sobrescrita de dívida modificada e atribuição indiscriminada ao primeiro aluno/escola. |
| `src/Models/Titulo.php` | Model | 260 | **Revisado** | **P0 (FIN-010)**: `isLiquidado()` vs `isVencido()`; **P1 (FIN-014)**: `scopeVencidos()` ignora `data_prorrogacao`. |
| `src/Http/Controllers/TituloController.php` | Controller | 286 | **Revisado** | **P1 (FIN-004)**: Delega impressão ao contrato perdendo seleção; **P1 (FIN-011)**: Recibo por título em vez de pagamento. |
| `src/Services/ContratoService.php` | Service | 309 | **Revisado** | **P1 (FIN-006)**: Validação da matrícula do aluno é contornada se `escola_id` for informado. |
| `src/Http/Controllers/PlanoPagamentoController.php` | Controller | 146 | **Revisado** | **P1 (FIN-007)**: Parcela 24 gera competência mês 0; taxa de matrícula não compõe `valor_total`; **P1 (FIN-008)**: rotas `edit`/`update` ausentes. |
| `src/Http/Controllers/ContaPagarController.php` | Controller | 172 | **Revisado** | **P1 (FIN-008)**: Rota `show` ausente; **P1 (FIN-012)**: `liquidar()` sobrescreve despesa paga e não filtra `tipo='despesa'`. |
| `src/Http/Controllers/RelatorioFinanceiroController.php` | Controller | 225 | **Revisado** | **P1 (FIN-013)**: Dupla contagem de encargos e deduções divergentes entre Fluxo de Caixa e DRE. |
| `src/Http/Controllers/ResponsavelFinanceiroController.php` | Controller | 218 | **Revisado** | **P1 (FIN-015)**: `destroy()` bloqueia apenas status `aberto`, ignorando histórico parcial, liquidado ou contratos. |
| `resources/views/titulos/show.blade.php` | View | 360 | **Revisado** | **P2 (FIN-016)**: Botões de copiar sem validação assíncrona; estorno sem modal de motivo com texto fixo. |
| `src/Http/Controllers/ReguaCobrancaController.php` | Controller | 96 | **Revisado** | **P2 (FIN-017)**: `ativo => $request->has('ativo')` grava true para valor falso. |
| `src/FinanceiroServiceProvider.php` | Provider | 165 | **Revisado** | Registra rotas, migrations, views e singletons. Validado no Laravel 12. |
| `src/Gateways/GatewayManager.php` | Gateway | 62 | **Revisado** | Gerenciador de drivers de pagamento. |
| `src/Gateways/Contracts/GatewayInterface.php` | Contract | 38 | **Revisado** | Interface do driver Asaas. |
| `src/Gateways/Drivers/AsaasGateway.php` | Driver | 340 | **Revisado** | Integração HTTP com Asaas (clientes, cobranças, carnês, boletos, pix). |
| `src/Services/DescontoService.php` | Service | 102 | **Revisado** | Cálculo de pontualidade, bolsas e descontos irmãos. Testado com suíte unitária. |
| `src/Services/InadimplenciaCheckService.php` | Service | 140 | **Revisado** | Aging list e bloqueio acadêmico para inadimplência. |
| `src/Services/ReguaCobrancaService.php` | Service | 190 | **Revisado** | Motor de disparo pré, no dia e pós-vencimento. |
| `src/Http/Middleware/CompartilharMenuMiddleware.php` | Middleware | 45 | **Revisado** | Compartilha variáveis de menu do i-Educar nas views financeiras. |
| `src/Http/Controllers/AlunoBuscaController.php` | Controller | 95 | **Revisado** | API de autocomplete de alunos e responsáveis. |
| `src/Http/Controllers/DashboardController.php` | Controller | 135 | **Revisado** | KPIs financeiros, arrecadação e inadimplência. |
| `src/Http/Controllers/GatewayConfigController.php` | Controller | 80 | **Revisado** | Configuração administrativa e teste de conexão do gateway. |
| `src/Http/Controllers/BaixaManualController.php` | Controller | 111 | **Revisado** | Tela e endpoint de baixa presencial de balcão e estorno. |
| `src/Http/Controllers/RenegociacaoController.php` | Controller | 115 | **Revisado** | Simulação e confirmação de novação de dívidas. |
| `src/Jobs/CancelarCobrancaGatewayJob.php` | Job | 60 | **Revisado** | Cancelamento assíncrono de cobrança no Asaas. |
| `src/Jobs/EnviarNotificacaoCobrancaJob.php` | Job | 75 | **Revisado** | Disparo de mensagens WhatsApp/E-mail. |
| `src/Jobs/GerarCobrancasGatewayLoteJob.php` | Job | 85 | **Revisado** | Emissão de boletos em lote por competência. |
| `src/Jobs/ProcessarWebhookJob.php` | Job | 110 | **Revisado** | Processamento assíncrono de webhook já persistido. |
| `src/Listeners/CancelarTitulosMatriculaCancelada.php` | Listener | 65 | **Revisado** | Regra de negócio: cancela parcelas futuras não vencidas. |
| `src/Listeners/CriarContratoAposMatricula.php` | Listener | 55 | **Revisado** | Gancho de integração na matrícula de aluno. |
| `database/migrations/2026_01_01_000001_create_financeiro_schema.php` | Migration | 28 | **Revisado** | Criação do schema `financeiro` no PostgreSQL. |
| `database/migrations/2026_01_01_000002_create_plano_contas_e_bancos.php` | Migration | 72 | **Revisado** | Contas bancárias e plano de contas. |
| `database/migrations/2026_01_01_000003_create_responsaveis_financeiros.php` | Migration | 68 | **Revisado** | Responsáveis e vínculo N:N de rateio. |
| `database/migrations/2026_01_01_000004_create_planos_pagamento.php` | Migration | 60 | **Revisado** | Planos e itens de mensalidade/matrícula. |
| `database/migrations/2026_01_01_000005_create_contratos_e_titulos.php` | Migration | 95 | **Revisado** | Contratos e títulos com constraints financeiras. |
| `database/migrations/2026_01_01_000006_create_transacoes_e_gateways.php` | Migration | 130 | **Revisado** | Pagamentos, auditoria contábil, webhooks e Asaas. |
| `database/migrations/2026_01_01_000007_create_regua_cobranca.php` | Migration | 85 | **Revisado** | Regras e logs de envio da régua. |
| `database/migrations/2026_01_01_000008_add_renegociacao_id_to_titulos.php` | Migration | 35 | **Revisado** | Vínculo de novação em títulos. |
| `database/migrations/2026_01_01_000010_create_financeiro_menus.php` | Migration | 140 | **Revisado** | Estrutura de menus no core i-Educar. |
| `database/migrations/2026_01_01_000011_atualizar_menus_e_permissoes_financeiro.php` | Migration | 145 | **Revisado** | Permissões de processo por perfil i-Educar. |
| `database/migrations/2026_01_01_000012_create_gateway_configuracoes.php` | Migration | 40 | **Revisado** | Credenciais dinâmicas do gateway por tenant. |
| `database/migrations/2026_01_01_000013_add_escola_id_to_contratos.php` | Migration | 38 | **Revisado** | Isolamento multiescola em contratos. |
| `database/migrations/2026_01_01_000014_add_installment_to_cobrancas_gateway.php` | Migration | 36 | **Revisado** | Suporte a carnês vinculados a contratos. |
| `database/seeders/PlanoContasPadraoSeeder.php` | Seeder | 120 | **Revisado** | Plano de contas padrão para escolas privadas. |
| `tests/Unit/CalculoJurosMultaTest.php` | Teste | 45 | **Revisado** | 4 testes unitários de juros e multas executados com sucesso. |
| `tests/Unit/DescontoServiceTest.php` | Teste | 70 | **Revisado** | 8 testes unitários de descontos e pontualidade executados com sucesso. |
| `tests/Integration/DuploSubmitContratoTest.php` | Teste | 94 | **Revisado** | Inconsistência identificada: falhava por ausência de dados do aluno no seed. |
| `tests/Integration/PagamentoDuranteRenegociacaoTest.php` | Teste | 95 | **Revisado** | Inconsistência identificada: chamava `Titulo::factory()` inexistente. |
| `tests/Integration/TituloPagoAntesDoJobTest.php` | Teste | 110 | **Revisado** | Inconsistência identificada: chamava `Titulo::factory()` inexistente. |
