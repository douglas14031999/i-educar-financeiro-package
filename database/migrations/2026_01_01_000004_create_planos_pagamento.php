<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanosPagamento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Planos de Cobrança Escolar (Anuidades/Semestres)
        Schema::create('financeiro.planos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('escola_id')->index(); // pmieducar.escola(cod_escola)
            $table->integer('ano_letivo')->index();
            $table->integer('curso_id')->nullable()->index(); // pmieducar.curso(cod_curso)
            $table->integer('serie_id')->nullable()->index(); // pmieducar.serie(cod_serie)
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->decimal('valor_total', 14, 2);
            $table->integer('qtd_parcelas')->default(12);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Itens do Plano (Matrícula, Mensalidades 1..12, Material)
        Schema::create('financeiro.plano_itens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('plano_id')->index();
            $table->string('tipo_item', 50)->default('mensalidade'); // matricula, mensalidade, material, uniforme, taxa_evento
            $table->string('descricao', 150);
            $table->integer('numero_parcela')->default(1); // 0 = matricula, 1..12 = parcelas
            $table->decimal('valor', 14, 2);
            $table->integer('mes_competencia')->default(1); // 1 a 12
            $table->integer('dia_vencimento_padrao')->default(10);
            $table->timestamps();

            $table->foreign('plano_id')
                ->references('id')
                ->on('financeiro.planos')
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
        Schema::dropIfExists('financeiro.plano_itens');
        Schema::dropIfExists('financeiro.planos');
    }
}
