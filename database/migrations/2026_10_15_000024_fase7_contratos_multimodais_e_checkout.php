<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Atualizações na tabela financeiro.contratos
        if (Schema::hasTable('financeiro.contratos')) {
            Schema::table('financeiro.contratos', function (Blueprint $table) {
                if (!Schema::hasColumn('financeiro.contratos', 'forma_pagamento')) {
                    $table->string('forma_pagamento', 30)->nullable()->after('status')
                        ->comment('dinheiro, cartao_presencial, cartao_credito, pix, boleto, carne');
                }
                if (!Schema::hasColumn('financeiro.contratos', 'cartao_bandeira')) {
                    $table->string('cartao_bandeira', 50)->nullable()->after('forma_pagamento')
                        ->comment('Bandeira do cartão para pagamentos em POS/maquininha');
                }
                if (!Schema::hasColumn('financeiro.contratos', 'cartao_nsu')) {
                    $table->string('cartao_nsu', 100)->nullable()->after('cartao_bandeira')
                        ->comment('Código de autorização / NSU da maquininha');
                }
                if (!Schema::hasColumn('financeiro.contratos', 'cartao_tipo')) {
                    $table->string('cartao_tipo', 20)->nullable()->after('cartao_nsu')
                        ->comment('credito_vista, debito, credito_parcelado');
                }
                if (!Schema::hasColumn('financeiro.contratos', 'payment_link_url')) {
                    $table->text('payment_link_url')->nullable()->after('cartao_tipo')
                        ->comment('URL do Link de Pagamento gerado no Asaas');
                }
                if (!Schema::hasColumn('financeiro.contratos', 'payment_link_id')) {
                    $table->string('payment_link_id', 100)->nullable()->after('payment_link_url')
                        ->comment('ID externo do Link de Pagamento no Asaas');
                }
                if (!Schema::hasColumn('financeiro.contratos', 'quitado_em')) {
                    $table->timestamp('quitado_em')->nullable()->after('payment_link_id')
                        ->comment('Timestamp em que todas as obrigações foram quitadas');
                }
            });
        }

        // 2. Atualizações na tabela financeiro.cobrancas_gateway
        if (Schema::hasTable('financeiro.cobrancas_gateway')) {
            Schema::table('financeiro.cobrancas_gateway', function (Blueprint $table) {
                if (!Schema::hasColumn('financeiro.cobrancas_gateway', 'tipo_cobranca')) {
                    $table->string('tipo_cobranca', 30)->nullable()->after('gateway')
                        ->comment('boleto, pix, cartao_credito, payment_link');
                }
                if (!Schema::hasColumn('financeiro.cobrancas_gateway', 'qr_code_base64')) {
                    $table->text('qr_code_base64')->nullable()->after('url_comprovante')
                        ->comment('Imagem em base64 do QR Code Pix');
                }
                if (!Schema::hasColumn('financeiro.cobrancas_gateway', 'pix_copia_cola')) {
                    $table->text('pix_copia_cola')->nullable()->after('qr_code_base64')
                        ->comment('Chave/Payload copia-e-cola do Pix');
                }
                if (!Schema::hasColumn('financeiro.cobrancas_gateway', 'expira_em')) {
                    $table->timestamp('expira_em')->nullable()->after('pix_copia_cola')
                        ->comment('Data e hora limite de expiração da cobrança/link');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('financeiro.contratos')) {
            Schema::table('financeiro.contratos', function (Blueprint $table) {
                $table->dropColumn([
                    'forma_pagamento',
                    'cartao_bandeira',
                    'cartao_nsu',
                    'cartao_tipo',
                    'payment_link_url',
                    'payment_link_id',
                    'quitado_em',
                ]);
            });
        }

        if (Schema::hasTable('financeiro.cobrancas_gateway')) {
            Schema::table('financeiro.cobrancas_gateway', function (Blueprint $table) {
                $table->dropColumn([
                    'tipo_cobranca',
                    'qr_code_base64',
                    'pix_copia_cola',
                    'expira_em',
                ]);
            });
        }
    }
};
