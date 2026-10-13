<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A28 — Adiciona escola_id à tabela financeiro.contratos.
 *
 * A coluna foi implementada nos models e serviços mas estava ausente
 * na migration original (2026_01_01_000005). Esta migration corrige
 * o schema existente de forma idempotente.
 */
class AddEscolaIdToContratos extends Migration
{
    public function up(): void
    {
        // Idempotente: só adiciona se a coluna ainda não existir
        if (!$this->columnExists('financeiro.contratos', 'escola_id')) {
            Schema::table('financeiro.contratos', function (Blueprint $table) {
                // nullable pois contratos antigos não têm o valor
                $table->integer('escola_id')->nullable()->index()->after('ano_letivo');
            });
        }
    }

    public function down(): void
    {
        if ($this->columnExists('financeiro.contratos', 'escola_id')) {
            Schema::table('financeiro.contratos', function (Blueprint $table) {
                $table->dropColumn('escola_id');
            });
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        [$schema, $tbl] = explode('.', $table);

        return (bool) DB::selectOne(
            "SELECT 1
               FROM information_schema.columns
              WHERE table_schema = ?
                AND table_name   = ?
                AND column_name  = ?",
            [$schema, $tbl, $column]
        );
    }
}
