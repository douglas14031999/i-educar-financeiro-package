<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RelaxNotNullResponsaveisAndLancamentos extends Migration
{
    /**
     * Run the migrations.
     *
     * BUG-006: escola_id em financeiro.lancamentos pode ser nulo (despesas gerais da rede)
     * BUG-007: dados de contato e endereço em financeiro.responsaveis_financeiros tornam-se nullable
     * para permitir cadastro rápido a partir do i-Educar sem falha de integridade.
     *
     * @return void
     */
    public function up()
    {
        // 1. Torna colunas opcionais de responsaveis_financeiros em NULLABLE
        if (Schema::hasTable('financeiro.responsaveis_financeiros')) {
            $colunas = [
                'email',
                'telefone_celular',
                'cep',
                'logradouro',
                'numero',
                'bairro',
                'cidade',
                'estado',
            ];

            foreach ($colunas as $coluna) {
                if (Schema::hasColumn('financeiro.responsaveis_financeiros', $coluna)) {
                    try {
                        DB::statement("ALTER TABLE financeiro.responsaveis_financeiros ALTER COLUMN {$coluna} DROP NOT NULL;");
                    } catch (\Throwable $e) {
                        // Ignora se já for nullable
                    }
                }
            }
        }

        // 2. Torna escola_id em financeiro.lancamentos em NULLABLE
        if (Schema::hasTable('financeiro.lancamentos') && Schema::hasColumn('financeiro.lancamentos', 'escola_id')) {
            try {
                DB::statement("ALTER TABLE financeiro.lancamentos ALTER COLUMN escola_id DROP NOT NULL;");
            } catch (\Throwable $e) {
                // Ignora se já for nullable
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Operação de relaxamento não necessita reversão destrutiva
    }
}
