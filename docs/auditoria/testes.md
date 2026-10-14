# Matriz de Testes Mínimos Obrigatórios (T01 a T26)

| Documento | Detalhe |
|---|---|
| **Auditoria** | Cenários de Testes, Comandos e Evidências de Validação |
| **Referência** | PRD de Homologação — Seção 10 |
| **Banco de Dados** | PostgreSQL 15.17 (`financeiro_test`) |

---

## 1. Quadro de Cenários de Teste

| ID | Cenário | Camada / Arquivo de Teste | Critério de Aceite |
|---|---|---|---|
| **T01** | Webhook sem token configurado | `tests/Integration/WebhookSegurancaTest.php` | Rejeição HTTP 401; nenhuma mutação no banco; nenhum segredo aprendido. |
| **T02** | Token incorreto no webhook | `tests/Integration/WebhookSegurancaTest.php` | Rejeição HTTP 401 Unauthorized; evento não persistido. |
| **T03** | Mesmo evento Asaas repetido | `tests/Integration/WebhookSegurancaTest.php` | Resposta 200 `already_processed`; apenas 1 registro em `financeiro.webhook_events`. |
| **T04** | Webhook e sincronização simultâneos | `tests/Integration/ConcorrenciaBaixaTest.php` | Bloqueio `FOR UPDATE` no PostgreSQL: exatamente 1 baixa registrada; sem pagamento duplicado. |
| **T05** | Pagamento excedente e estorno | `tests/Integration/EstornoAmortizacaoTest.php` | Dívida R$ 100, pago R$ 120 (crédito R$ 20). Estorno restaura saldo exato de R$ 100. |
| **T06** | Dois estornos simultâneos | `tests/Integration/ConcorrenciaEstornoTest.php` | Transação atômica: exatamente 1 estorno efetivado; segundo request recebe erro de duplicidade. |
| **T07** | Crédito excedente já utilizado | `tests/Integration/EstornoAmortizacaoTest.php` | Estorno bloqueado com `CreditoJaUtilizadoException` se o crédito já tiver sido consumido. |
| **T08** | Baixa retroativa | `tests/Integration/ReciboPagamentoTest.php` | Recibo busca pelo ID do pagamento recém-gerado; comprovante do pagamento retroativo correto. |
| **T09** | Impressão de carnê | `tests/Integration/CarneImpressaoTest.php` | GET em `contratos/{id}/imprimir-carne` não chama `criarInstallment` nem mutaciona o banco. |
| **T10** | Seleção de duas parcelas | `tests/Integration/CarneImpressaoTest.php` | Seleção de 2 títulos imprime estritamente 2 parcelas, preservando `$ids`. |
| **T11** | Geração repetida de parcelas | `tests/Integration/ContratoServiceTest.php` | Repetir `gerarTitulos()` não duplica parcelas existentes e mantém total contratado. |
| **T12** | Parcela ausente com rateio | `tests/Integration/ContratoServiceTest.php` | Regeneração aplica rateio percentual e desconto proporcional preservados no contrato. |
| **T13** | Matrícula de outro aluno | `tests/Integration/ContratoValidacaoTest.php` | Rejeitado mesmo que `escola_id` seja informado na requisição. |
| **T14** | Plano de 24 parcelas | `tests/Integration/PlanoPagamentoTest.php` | Nenhuma competência com mês 0; parcelas 13 a 24 avançam para o ano letivo subsequente. |
| **T15** | Pagamento após simulação | `tests/Integration/PagamentoDuranteRenegociacaoTest.php` | Confirmação de renegociação relê títulos e bloqueia se saldo foi alterado após simulação. |
| **T16** | Renegociações concorrentes | `tests/Integration/ConcorrenciaRenegociacaoTest.php` | Duas confirmações simultâneas não consomem os mesmos títulos; novação única. |
| **T17** | Título cancelado ou renegociado | `tests/Integration/TituloElegibilidadeTest.php` | Tentativa de baixa ou emissão de boleto em título cancelado/renegociado é rejeitada no servidor. |
| **T18** | Liquidação repetida de despesa | `tests/Integration/ContaPagarTest.php` | Liquidação repetida de conta a pagar já paga é bloqueada sem sobrescrever data ou conta. |
| **T19** | Título prorrogado | `tests/Integration/TituloProrrogacaoTest.php` | Título com `data_prorrogacao` futura não aparece em `scopeVencidos()` nem como inadimplente. |
| **T20** | Usuário de outra escola | `tests/Integration/IsolamentoMultiescolaTest.php` | Requisição para recurso de escola não autorizada retorna HTTP 403 Forbidden. |
| **T21** | Régua com ativo=false | `tests/Integration/ReguaCobrancaTest.php` | Atualização com `ativo=false` ou `ativo=0` persiste `ativo = false` no banco. |
| **T22** | Falha ao copiar Pix | `tests/Unit/ViewClipboardTest.php` | Script Blade trata Promise com feedback visual de sucesso e erro. |
| **T23** | Gateway cria cobrança, resposta falha | `tests/Integration/GatewayResilienciaTest.php` | Reconciliação busca cobrança existente no Asaas evitando criar segunda cobrança. |
| **T24** | Evento salvo, fila indisponível | `tests/Integration/WebhookFilaTest.php` | Evento salvo em `financeiro.webhook_events` com `processado=false` pode ser reprocessado. |
| **T25** | Relatórios após estorno | `tests/Integration/RelatoriosReconciliacaoTest.php` | Pagamentos estornados são excluídos do Fluxo de Caixa e da DRE; totais reconciliados. |
| **T26** | Todas as rotas registradas | `tests/Integration/RotasValidasTest.php` | 100% das rotas registradas resolvem para controllers e métodos existentes. |

---

## 2. Comandos de Execução dos Testes

```powershell
# Execução da suíte completa de testes no PostgreSQL 15:
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
php .\vendor\bin\phpunit --colors=always

# Execução individual de suíte unitária:
php .\vendor\bin\phpunit --testsuite=Unit --colors=always

# Execução individual de suíte de integração:
php .\vendor\bin\phpunit --testsuite=Integration --colors=always
```
