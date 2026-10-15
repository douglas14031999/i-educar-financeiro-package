<?php

use iEducar\Financeiro\Constants\ProcessosFinanceiro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissoesFinanceiroCorretivas extends Migration
{
    /**
     * Disable transaction so that individual errors do not cancel the whole migration.
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     *
     * BUG-010: Remove permissões financeiras concedidas indevidamente a todos os perfis
     * de usuário e restringe aos perfis administrativos autorizados.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('menus') || !Schema::hasTable('menu_tipo_usuario')) {
            return;
        }

        try {
            $processos = [
                ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ,
                ProcessosFinanceiro::MENU_DASHBOARD,
                ProcessosFinanceiro::MENU_RESPONSAVEIS,
                ProcessosFinanceiro::MENU_PLANOS,
                ProcessosFinanceiro::MENU_CONTRATOS,
                ProcessosFinanceiro::MENU_TITULOS,
                ProcessosFinanceiro::MENU_BAIXA_MANUAL,
                ProcessosFinanceiro::MENU_RENEGOCIACAO,
                ProcessosFinanceiro::MENU_CONTAS_PAGAR,
                ProcessosFinanceiro::MENU_RELATORIOS,
                ProcessosFinanceiro::MENU_GATEWAY_CONFIG,
            ];

            $menuIds = DB::table('menus')
                ->whereIn('process', $processos)
                ->orWhereIn('old', $processos)
                ->pluck('id')
                ->toArray();

            if (empty($menuIds)) {
                return;
            }

            // Perfis autorizados padrão: Administrador (1) e Institucional
            $perfisPermitidos = config('financeiro.perfis_padrao', [1]);

            // 1. Remove permissões de usuários não-administrativos nos menus do módulo financeiro
            DB::table('menu_tipo_usuario')
                ->whereIn('menu_id', $menuIds)
                ->whereNotIn('tipo_usuario_id', $perfisPermitidos)
                ->delete();

            // 2. Garante que os perfis permitidos possuam acesso total
            foreach ($menuIds as $menuId) {
                foreach ($perfisPermitidos as $tipoUsuarioId) {
                    $existe = DB::table('menu_tipo_usuario')
                        ->where('menu_id', $menuId)
                        ->where('tipo_usuario_id', $tipoUsuarioId)
                        ->first();

                    if ($existe) {
                        DB::table('menu_tipo_usuario')
                            ->where('menu_id', $menuId)
                            ->where('tipo_usuario_id', $tipoUsuarioId)
                            ->update([
                                'visualiza' => 1,
                                'cadastra'  => 1,
                                'exclui'    => 1,
                            ]);
                    } else {
                        DB::table('menu_tipo_usuario')->insert([
                            'menu_id'         => $menuId,
                            'tipo_usuario_id' => $tipoUsuarioId,
                            'visualiza'       => 1,
                            'cadastra'        => 1,
                            'exclui'          => 1,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log or ignore if tables are not fully populated in host test environment
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No-op for security rollback
    }
}
