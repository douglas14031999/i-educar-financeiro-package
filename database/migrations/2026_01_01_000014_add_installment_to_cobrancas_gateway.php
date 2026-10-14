<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona suporte a Installments do Asaas na tabela cobrancas_gateway.
 *
 * installment_id   → ID do parcelamento criado via POST /v3/installments
 * carne_url        → URL pública do carnê gerada pelo Asaas (fallback para download)
 * contrato_id      → vínculo direto ao contrato para carnês por contrato
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financeiro.cobrancas_gateway', function (Blueprint $table) {
            $table->string('installment_id')->nullable()->after('gateway_charge_id')
                ->comment('ID do parcelamento (installment) no Asaas — POST /v3/installments');
            $table->text('carne_url')->nullable()->after('link_pagamento')
                ->comment('URL pública do carnê PDF gerado pelo Asaas');
            $table->unsignedBigInteger('contrato_id')->nullable()->after('titulo_id')
                ->comment('Contrato vinculado quando o carnê é gerado por contrato inteiro');
        });
    }

    public function down(): void
    {
        Schema::table('financeiro.cobrancas_gateway', function (Blueprint $table) {
            $table->dropColumn(['installment_id', 'carne_url', 'contrato_id']);
        });
    }
};
