<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Fase5IndicesEChecks extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isPgsql = DB::getDriverName() === 'pgsql';

        // 1. Criação de índices de performance adicionais (BUG-038 / M08)
        if ($isPgsql) {
            DB::statement("CREATE INDEX IF NOT EXISTS idx_titulos_status_vencimento ON financeiro.titulos (status, data_vencimento);");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_titulos_resp_status ON financeiro.titulos (responsavel_financeiro_id, status);");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_titulos_aluno_id ON financeiro.titulos (aluno_id);");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_titulos_contrato_parcela ON financeiro.titulos (contrato_id, numero_parcela);");
            if (Schema::hasTable('financeiro.webhook_events')) {
                DB::statement("CREATE INDEX IF NOT EXISTS idx_webhook_proc_created ON financeiro.webhook_events (processado, created_at);");
            }
        } elseif (Schema::hasTable('financeiro.titulos')) {
            try {
                Schema::table('financeiro.titulos', function (Blueprint $table) {
                    $table->index(['status', 'data_vencimento'], 'idx_titulos_status_vencimento');
                    $table->index(['responsavel_financeiro_id', 'status'], 'idx_titulos_resp_status');
                    $table->index('aluno_id', 'idx_titulos_aluno_id');
                    $table->index(['contrato_id', 'numero_parcela'], 'idx_titulos_contrato_parcela');
                });
            } catch (\Throwable $e) {}

            if (Schema::hasTable('financeiro.webhook_events')) {
                try {
                    Schema::table('financeiro.webhook_events', function (Blueprint $table) {
                        $table->index(['processado', 'created_at'], 'idx_webhook_proc_created');
                    });
                } catch (\Throwable $e) {}
            }
        }

        // 2. Constraints de integridade (CHECKs) no PostgreSQL
        if ($isPgsql) {
            DB::statement("
                DO $$
                BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_titulos_saldo_positivo') THEN
                        ALTER TABLE financeiro.titulos ADD CONSTRAINT chk_titulos_saldo_positivo CHECK (valor_saldo >= 0);
                    END IF;
                    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_titulos_nominal_positivo') THEN
                        ALTER TABLE financeiro.titulos ADD CONSTRAINT chk_titulos_nominal_positivo CHECK (valor_nominal > 0);
                    END IF;
                    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_aluno_resp_rateio') THEN
                        ALTER TABLE financeiro.aluno_responsavel ADD CONSTRAINT chk_aluno_resp_rateio CHECK (percentual_rateio BETWEEN 0.01 AND 100.00);
                    END IF;
                END $$;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE financeiro.titulos DROP CONSTRAINT IF EXISTS chk_titulos_saldo_positivo;");
            DB::statement("ALTER TABLE financeiro.titulos DROP CONSTRAINT IF EXISTS chk_titulos_nominal_positivo;");
            DB::statement("ALTER TABLE financeiro.aluno_responsavel DROP CONSTRAINT IF EXISTS chk_aluno_resp_rateio;");
        }
    }
}
