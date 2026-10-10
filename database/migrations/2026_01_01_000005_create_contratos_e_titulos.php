<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContratosETitulos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Contratos Financeiros Vinculados à Matrícula Escolar
        Schema::create('financeiro.contratos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('numero_contrato', 50)->unique();
            $table->integer('matricula_id')->index(); // pmieducar.matricula(cod_matricula)
            $table->integer('aluno_id')->index(); // pmieducar.aluno(cod_aluno)
            $table->unsignedBigInteger('responsavel_financeiro_id')->index();
            $table->unsignedBigInteger('plano_id')->nullable()->index();
            $table->integer('ano_letivo')->index();
            $table->date('data_inicio');
            $table->date('data_termino');
            $table->decimal('valor_total_bruto', 14, 2);
            $table->decimal('valor_total_descontos', 14, 2)->default(0.00);
            $table->decimal('valor_total_liquido', 14, 2);
            $table->integer('dia_vencimento')->default(10);
            $table->string('status', 30)->default('ativo'); // rascunho, ativo, suspenso, cancelado, transferido, quitado
            $table->text('motivo_cancelamento')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('responsavel_financeiro_id')
                ->references('id')
                ->on('financeiro.responsaveis_financeiros')
                ->onDelete('restrict');

            $table->foreign('plano_id')
                ->references('id')
                ->on('financeiro.planos')
                ->onDelete('set null');
        });

        // 2. Títulos (Parcelas de Mensalidade e Cobranças Avulsas)
        Schema::create('financeiro.titulos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('contrato_id')->nullable()->index();
            $table->integer('aluno_id')->index(); // pmieducar.aluno(cod_aluno)
            $table->unsignedBigInteger('responsavel_financeiro_id')->index();
            $table->integer('escola_id')->index(); // pmieducar.escola(cod_escola)
            $table->string('tipo', 40)->default('mensalidade'); // mensalidade, matricula, material, taxa_avulsa, renegociacao
            $table->integer('numero_parcela')->default(1);
            $table->integer('total_parcelas')->default(1);
            $table->string('competencia', 7)->index(); // 'YYYY-MM'
            $table->date('data_vencimento')->index();
            $table->date('data_prorrogacao')->nullable();
            $table->decimal('valor_nominal', 14, 2);
            $table->decimal('valor_desconto', 14, 2)->default(0.00);
            $table->decimal('valor_multa', 14, 2)->default(0.00);
            $table->decimal('valor_juros', 14, 2)->default(0.00);
            $table->decimal('valor_pago', 14, 2)->default(0.00);
            $table->decimal('valor_saldo', 14, 2);
            $table->string('status', 30)->default('aberto')->index(); // aberto, pago, parcial, vencido, cancelado, renegociado, estornado
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('contrato_id')
                ->references('id')
                ->on('financeiro.contratos')
                ->onDelete('set null');

            $table->foreign('responsavel_financeiro_id')
                ->references('id')
                ->on('financeiro.responsaveis_financeiros')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financeiro.titulos');
        Schema::dropIfExists('financeiro.contratos');
    }
}
