<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Fase3OperacoesEFechamento extends Migration
{
    /**
     * Run the migrations.
     *
     * T3.3 (BUG-023): Unicidade de contratos por matrícula/ano e snapshot de plano
     * T3.5 (BUG-025): Soft delete compatível com CPF único em responsáveis
     * T3.8 (BUG-026 / FEAT-10): Extrato bancário e liquidação de contas a pagar
     *
     * @return void
     */
    public function up()
    {
        // 1. Contratos: colunas de snapshot e auditoria de cancelamento
        if (Schema::hasTable('financeiro.contratos')) {
            Schema::table('financeiro.contratos', function (Blueprint $table) {
                if (!Schema::hasColumn('financeiro.contratos', 'plano_snapshot')) {
                    $table->jsonb('plano_snapshot')->nullable();
                }
                if (!Schema::hasColumn('financeiro.contratos', 'cancelado_em')) {
                    $table->timestamp('cancelado_em')->nullable();
                }
                if (!Schema::hasColumn('financeiro.contratos', 'cancelado_por')) {
                    $table->unsignedBigInteger('cancelado_por')->nullable();
                }
            });
        }

        // 2. Lançamentos (Contas a Pagar): colunas de liquidação completa
        if (Schema::hasTable('financeiro.lancamentos')) {
            Schema::table('financeiro.lancamentos', function (Blueprint $table) {
                if (!Schema::hasColumn('financeiro.lancamentos', 'valor_pago')) {
                    $table->decimal('valor_pago', 14, 2)->nullable();
                }
                if (!Schema::hasColumn('financeiro.lancamentos', 'valor_juros')) {
                    $table->decimal('valor_juros', 14, 2)->default(0.00);
                }
                if (!Schema::hasColumn('financeiro.lancamentos', 'valor_multa')) {
                    $table->decimal('valor_multa', 14, 2)->default(0.00);
                }
                if (!Schema::hasColumn('financeiro.lancamentos', 'valor_desconto')) {
                    $table->decimal('valor_desconto', 14, 2)->default(0.00);
                }
                if (!Schema::hasColumn('financeiro.lancamentos', 'forma_pagamento')) {
                    $table->string('forma_pagamento', 30)->nullable();
                }
                if (!Schema::hasColumn('financeiro.lancamentos', 'liquidado_por')) {
                    $table->unsignedBigInteger('liquidado_por')->nullable();
                }
                if (!Schema::hasColumn('financeiro.lancamentos', 'liquidado_em')) {
                    $table->timestamp('liquidado_em')->nullable();
                }
                if (!Schema::hasColumn('financeiro.lancamentos', 'cancelado_em')) {
                    $table->timestamp('cancelado_em')->nullable();
                }
                if (!Schema::hasColumn('financeiro.lancamentos', 'cancelado_por')) {
                    $table->unsignedBigInteger('cancelado_por')->nullable();
                }
            });
        }

        // 3. Tabela de Movimentações de Conta (Extrato Bancário)
        if (!Schema::hasTable('financeiro.movimentacoes_conta')) {
            Schema::create('financeiro.movimentacoes_conta', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('conta_bancaria_id')->index();
                $table->string('tipo', 20); // 'entrada', 'saida'
                $table->decimal('valor', 14, 2);
                $table->decimal('saldo_anterior', 14, 2);
                $table->decimal('saldo_posterior', 14, 2);
                $table->string('origem_tipo', 50)->nullable(); // 'lancamento', 'pagamento_titulo', 'ajuste'
                $table->unsignedBigInteger('origem_id')->nullable()->index();
                $table->date('data_movimentacao')->index();
                $table->string('descricao', 255);
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamps();

                $table->foreign('conta_bancaria_id')
                    ->references('id')
                    ->on('financeiro.contas_bancarias')
                    ->onDelete('restrict');
            });
        }

        // 4. Índices parciais de unicidade PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                CREATE UNIQUE INDEX IF NOT EXISTS idx_contratos_matricula_ano_ativo
                ON financeiro.contratos (matricula_id, ano_letivo)
                WHERE deleted_at IS NULL AND status != 'cancelado';
            ");

            DB::statement("
                CREATE UNIQUE INDEX IF NOT EXISTS idx_titulos_contrato_resp_tipo_parcela
                ON financeiro.titulos (contrato_id, responsavel_financeiro_id, tipo, numero_parcela)
                WHERE deleted_at IS NULL AND status != 'cancelado' AND contrato_id IS NOT NULL;
            ");

            // Permite reuso de CPF para novos cadastros caso registro anterior esteja soft-deleted
            DB::statement("
                ALTER TABLE financeiro.responsaveis_financeiros
                DROP CONSTRAINT IF EXISTS responsaveis_financeiros_cpf_cnpj_unique;
            ");

            DB::statement("
                CREATE UNIQUE INDEX IF NOT EXISTS idx_responsaveis_cpf_cnpj_active
                ON financeiro.responsaveis_financeiros (cpf_cnpj)
                WHERE deleted_at IS NULL;
            ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS financeiro.idx_contratos_matricula_ano_ativo;");
            DB::statement("DROP INDEX IF EXISTS financeiro.idx_titulos_contrato_resp_tipo_parcela;");
            DB::statement("DROP INDEX IF EXISTS financeiro.idx_responsaveis_cpf_cnpj_active;");
        }

        Schema::dropIfExists('financeiro.movimentacoes_conta');
    }
}
