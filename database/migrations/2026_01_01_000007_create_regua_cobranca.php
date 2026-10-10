<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateReguaCobranca extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Tabela de Regras Configuráveis da Régua de Cobrança
        Schema::create('financeiro.regua_cobranca_regras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('escola_id')->nullable()->index(); // Null = Regra Geral da Rede
            $table->string('nome_regra', 150);
            $table->string('tipo_gatilho', 30); // antes_vencimento, no_vencimento, apos_vencimento
            $table->integer('dias_gatilho'); // -7, -3, 0, 3, 10
            $table->string('canal', 30)->default('whatsapp'); // whatsapp, email, sms
            $table->string('titulo_template', 200)->nullable();
            $table->text('corpo_template');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // 2. Tabela de Logs e Rastreabilidade de Envios da Régua
        Schema::create('financeiro.regua_envios_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('regra_id')->nullable()->index();
            $table->unsignedBigInteger('titulo_id')->index();
            $table->unsignedBigInteger('responsavel_id')->index();
            $table->string('canal', 30);
            $table->string('destinatario', 150);
            $table->text('mensagem_enviada');
            $table->boolean('sucesso')->default(true);
            $table->text('erro')->nullable();
            $table->timestamp('enviado_em');
            $table->timestamps();

            $table->foreign('regra_id')
                ->references('id')
                ->on('financeiro.regua_cobranca_regras')
                ->onDelete('set null');

            $table->foreign('titulo_id')
                ->references('id')
                ->on('financeiro.titulos')
                ->onDelete('cascade');

            $table->foreign('responsavel_id')
                ->references('id')
                ->on('financeiro.responsaveis_financeiros')
                ->onDelete('cascade');
        });

        // Regras Iniciais Padrão (D-7, D-3, D0, D+3, D+10)
        DB::table('financeiro.regua_cobranca_regras')->insert([
            [
                'nome_regra' => 'Lembrete Amigável Pré-Vencimento (D-7)',
                'tipo_gatilho' => 'antes_vencimento',
                'dias_gatilho' => -7,
                'canal' => 'whatsapp',
                'titulo_template' => 'Lembrete de Mensalidade Escolar',
                'corpo_template' => 'Olá, {responsavel}! Lembramos que a mensalidade de {competencia} do aluno {aluno} vence em {vencimento} no valor de R$ {valor}. Acesse a 2ª via ou pague com Pix pelo link: {link_pagamento}',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome_regra' => 'Aviso Próximo ao Vencimento (D-3)',
                'tipo_gatilho' => 'antes_vencimento',
                'dias_gatilho' => -3,
                'canal' => 'whatsapp',
                'titulo_template' => 'Aviso de Vencimento Próximo',
                'corpo_template' => 'Olá, {responsavel}! A mensalidade escolar ({competencia}) vence em apenas 3 dias ({vencimento}). Valor: R$ {valor}. Linha digitável: {linha_digitavel} ou pague via Pix: {link_pagamento}',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome_regra' => 'Alerta do Dia do Vencimento (D0)',
                'tipo_gatilho' => 'no_vencimento',
                'dias_gatilho' => 0,
                'canal' => 'whatsapp',
                'titulo_template' => 'Mensalidade Vencendo Hoje',
                'corpo_template' => 'Atenção, {responsavel}! A mensalidade escolar referente a {competencia} vence HOJE ({vencimento}). Valor: R$ {valor}. Pague agora pelo Pix e evite juros e encargos: {link_pagamento}',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome_regra' => 'Cobrança Pós-Vencimento Inicial (D+3)',
                'tipo_gatilho' => 'apos_vencimento',
                'dias_gatilho' => 3,
                'canal' => 'whatsapp',
                'titulo_template' => 'Aviso de Mensalidade em Atraso',
                'corpo_template' => 'Olá, {responsavel}! Não identificamos o pagamento da mensalidade de {competencia} (Vencida em {vencimento}). Caso já tenha efetuado, por favor desconsidere. Para regularizar acesse: {link_pagamento}',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome_regra' => 'Notificação de Inadimplência e Encargos (D+10)',
                'tipo_gatilho' => 'apos_vencimento',
                'dias_gatilho' => 10,
                'canal' => 'whatsapp',
                'titulo_template' => 'Notificação de Pendência Financeira',
                'corpo_template' => 'Prezado(a) {responsavel}, a mensalidade de {competencia} está com 10 dias de atraso. Estão incidindo multa contratual de 2% e juros de 1% a.m. Acesse a 2ª via atualizada pelo link: {link_pagamento} ou entre em contato com a tesouraria da escola.',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financeiro.regua_envios_log');
        Schema::dropIfExists('financeiro.regua_cobranca_regras');
    }
}
