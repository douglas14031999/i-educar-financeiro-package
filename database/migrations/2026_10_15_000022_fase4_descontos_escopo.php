<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Fase4DescontosEscopo extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ampliação da tabela de descontos e convênios com suporte a escopo e tetos (FEAT-06 / M11)
        if (Schema::hasTable('financeiro.descontos_convenios')) {
            Schema::table('financeiro.descontos_convenios', function (Blueprint $table) {
                if (!Schema::hasColumn('financeiro.descontos_convenios', 'escopo')) {
                    $table->string('escopo', 30)->default('global')->after('tipo'); // global, plano, contrato, aluno, responsavel
                }
                if (!Schema::hasColumn('financeiro.descontos_convenios', 'alvo_id')) {
                    $table->unsignedBigInteger('alvo_id')->nullable()->index()->after('escopo');
                }
                if (!Schema::hasColumn('financeiro.descontos_convenios', 'teto_valor')) {
                    $table->decimal('teto_valor', 14, 2)->nullable()->after('valor_regra');
                }
                if (!Schema::hasColumn('financeiro.descontos_convenios', 'teto_percentual')) {
                    $table->decimal('teto_percentual', 5, 2)->nullable()->after('teto_valor');
                }
            });
        }

        // 2. Garantir existência da tabela titulo_descontos para registro histórico dos descontos aplicados
        if (!Schema::hasTable('financeiro.titulo_descontos')) {
            Schema::create('financeiro.titulo_descontos', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('titulo_id')->index();
                $table->unsignedBigInteger('desconto_id')->nullable()->index();
                $table->decimal('valor_aplicado', 14, 2);
                $table->integer('concedido_por_usuario_id')->nullable();
                $table->string('motivo', 255)->nullable();
                $table->timestamps();

                $table->foreign('titulo_id')
                    ->references('id')
                    ->on('financeiro.titulos')
                    ->onDelete('cascade');

                $table->foreign('desconto_id')
                    ->references('id')
                    ->on('financeiro.descontos_convenios')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('financeiro.descontos_convenios')) {
            Schema::table('financeiro.descontos_convenios', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('financeiro.descontos_convenios', 'escopo')) $cols[] = 'escopo';
                if (Schema::hasColumn('financeiro.descontos_convenios', 'alvo_id')) $cols[] = 'alvo_id';
                if (Schema::hasColumn('financeiro.descontos_convenios', 'teto_valor')) $cols[] = 'teto_valor';
                if (Schema::hasColumn('financeiro.descontos_convenios', 'teto_percentual')) $cols[] = 'teto_percentual';
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
}
