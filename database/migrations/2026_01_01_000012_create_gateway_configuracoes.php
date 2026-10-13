<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A16 — Cria tabela financeiro.gateway_configuracoes para persistência real
 * das credenciais de gateway em banco de dados, substituindo config() em runtime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financeiro.gateway_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 40)->unique()->comment('Identificador do gateway: asaas, pagseguro, etc.');
            $table->text('api_key')->nullable()->comment('Chave de API (criptografada via APP_KEY)');
            $table->string('environment', 20)->default('sandbox')->comment('sandbox|production');
            $table->text('webhook_token')->nullable()->comment('Token de autenticação do webhook');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('gateway');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financeiro.gateway_configuracoes');
    }
};
