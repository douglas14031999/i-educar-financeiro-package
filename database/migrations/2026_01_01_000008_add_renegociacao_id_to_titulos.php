<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRenegociacaoIdToTitulos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('financeiro.titulos', function (Blueprint $table) {
            $table->unsignedBigInteger('renegociacao_id')->nullable()->index();

            $table->foreign('renegociacao_id')
                ->references('id')
                ->on('financeiro.renegociacoes')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('financeiro.titulos', function (Blueprint $table) {
            $table->dropForeign(['renegociacao_id']);
            $table->dropColumn('renegociacao_id');
        });
    }
}
