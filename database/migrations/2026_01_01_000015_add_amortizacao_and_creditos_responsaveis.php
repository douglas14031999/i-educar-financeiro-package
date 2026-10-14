<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmortizacaoAndCreditosResponsaveis extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Adiciona valor_amortizado e valor_credito_gerado na tabela de pagamentos
        Schema::table('financeiro.pagamentos', function (Blueprint $table) {
            if (!Schema::hasColumn('financeiro.pagamentos', 'valor_amortizado')) {
                $table->decimal('valor_amortizado', 14, 2)->default(0.00)->after('valor_desconto_concedido');
            }
            if (!Schema::hasColumn('financeiro.pagamentos', 'valor_credito_gerado')) {
                $table->decimal('valor_credito_gerado', 14, 2)->default(0.00)->after('valor_amortizado');
            }
        });

        // 2. Tabela de Créditos de Responsáveis por Excedente de Pagamento
        if (!Schema::hasTable('financeiro.creditos_responsaveis')) {
            Schema::create('financeiro.creditos_responsaveis', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('responsavel_financeiro_id')->index();
                $table->unsignedBigInteger('pagamento_id')->nullable()->index();
                $table->decimal('valor', 14, 2);
                $table->string('origem', 50)->default('excedente_baixa');
                $table->string('descricao', 255)->nullable();
                $table->boolean('utilizado')->default(false);
                $table->boolean('cancelado')->default(false);
                $table->timestamps();

                $table->foreign('responsavel_financeiro_id')
                    ->references('id')
                    ->on('financeiro.responsaveis_financeiros')
                    ->onDelete('cascade');

                $table->foreign('pagamento_id')
                    ->references('id')
                    ->on('financeiro.pagamentos')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financeiro.creditos_responsaveis');

        Schema::table('financeiro.pagamentos', function (Blueprint $table) {
            $table->dropColumn(['valor_amortizado', 'valor_credito_gerado']);
        });
    }
}
