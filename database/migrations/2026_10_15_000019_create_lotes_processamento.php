<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLotesProcessamento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('financeiro.lotes_processamento')) {
            Schema::create('financeiro.lotes_processamento', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('tipo', 50)->index(); // 'emissao_asaas', 'sincronizacao_asaas', 'regua_cobranca', etc.
                $table->jsonb('parametros')->nullable();
                $table->integer('total')->default(0);
                $table->integer('sucesso')->default(0);
                $table->integer('falha')->default(0);
                $table->integer('ignorados')->default(0);
                $table->string('status', 30)->default('processando')->index(); // 'processando', 'concluido', 'falha'
                $table->unsignedBigInteger('usuario_id')->nullable()->index();
                $table->timestamp('iniciado_em')->nullable();
                $table->timestamp('finalizado_em')->nullable();
                $table->jsonb('erros')->nullable();
                $table->timestamps();
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
        Schema::dropIfExists('financeiro.lotes_processamento');
    }
}
