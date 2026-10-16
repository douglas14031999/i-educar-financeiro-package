<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Web do Módulo Financeiro i-Educar
|--------------------------------------------------------------------------
| Estas rotas carregam dentro do ecossistema administrativo do i-Educar,
| respeitando as permissões do usuário logado (RBAC via financeiro.can) e o layout padrão.
*/

$prefix = config('financeiro.route_prefix', 'financeiro');
$configuredMiddleware = config('financeiro.middleware', ['web', 'auth']);
$middleware = array_merge((array)$configuredMiddleware, [\iEducar\Financeiro\Http\Middleware\CompartilharMenuMiddleware::class]);

Route::group(['prefix' => $prefix, 'as' => 'financeiro.', 'middleware' => $middleware, 'namespace' => 'iEducar\Financeiro\Http\Controllers'], function () {

    // 1. Dashboard Executivo Financeiro
    Route::get('/', 'DashboardController@index')
        ->name('dashboard')
        ->middleware('financeiro.can:MENU_DASHBOARD,visualiza');

    // 2. Cadastros de Responsáveis Financeiros
    Route::resource('responsaveis', 'ResponsavelFinanceiroController')->middleware('financeiro.can:MENU_RESPONSAVEIS,visualiza');
    Route::post('responsaveis/{id}/vincular-aluno', 'ResponsavelFinanceiroController@vincularAluno')
        ->name('responsaveis.vincular-aluno')
        ->middleware('financeiro.can:MENU_RESPONSAVEIS,cadastra');
    Route::delete('responsaveis/{id}/desvincular-aluno/{aluno_id}', 'ResponsavelFinanceiroController@desvincularAluno')
        ->name('responsaveis.desvincular-aluno')
        ->middleware('financeiro.can:MENU_RESPONSAVEIS,exclui');

    // 2.1 API Interna de Busca de Alunos e Vinculação de Responsáveis
    Route::get('api/alunos/buscar', 'AlunoBuscaController@buscar')
        ->name('api.alunos.buscar')
        ->middleware('financeiro.can:MENU_RESPONSAVEIS,visualiza');
    Route::get('api/alunos/{id}/detalhes', 'AlunoBuscaController@detalhes')
        ->name('api.alunos.detalhes')
        ->middleware('financeiro.can:MENU_RESPONSAVEIS,visualiza');
    Route::post('api/responsaveis/vincular-ou-criar', 'AlunoBuscaController@vincularOuCriarResponsavel')
        ->name('api.responsaveis.vincular-ou-criar')
        ->middleware('financeiro.can:MENU_RESPONSAVEIS,cadastra');

    // 3. Planos de Cobrança e Tabela de Preços (BUG-024 / FEAT-08)
    Route::get('api/escolas/{escola_id}/cursos', 'PlanoPagamentoController@cursosPorEscola')
        ->name('api.escolas.cursos')
        ->middleware('financeiro.can:MENU_PLANOS,visualiza');
    Route::post('planos/{id}/copiar', 'PlanoPagamentoController@copiar')
        ->name('planos.copiar')
        ->middleware('financeiro.can:MENU_PLANOS,cadastra');
    Route::resource('planos', 'PlanoPagamentoController')->middleware('financeiro.can:MENU_PLANOS,visualiza');

    // 4. Contratos Financeiros (FIN-008: sem edit/update/destroy)
    Route::get('contratos/matriculas-sem-contrato', 'ContratoController@matriculasSemContrato')
        ->name('contratos.matriculas-sem-contrato')
        ->middleware('financeiro.can:MENU_CONTRATOS,visualiza');
    Route::resource('contratos', 'ContratoController')->only(['index', 'create', 'store', 'show'])->middleware('financeiro.can:MENU_CONTRATOS,visualiza');
    Route::get('contratos/{id}/checkout', 'ContratoController@checkout')
        ->name('contratos.checkout')
        ->middleware('financeiro.can:MENU_CONTRATOS,visualiza');
    Route::get('contratos/{id}/status-pagamento', 'ContratoController@statusPagamento')
        ->name('contratos.status-pagamento')
        ->middleware('financeiro.can:MENU_CONTRATOS,visualiza');
    Route::post('contratos/{id}/gerar-titulos', 'ContratoController@gerarTitulos')
        ->name('contratos.gerar-titulos')
        ->middleware('financeiro.can:MENU_CONTRATOS,cadastra');
    Route::post('contratos/{id}/cancelar', 'ContratoController@cancelar')
        ->name('contratos.cancelar')
        ->middleware('financeiro.can:MENU_CONTRATOS,exclui');
    Route::get('contratos/{id}/imprimir-carne', 'ContratoController@imprimirCarne')
        ->name('contratos.imprimir-carne')
        ->middleware('financeiro.can:MENU_CONTRATOS,visualiza');
    Route::post('contratos/{id}/emitir-carne-asaas', 'ContratoController@emitirCarneAsaas')
        ->name('contratos.emitir-carne-asaas')
        ->middleware('financeiro.can:MENU_CONTRATOS,cadastra');
    Route::get('contratos/{id}/carne-asaas', 'ContratoController@carneAsaas')
        ->name('contratos.carne-asaas')
        ->middleware('financeiro.can:MENU_CONTRATOS,visualiza');

    // 5. Títulos (Parcelas e Mensalidades)
    Route::get('titulos', 'TituloController@index')
        ->name('titulos.index')
        ->middleware('financeiro.can:MENU_TITULOS,visualiza');
    Route::match(['get', 'post'], 'titulos/imprimir-carne', 'TituloController@imprimirCarne')
        ->name('titulos.imprimir-carne')
        ->middleware('financeiro.can:MENU_TITULOS,visualiza');
    Route::post('titulos/emitir-lote-asaas', 'TituloController@emitirLoteAsaas')
        ->name('titulos.emitir-lote-asaas')
        ->middleware('financeiro.can:MENU_TITULOS,cadastra');
    Route::post('titulos/sincronizar-lote-asaas', 'TituloController@sincronizarLoteAsaas')
        ->name('titulos.sincronizar-lote-asaas')
        ->middleware('financeiro.can:MENU_TITULOS,cadastra');
    Route::get('titulos/{id}', 'TituloController@show')
        ->name('titulos.show')
        ->middleware('financeiro.can:MENU_TITULOS,visualiza');
    Route::post('titulos/{id}/emitir-asaas', 'TituloController@emitirAsaas')
        ->name('titulos.emitir-asaas')
        ->middleware('financeiro.can:MENU_TITULOS,cadastra');
    Route::post('titulos/{id}/sincronizar-asaas', 'TituloController@sincronizarStatus')
        ->name('titulos.sincronizar-asaas')
        ->middleware('financeiro.can:MENU_TITULOS,cadastra');
    Route::get('titulos/{id}/recibo-pdf', 'TituloController@imprimirRecibo')
        ->name('titulos.recibo-pdf')
        ->middleware('financeiro.can:MENU_TITULOS,visualiza');
    Route::get('pagamentos/{id}/recibo', 'TituloController@imprimirReciboPorPagamento')
        ->name('pagamentos.recibo')
        ->middleware('financeiro.can:MENU_TITULOS,visualiza');
    Route::get('titulos/{id}/notificar-whatsapp', 'TituloController@notificarWhatsApp')
        ->name('titulos.notificar-whatsapp')
        ->middleware('financeiro.can:MENU_TITULOS,cadastra');

    // 6. Baixa de Balcão (Caixa / Secretaria)
    Route::get('baixas/novo/{titulo_id?}', 'BaixaManualController@create')
        ->name('baixas.create')
        ->middleware('financeiro.can:MENU_BAIXA_MANUAL,visualiza');
    Route::post('baixas', 'BaixaManualController@store')
        ->name('baixas.store')
        ->middleware('financeiro.can:MENU_BAIXA_MANUAL,cadastra');
    Route::post('baixas/{pagamento_id}/estornar', 'BaixaManualController@estornar')
        ->name('baixas.estornar')
        ->middleware('financeiro.can:MENU_BAIXA_MANUAL,exclui');
    Route::get('api/titulos/buscar', 'BaixaManualController@apiBuscarTitulos')
        ->name('api.titulos.buscar')
        ->middleware('financeiro.can:MENU_BAIXA_MANUAL,visualiza');
    Route::get('api/titulos/{id}/calculo', 'BaixaManualController@apiCalcular')
        ->name('api.titulos.calculo')
        ->middleware('financeiro.can:MENU_BAIXA_MANUAL,visualiza');
    Route::get('api/stream', 'RealtimeStreamController@stream')
        ->name('api.stream')
        ->middleware('financeiro.can:MENU_DASHBOARD,visualiza');

    // 7. Renegociação de Dívidas e Acordos
    Route::get('renegociacao', 'RenegociacaoController@index')
        ->name('renegociacao.index')
        ->middleware('financeiro.can:MENU_RENEGOCIACAO,visualiza');
    Route::post('renegociacao/simular', 'RenegociacaoController@simular')
        ->name('renegociacao.simular')
        ->middleware('financeiro.can:MENU_RENEGOCIACAO,cadastra');
    Route::post('renegociacao/confirmar', 'RenegociacaoController@confirmar')
        ->name('renegociacao.confirmar')
        ->middleware('financeiro.can:MENU_RENEGOCIACAO,cadastra');
    Route::get('renegociacao/{id}/termo-pdf', 'RenegociacaoController@imprimirTermo')
        ->name('renegociacao.termo-pdf')
        ->middleware('financeiro.can:MENU_RENEGOCIACAO,visualiza');

    // 8. Contas a Pagar e Movimentação de Caixa (FEAT-10 / BUG-026)
    Route::resource('contas-pagar', 'ContaPagarController')->middleware('financeiro.can:MENU_CONTAS_PAGAR,visualiza');
    Route::post('contas-pagar/{id}/liquidar', 'ContaPagarController@liquidar')
        ->name('contas-pagar.liquidar')
        ->middleware('financeiro.can:MENU_CONTAS_PAGAR,cadastra');
    Route::post('contas-pagar/{id}/estornar', 'ContaPagarController@estornar')
        ->name('contas-pagar.estornar')
        ->middleware('financeiro.can:MENU_CONTAS_PAGAR,exclui');

    // 9. Histórico de Notificações da Secretaria e Régua
    Route::get('notificacoes', 'ReguaCobrancaController@historicoNotificacoes')
        ->name('notificacoes.index')
        ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,visualiza');

    // 10. Relatórios Gerenciais e Fiscais
    Route::prefix('relatorios')->as('relatorios.')->middleware('financeiro.can:MENU_RELATORIOS,visualiza')->group(function () {
        Route::get('/', 'RelatorioFinanceiroController@index')->name('index');
        Route::get('inadimplencia', 'RelatorioFinanceiroController@inadimplencia')->name('inadimplencia');
        Route::get('fluxo-caixa', 'RelatorioFinanceiroController@fluxoCaixa')->name('fluxo-caixa');
        Route::get('dre', 'RelatorioFinanceiroController@dre')->name('dre');
        Route::get('informe-irpf', 'RelatorioFinanceiroController@informeIRPF')->name('informe-irpf');
        Route::get('quitacao-anual', 'RelatorioFinanceiroController@quitacaoAnual')->name('quitacao-anual');
    });

    // Verificação de autenticidade documental (BUG-038)
    Route::get('verificar/{hash}', 'RelatorioFinanceiroController@verificarDocumento')->name('verificar.documento');

    // 11. Processamentos Assíncronos e Pendências Operacionais (FEAT-05)
    Route::prefix('processamentos')->as('processamentos.')->group(function () {
        Route::get('/', 'ProcessamentoController@index')
            ->name('index')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,visualiza');
        Route::post('webhooks/{id}/reprocessar', 'ProcessamentoController@reprocessarWebhook')
            ->name('webhooks.reprocessar')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,cadastra');
        Route::post('webhooks/{id}/ignorar', 'ProcessamentoController@ignorarWebhook')
            ->name('webhooks.ignorar')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,cadastra');
        Route::post('lotes/{id}/reprocessar', 'ProcessamentoController@reprocessarLote')
            ->name('lotes.reprocessar')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,cadastra');
    });

    // 12. Configurações de Gateway e Parâmetros
    Route::prefix('configuracoes')->as('configuracoes.')->group(function () {
        Route::get('gateway', 'GatewayConfigController@index')
            ->name('gateway')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,visualiza');
        Route::post('gateway/salvar', 'GatewayConfigController@salvar')
            ->name('gateway.salvar')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,cadastra');
        Route::post('gateway/testar-conexao', 'GatewayConfigController@testarConexao')
            ->name('gateway.testar-conexao')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,cadastra');
        Route::post('gateway/registrar-webhook', 'GatewayConfigController@registrarWebhook')
            ->name('gateway.registrar-webhook')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,cadastra');

        // Régua de Cobrança
        Route::get('regua', 'ReguaCobrancaController@index')
            ->name('regua')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,visualiza');
        Route::post('regua/{id}/atualizar', 'ReguaCobrancaController@atualizar')
            ->name('regua.atualizar')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,cadastra');
        Route::post('regua/executar-manual', 'ReguaCobrancaController@executarManual')
            ->name('regua.executar-manual')
            ->middleware('financeiro.can:MENU_GATEWAY_CONFIG,cadastra');
    });
});
