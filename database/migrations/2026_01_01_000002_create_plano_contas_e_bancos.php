<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanoContasEBancos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Contas Bancárias e Caixas Físicos
        Schema::create('financeiro.contas_bancarias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('escola_id')->nullable()->index();
            $table->string('nome', 150);
            $table->string('tipo', 30)->default('corrente'); // corrente, caixa_fisico, gateway
            $table->string('banco_codigo', 10)->nullable();
            $table->string('agencia', 20)->nullable();
            $table->string('conta', 30)->nullable();
            $table->decimal('saldo_inicial', 14, 2)->default(0.00);
            $table->decimal('saldo_atual', 14, 2)->default(0.00);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Plano de Contas Hierárquico
        Schema::create('financeiro.plano_contas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 50)->unique(); // Ex: 1, 1.1, 1.1.01
            $table->string('nome', 150);
            $table->string('tipo', 20); // receita, despesa
            $table->unsignedBigInteger('pai_id')->nullable();
            $table->boolean('aceita_lancamento')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('pai_id')
                ->references('id')
                ->on('financeiro.plano_contas')
                ->onDelete('cascade');
        });

        // 3. Centros de Custo
        Schema::create('financeiro.centros_custo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('escola_id')->nullable()->index();
            $table->string('codigo', 30)->nullable();
            $table->string('nome', 150);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financeiro.centros_custo');
        Schema::dropIfExists('financeiro.plano_contas');
        Schema::dropIfExists('financeiro.contas_bancarias');
    }
}
