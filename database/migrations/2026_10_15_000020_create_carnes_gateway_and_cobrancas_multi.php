<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarnesGatewayAndCobrancasMulti extends Migration
{
    /**
     * Run the migrations.
     *
     * BUG-003 / FEAT-01: Carnê Asaas robusto e mapeamento 1:N de cobranças por parcelamento
     * BUG-016: Guarda de status e reemissão com histórico (colunas ativa, cancelada_em)
     *
     * @return void
     */
    public function up()
    {
        // 1. Tabela de Carnês do Gateway
        if (!Schema::hasTable('financeiro.carnes_gateway')) {
            Schema::create('financeiro.carnes_gateway', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('contrato_id')->index();
                $table->unsignedBigInteger('responsavel_financeiro_id')->index();
                $table->string('gateway', 30)->default('asaas');
                $table->string('installment_id', 100)->unique();
                $table->string('status', 30)->default('ativo')->index(); // 'ativo', 'cancelado', 'parcial', 'quitado'
                $table->integer('qtd_parcelas');
                $table->decimal('valor_parcela', 14, 2);
                $table->date('primeiro_vencimento');
                $table->jsonb('titulo_ids')->nullable();
                $table->jsonb('payload')->nullable();
                $table->timestamp('cancelado_em')->nullable();
                $table->unsignedBigInteger('cancelado_por')->nullable();
                $table->unsignedBigInteger('criado_por')->nullable();
                $table->timestamps();

                $table->foreign('contrato_id')
                    ->references('id')
                    ->on('financeiro.contratos')
                    ->onDelete('cascade');

                $table->foreign('responsavel_financeiro_id')
                    ->references('id')
                    ->on('financeiro.responsaveis_financeiros')
                    ->onDelete('cascade');
            });
        }

        // 2. Colunas de apoio em cobrancas_gateway
        if (Schema::hasTable('financeiro.cobrancas_gateway')) {
            Schema::table('financeiro.cobrancas_gateway', function (Blueprint $table) {
                if (!Schema::hasColumn('financeiro.cobrancas_gateway', 'carne_id')) {
                    $table->unsignedBigInteger('carne_id')->nullable()->index()->after('installment_id');
                }
                if (!Schema::hasColumn('financeiro.cobrancas_gateway', 'ativa')) {
                    $table->boolean('ativa')->default(true)->index()->after('carne_id');
                }
                if (!Schema::hasColumn('financeiro.cobrancas_gateway', 'cancelada_em')) {
                    $table->timestamp('cancelada_em')->nullable()->after('ativa');
                }
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
        if (Schema::hasTable('financeiro.cobrancas_gateway')) {
            Schema::table('financeiro.cobrancas_gateway', function (Blueprint $table) {
                $table->dropColumn(['carne_id', 'ativa', 'cancelada_em']);
            });
        }

        Schema::dropIfExists('financeiro.carnes_gateway');
    }
}
