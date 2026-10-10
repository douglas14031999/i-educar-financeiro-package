<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransacoesEGateways extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Tabela de Descontos e Convênios (Bolsas, Irmãos, Pontualidade)
        Schema::create('financeiro.descontos_convenios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nome', 100);
            $table->string('tipo', 30)->default('percentual'); // percentual, valor_fixo, pontualidade
            $table->decimal('valor_regra', 14, 2);
            $table->boolean('cumulativo')->default(false);
            $table->date('vigencia_inicio');
            $table->date('vigencia_fim')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // 2. Descontos Aplicados aos Títulos
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

        // 3. Pagamentos / Baixas (Integral, Parcial e Estorno)
        Schema::create('financeiro.pagamentos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('titulo_id')->index();
            $table->string('forma_pagamento', 40)->default('pix'); // pix, boleto, cartao_credito, dinheiro, transferencia
            $table->date('data_pagamento')->index();
            $table->date('data_compensacao')->nullable();
            $table->decimal('valor_pago', 14, 2);
            $table->decimal('valor_juros', 14, 2)->default(0.00);
            $table->decimal('valor_multa', 14, 2)->default(0.00);
            $table->decimal('valor_desconto_concedido', 14, 2)->default(0.00);
            $table->string('origem_baixa', 30)->default('manual'); // manual, webhook_asaas, cnab
            $table->integer('usuario_id')->nullable();
            $table->string('comprovante_hash', 255)->nullable();
            $table->boolean('estornado')->default(false);
            $table->text('motivo_estorno')->nullable();
            $table->timestamp('data_estorno')->nullable();
            $table->integer('estornado_por_usuario_id')->nullable();
            $table->timestamps();

            $table->foreign('titulo_id')
                ->references('id')
                ->on('financeiro.titulos')
                ->onDelete('restrict');
        });

        // 4. Cobranças nos Gateways (Asaas)
        Schema::create('financeiro.cobrancas_gateway', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('titulo_id')->index();
            $table->string('gateway', 50)->default('asaas');
            $table->string('gateway_charge_id', 150)->unique();
            $table->string('status_externo', 50);
            $table->string('linha_digitavel', 100)->nullable();
            $table->string('codigo_barras', 100)->nullable();
            $table->string('link_boleto_pdf', 500)->nullable();
            $table->string('link_pagamento', 500)->nullable();
            $table->text('pix_copia_cola')->nullable();
            $table->text('pix_qr_code_base64')->nullable();
            $table->jsonb('payload_requisicao')->nullable();
            $table->jsonb('payload_resposta')->nullable();
            $table->timestamps();

            $table->foreign('titulo_id')
                ->references('id')
                ->on('financeiro.titulos')
                ->onDelete('cascade');
        });

        // 5. Tabela de Idempotência para Webhooks (Garante Risco Zero de Dupla Baixa)
        Schema::create('financeiro.webhook_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('gateway', 50)->default('asaas');
            $table->string('event_id', 150)->unique();
            $table->string('event_type', 100);
            $table->jsonb('payload');
            $table->boolean('processado')->default(false);
            $table->timestamp('processado_em')->nullable();
            $table->text('erro')->nullable();
            $table->timestamps();
        });

        // 6. Contas a Pagar / Despesas da Escola
        Schema::create('financeiro.lancamentos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('escola_id')->index(); // pmieducar.escola(cod_escola)
            $table->unsignedBigInteger('conta_bancaria_id')->nullable()->index();
            $table->unsignedBigInteger('plano_conta_id')->nullable()->index();
            $table->unsignedBigInteger('centro_custo_id')->nullable()->index();
            $table->string('tipo', 20)->default('despesa'); // receita, despesa, transferencia
            $table->string('descricao', 255);
            $table->string('fornecedor_nome', 200)->nullable();
            $table->date('data_competencia');
            $table->date('data_vencimento')->index();
            $table->date('data_pagamento')->nullable();
            $table->decimal('valor', 14, 2);
            $table->string('status', 30)->default('a_pagar'); // a_pagar, pago, cancelado
            $table->string('documento_fiscal', 100)->nullable();
            $table->text('observacoes')->nullable();
            $table->integer('usuario_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conta_bancaria_id')
                ->references('id')
                ->on('financeiro.contas_bancarias')
                ->onDelete('set null');

            $table->foreign('plano_conta_id')
                ->references('id')
                ->on('financeiro.plano_contas')
                ->onDelete('set null');

            $table->foreign('centro_custo_id')
                ->references('id')
                ->on('financeiro.centros_custo')
                ->onDelete('set null');
        });

        // 7. Renegociações e Acordos Financeiros
        Schema::create('financeiro.renegociacoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('numero_acordo', 50)->unique();
            $table->integer('aluno_id')->index();
            $table->unsignedBigInteger('responsavel_financeiro_id')->index();
            $table->decimal('valor_original', 14, 2);
            $table->decimal('valor_desconto_concedido', 14, 2)->default(0.00);
            $table->decimal('valor_acordo', 14, 2);
            $table->integer('qtd_parcelas')->default(1);
            $table->string('status', 30)->default('ativo'); // ativo, quitado, cancelado
            $table->integer('usuario_id')->nullable();
            $table->timestamps();

            $table->foreign('responsavel_financeiro_id')
                ->references('id')
                ->on('financeiro.responsaveis_financeiros')
                ->onDelete('restrict');
        });

        Schema::create('financeiro.renegociacao_titulos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('renegociacao_id')->index();
            $table->unsignedBigInteger('titulo_id')->index();
            $table->timestamps();

            $table->foreign('renegociacao_id')
                ->references('id')
                ->on('financeiro.renegociacoes')
                ->onDelete('cascade');

            $table->foreign('titulo_id')
                ->references('id')
                ->on('financeiro.titulos')
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
        Schema::dropIfExists('financeiro.renegociacao_titulos');
        Schema::dropIfExists('financeiro.renegociacoes');
        Schema::dropIfExists('financeiro.lancamentos');
        Schema::dropIfExists('financeiro.webhook_events');
        Schema::dropIfExists('financeiro.cobrancas_gateway');
        Schema::dropIfExists('financeiro.pagamentos');
        Schema::dropIfExists('financeiro.titulo_descontos');
        Schema::dropIfExists('financeiro.descontos_convenios');
    }
}
