<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePagamentosAndWebhookEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Atualizações na tabela de pagamentos
        if (Schema::hasTable('financeiro.pagamentos')) {
            Schema::table('financeiro.pagamentos', function (Blueprint $table) {
                if (!Schema::hasColumn('financeiro.pagamentos', 'troco')) {
                    $table->decimal('troco', 14, 2)->default(0.00)->after('valor_credito_gerado');
                }
                if (!Schema::hasColumn('financeiro.pagamentos', 'idempotency_key')) {
                    $table->string('idempotency_key', 64)->nullable()->unique()->after('comprovante_hash');
                }
                if (!Schema::hasColumn('financeiro.pagamentos', 'lote_baixa_uuid')) {
                    $table->string('lote_baixa_uuid', 36)->nullable()->index()->after('idempotency_key');
                }
            });
        }

        // 2. Atualizações na tabela de eventos de webhook para retentativas e recuperação
        if (Schema::hasTable('financeiro.webhook_events')) {
            Schema::table('financeiro.webhook_events', function (Blueprint $table) {
                if (!Schema::hasColumn('financeiro.webhook_events', 'tentativas')) {
                    $table->integer('tentativas')->default(0)->after('processado');
                }
                if (!Schema::hasColumn('financeiro.webhook_events', 'proxima_tentativa_em')) {
                    $table->timestamp('proxima_tentativa_em')->nullable()->after('tentativas');
                }
                if (!Schema::hasColumn('financeiro.webhook_events', 'requer_acao')) {
                    $table->boolean('requer_acao')->default(false)->after('proxima_tentativa_em');
                }
                if (!Schema::hasColumn('financeiro.webhook_events', 'motivo_acao')) {
                    $table->text('motivo_acao')->nullable()->after('requer_acao');
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
        if (Schema::hasTable('financeiro.pagamentos')) {
            Schema::table('financeiro.pagamentos', function (Blueprint $table) {
                $table->dropColumn(['troco', 'idempotency_key', 'lote_baixa_uuid']);
            });
        }

        if (Schema::hasTable('financeiro.webhook_events')) {
            Schema::table('financeiro.webhook_events', function (Blueprint $table) {
                $table->dropColumn(['tentativas', 'proxima_tentativa_em', 'requer_acao', 'motivo_acao']);
            });
        }
    }
}
