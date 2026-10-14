<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Web do Módulo Financeiro i-Educar
|--------------------------------------------------------------------------
| Estas rotas carregam dentro do ecossistema administrativo do i-Educar,
| respeitando as permissões do usuário logado e o layout padrão do sistema.
*/

$prefix = config('financeiro.route_prefix', 'financeiro');
$configuredMiddleware = config('financeiro.middleware', ['web', 'auth']);
$middleware = array_merge((array)$configuredMiddleware, [\iEducar\Financeiro\Http\Middleware\CompartilharMenuMiddleware::class]);

Route::group(['prefix' => $prefix, 'as' => 'financeiro.', 'middleware' => $middleware, 'namespace' => 'iEducar\Financeiro\Http\Controllers'], function () {

    // 1. Dashboard Executivo Financeiro
    Route::get('/', 'DashboardController@index')->name('dashboard');

    // 2. Cadastros de Responsáveis Financeiros
    Route::resource('responsaveis', 'ResponsavelFinanceiroController');
    Route::post('responsaveis/{id}/vincular-aluno', 'ResponsavelFinanceiroController@vincularAluno')->name('responsaveis.vincular-aluno');
    Route::delete('responsaveis/{id}/desvincular-aluno/{aluno_id}', 'ResponsavelFinanceiroController@desvincularAluno')->name('responsaveis.desvincular-aluno');

    // 2.1 API Interna de Busca de Alunos e Vinculação de Responsáveis
    Route::get('api/alunos/buscar', 'AlunoBuscaController@buscar')->name('api.alunos.buscar');
    Route::get('api/alunos/{id}/detalhes', 'AlunoBuscaController@detalhes')->name('api.alunos.detalhes');
    Route::post('api/responsaveis/vincular-ou-criar', 'AlunoBuscaController@vincularOuCriarResponsavel')->name('api.responsaveis.vincular-ou-criar');

    // 3. Planos de Cobrança e Tabela de Preços
    Route::resource('planos', 'PlanoPagamentoController');

    // 4. Contratos Financeiros
    Route::resource('contratos', 'ContratoController');
    Route::post('contratos/{id}/gerar-titulos', 'ContratoController@gerarTitulos')->name('contratos.gerar-titulos');
    Route::post('contratos/{id}/cancelar', 'ContratoController@cancelar')->name('contratos.cancelar');
    Route::get('contratos/{id}/imprimir-carne', 'ContratoController@imprimirCarne')->name('contratos.imprimir-carne');

    // 5. Títulos (Parcelas e Mensalidades)
    Route::get('titulos', 'TituloController@index')->name('titulos.index');
    Route::match(['get', 'post'], 'titulos/imprimir-carne', 'TituloController@imprimirCarne')->name('titulos.imprimir-carne');
    Route::post('titulos/emitir-lote-asaas', 'TituloController@emitirLoteAsaas')->name('titulos.emitir-lote-asaas');
    Route::get('titulos/{id}', 'TituloController@show')->name('titulos.show');
    Route::post('titulos/{id}/emitir-asaas', 'TituloController@emitirAsaas')->name('titulos.emitir-asaas');
    Route::post('titulos/{id}/sincronizar-asaas', 'TituloController@sincronizarStatus')->name('titulos.sincronizar-asaas');
    Route::get('titulos/{id}/recibo-pdf', 'TituloController@imprimirRecibo')->name('titulos.recibo-pdf');
    Route::get('titulos/{id}/notificar-whatsapp', 'TituloController@notificarWhatsApp')->name('titulos.notificar-whatsapp');

    // 6. Baixa de Balcão (Caixa / Secretaria)
    Route::get('baixas/novo/{titulo_id?}', 'BaixaManualController@create')->name('baixas.create');
    Route::post('baixas', 'BaixaManualController@store')->name('baixas.store');
    Route::post('baixas/{pagamento_id}/estornar', 'BaixaManualController@estornar')->name('baixas.estornar');

    // 7. Renegociação de Dívidas e Acordos
    Route::get('renegociacao', 'RenegociacaoController@index')->name('renegociacao.index');
    Route::post('renegociacao/simular', 'RenegociacaoController@simular')->name('renegociacao.simular');
    Route::post('renegociacao/confirmar', 'RenegociacaoController@confirmar')->name('renegociacao.confirmar');
    Route::get('renegociacao/{id}/termo-pdf', 'RenegociacaoController@imprimirTermo')->name('renegociacao.termo-pdf');

    // 8. Contas a Pagar e Movimentação de Caixa
    Route::resource('contas-pagar', 'ContaPagarController');
    Route::post('contas-pagar/{id}/liquidar', 'ContaPagarController@liquidar')->name('contas-pagar.liquidar');

    // 9. Histórico de Notificações da Secretaria e Régua
    Route::get('notificacoes', 'ReguaCobrancaController@historicoNotificacoes')->name('notificacoes.index');

    // 10. Relatórios Gerenciais e Fiscais
    Route::prefix('relatorios')->as('relatorios.')->group(function () {
        Route::get('/', 'RelatorioFinanceiroController@index')->name('index');
        Route::get('inadimplencia', 'RelatorioFinanceiroController@inadimplencia')->name('inadimplencia');
        Route::get('fluxo-caixa', 'RelatorioFinanceiroController@fluxoCaixa')->name('fluxo-caixa');
        Route::get('dre', 'RelatorioFinanceiroController@dre')->name('dre');
        Route::get('informe-irpf', 'RelatorioFinanceiroController@informeIRPF')->name('informe-irpf');
        Route::get('quitacao-anual', 'RelatorioFinanceiroController@quitacaoAnual')->name('quitacao-anual');
    });

    // 10. Configurações de Gateway e Parâmetros
    Route::prefix('configuracoes')->as('configuracoes.')->group(function () {
        Route::get('gateway', 'GatewayConfigController@index')->name('gateway');
        Route::post('gateway/salvar', 'GatewayConfigController@salvar')->name('gateway.salvar');
        Route::post('gateway/testar-conexao', 'GatewayConfigController@testarConexao')->name('gateway.testar-conexao');

        // Régua de Cobrança
        Route::get('regua', 'ReguaCobrancaController@index')->name('regua');
        Route::post('regua/{id}/atualizar', 'ReguaCobrancaController@atualizar')->name('regua.atualizar');
        Route::post('regua/executar-manual', 'ReguaCobrancaController@executarManual')->name('regua.executar-manual');
    });
});

