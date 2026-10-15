<?php

namespace iEducar\Financeiro\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use iEducar\Financeiro\FinanceiroServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            FinanceiroServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:6Cu/KuK4+BPWdR6x6bCXDeExIMNx6TkUMExhNOxxeed=');
        
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver'      => 'pgsql',
            'host'        => '127.0.0.1',
            'port'        => '5432',
            'database'    => 'financeiro_test',
            'username'    => 'postgres',
            'password'    => '',
            'charset'     => 'utf8',
            'prefix'      => '',
            'schema'      => 'financeiro',
            'search_path' => 'financeiro,public,pmieducar,cadastro',
            'sslmode'     => 'prefer',
        ]);

        $app['config']->set('financeiro.middleware', ['web']);
        $app['config']->set('financeiro.gateways.asaas.webhook_token', 'test_secret_token_123');
        $app['config']->set('financeiro.politicas.multa_percentual', 2.00);
        $app['config']->set('financeiro.politicas.juros_mensal_percentual', 1.00);
    }

    protected function defineDatabaseMigrations()
    {
        $this->setupIeducarMockTables();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupIeducarMockTables();
    }

    protected function setupIeducarMockTables(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("CREATE SCHEMA IF NOT EXISTS pmieducar;");
            DB::statement("CREATE SCHEMA IF NOT EXISTS cadastro;");
            DB::statement("CREATE SCHEMA IF NOT EXISTS financeiro;");
        }

        DB::statement("CREATE TABLE IF NOT EXISTS pmieducar.escola (
            cod_escola INTEGER PRIMARY KEY,
            ref_idpes INTEGER,
            ativo INTEGER DEFAULT 1
        );");

        DB::statement("CREATE TABLE IF NOT EXISTS cadastro.pessoa (
            idpes INTEGER PRIMARY KEY,
            nome TEXT,
            email TEXT
        );");

        DB::statement("CREATE TABLE IF NOT EXISTS pmieducar.aluno (
            cod_aluno INTEGER PRIMARY KEY,
            ref_idpes INTEGER,
            ativo INTEGER DEFAULT 1
        );");

        DB::statement("CREATE TABLE IF NOT EXISTS pmieducar.matricula (
            cod_matricula INTEGER PRIMARY KEY,
            ref_cod_aluno INTEGER,
            ref_ref_cod_escola INTEGER,
            ano INTEGER,
            ativo INTEGER DEFAULT 1
        );");

        DB::statement("CREATE TABLE IF NOT EXISTS pmieducar.turma (
            cod_turma INTEGER PRIMARY KEY,
            nm_turma TEXT,
            ref_ref_cod_escola INTEGER,
            ativo INTEGER DEFAULT 1
        );");

        DB::statement("CREATE TABLE IF NOT EXISTS pmieducar.matricula_turma (
            ref_cod_matricula INTEGER,
            ref_cod_turma INTEGER
        );");

        DB::statement("CREATE TABLE IF NOT EXISTS public.menus (
            id SERIAL PRIMARY KEY,
            name TEXT,
            process INTEGER,
            old INTEGER
        );");

        DB::statement("CREATE TABLE IF NOT EXISTS public.menu_tipo_usuario (
            menu_id INTEGER,
            tipo_usuario_id INTEGER,
            visualiza INTEGER DEFAULT 1,
            cadastra INTEGER DEFAULT 1,
            exclui INTEGER DEFAULT 1,
            PRIMARY KEY (menu_id, tipo_usuario_id)
        );");

        DB::statement("CREATE TABLE IF NOT EXISTS financeiro.menus (
            id SERIAL PRIMARY KEY,
            name TEXT,
            process INTEGER,
            old INTEGER
        );");

        DB::statement("CREATE TABLE IF NOT EXISTS financeiro.menu_tipo_usuario (
            menu_id INTEGER,
            tipo_usuario_id INTEGER,
            visualiza INTEGER DEFAULT 1,
            cadastra INTEGER DEFAULT 1,
            exclui INTEGER DEFAULT 1,
            PRIMARY KEY (menu_id, tipo_usuario_id)
        );");

        try {
            DB::statement("INSERT INTO pmieducar.escola (cod_escola, ativo) VALUES (1, 1) ON CONFLICT (cod_escola) DO NOTHING;");
            DB::statement("INSERT INTO cadastro.pessoa (idpes, nome, email) VALUES (1, 'Aluno Teste', 'aluno@teste.com') ON CONFLICT (idpes) DO NOTHING;");
            DB::statement("INSERT INTO pmieducar.aluno (cod_aluno, ref_idpes, ativo) VALUES (1, 1, 1) ON CONFLICT (cod_aluno) DO NOTHING;");
            DB::statement("INSERT INTO pmieducar.matricula (cod_matricula, ref_cod_aluno, ref_ref_cod_escola, ano, ativo) VALUES (1, 1, 1, " . date('Y') . ", 1) ON CONFLICT (cod_matricula) DO NOTHING;");
        } catch (\Throwable $e) {
            // Ignore if schema conflict
        }
    }
}
