<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResponsaveisFinanceiros extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Cadastro de Responsáveis Financeiros
        Schema::create('financeiro.responsaveis_financeiros', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('pessoa_id')->nullable()->index(); // Opcional: vínculo com cadastro.pessoa
            $table->string('nome', 255);
            $table->string('cpf_cnpj', 20)->unique();
            $table->string('rg_ie', 30)->nullable();
            $table->string('email', 255)->index();
            $table->string('telefone_celular', 20);
            $table->string('telefone_fixo', 20)->nullable();
            $table->string('cep', 10);
            $table->string('logradouro', 255);
            $table->string('numero', 20);
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100);
            $table->string('cidade', 100);
            $table->string('estado', 2);
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Vínculo N:N Aluno e Responsável com Rateio Percentual
        Schema::create('financeiro.aluno_responsavel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('aluno_id')->index(); // Vínculo com pmieducar.aluno(cod_aluno)
            $table->unsignedBigInteger('responsavel_id')->index();
            $table->string('parentesco', 50)->default('pai'); // pai, mae, avo, tutor, empresa, outro
            $table->decimal('percentual_rateio', 5, 2)->default(100.00); // Ex: 50.00 para 50%
            $table->boolean('responsavel_principal')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('responsavel_id')
                ->references('id')
                ->on('financeiro.responsaveis_financeiros')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financeiro.aluno_responsavel');
        Schema::dropIfExists('financeiro.responsaveis_financeiros');
    }
}
