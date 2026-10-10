<?php

use iEducar\Financeiro\Constants\ProcessosFinanceiro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFinanceiroMenus extends Migration
{
    /**
     * Disable transaction so that individual errors do not cancel the whole migration.
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        try {
            // 1. Menu Raiz: "Financeiro"
            $rootMenu = DB::table('menus')->where('old', ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ)->first();

            $rootId = null;
            $rootPayload = [
                'title'       => 'Financeiro',
                'description' => 'Módulo Financeiro Escolar',
                'link'        => '/financeiro',
                'order'       => 9,
                'process'     => ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ,
                'icon'        => 'fa-usd',
                'type'        => 1,
                'active'      => true,
            ];

            if ($rootMenu) {
                $rootId = $rootMenu->id;
                DB::table('menus')->where('id', $rootId)->update($rootPayload);
            } else {
                $rootId = DB::table('menus')->insertGetId(array_merge($rootPayload, [
                    'old'        => ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ,
                    'parent_id'  => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            // 2. Submenus do Módulo Financeiro
            $submenus = [
                [
                    'old' => ProcessosFinanceiro::MENU_DASHBOARD,
                    'title' => 'Painel Geral',
                    'description' => 'Indicadores Financeiros',
                    'link' => '/financeiro',
                    'order' => 1,
                    'process' => ProcessosFinanceiro::MENU_DASHBOARD,
                ],
                [
                    'old' => ProcessosFinanceiro::MENU_RESPONSAVEIS,
                    'title' => 'Responsáveis Financeiros',
                    'description' => 'Cadastro e rateio de responsáveis',
                    'link' => '/financeiro/responsaveis',
                    'order' => 2,
                    'process' => ProcessosFinanceiro::MENU_RESPONSAVEIS,
                ],
                [
                    'old' => ProcessosFinanceiro::MENU_PLANOS,
                    'title' => 'Planos de Cobrança',
                    'description' => 'Tabelas de anuidade e parcelas',
                    'link' => '/financeiro/planos',
                    'order' => 3,
                    'process' => ProcessosFinanceiro::MENU_PLANOS,
                ],
                [
                    'old' => ProcessosFinanceiro::MENU_CONTRATOS,
                    'title' => 'Contratos',
                    'description' => 'Contratos por matrícula escolar',
                    'link' => '/financeiro/contratos',
                    'order' => 4,
                    'process' => ProcessosFinanceiro::MENU_CONTRATOS,
                ],
                [
                    'old' => ProcessosFinanceiro::MENU_TITULOS,
                    'title' => 'Mensalidades e Boletos',
                    'description' => 'Gestão de títulos e parcelas Asaas',
                    'link' => '/financeiro/titulos',
                    'order' => 5,
                    'process' => ProcessosFinanceiro::MENU_TITULOS,
                ],
                [
                    'old' => ProcessosFinanceiro::MENU_BAIXA_MANUAL,
                    'title' => 'Baixa de Balcão (Caixa)',
                    'description' => 'Recebimento presencial na secretaria',
                    'link' => '/financeiro/baixas/novo',
                    'order' => 6,
                    'process' => ProcessosFinanceiro::MENU_BAIXA_MANUAL,
                ],
                [
                    'old' => ProcessosFinanceiro::MENU_RENEGOCIACAO,
                    'title' => 'Renegociação de Dívidas',
                    'description' => 'Acordos financeiros e parcelamento',
                    'link' => '/financeiro/renegociacao',
                    'order' => 7,
                    'process' => ProcessosFinanceiro::MENU_RENEGOCIACAO,
                ],
                [
                    'old' => ProcessosFinanceiro::MENU_CONTAS_PAGAR,
                    'title' => 'Contas a Pagar',
                    'description' => 'Despesas operacionais e caixas',
                    'link' => '/financeiro/contas-pagar',
                    'order' => 8,
                    'process' => ProcessosFinanceiro::MENU_CONTAS_PAGAR,
                ],
                [
                    'old' => ProcessosFinanceiro::MENU_RELATORIOS,
                    'title' => 'Relatórios Gerenciais',
                    'description' => 'Inadimplência, DRE, IRPF e Lei 12.007',
                    'link' => '/financeiro/relatorios',
                    'order' => 9,
                    'process' => ProcessosFinanceiro::MENU_RELATORIOS,
                ],
                [
                    'old' => ProcessosFinanceiro::MENU_GATEWAY_CONFIG,
                    'title' => 'Configurações do Asaas',
                    'description' => 'Chaves de API e régua de cobrança',
                    'link' => '/financeiro/configuracoes/gateway',
                    'order' => 10,
                    'process' => ProcessosFinanceiro::MENU_GATEWAY_CONFIG,
                ],
            ];

            $menuIdsCriados = [$rootId];

            foreach ($submenus as $sub) {
                $subData = [
                    'parent_id'   => $rootId,
                    'parent_old'  => ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ,
                    'title'       => $sub['title'],
                    'description' => $sub['description'],
                    'link'        => $sub['link'],
                    'order'       => $sub['order'],
                    'process'     => $sub['process'],
                    'old'         => $sub['old'],
                    'type'        => 2,
                    'active'      => true,
                ];

                $existente = DB::table('menus')->where('old', $sub['old'])->first();
                if ($existente) {
                    DB::table('menus')->where('id', $existente->id)->update($subData);
                    $menuIdsCriados[] = $existente->id;
                } else {
                    $newId = DB::table('menus')->insertGetId(array_merge($subData, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                    $menuIdsCriados[] = $newId;
                }
            }

            // 3. Atribuição de Permissões aos Tipos de Usuário
            $tiposUsuario = collect();
            try {
                if (Schema::hasTable('menu_tipo_usuario')) {
                    $tiposUsuario = $tiposUsuario->merge(DB::table('menu_tipo_usuario')->pluck('tipo_usuario_id')->unique());
                }
            } catch (\Throwable $e) {}

            try {
                $tiposUsuario = $tiposUsuario->merge(DB::table('pmieducar.tipo_usuario')->pluck('cod_tipo_usuario'));
            } catch (\Throwable $e) {}

            try {
                if (Schema::hasTable('tipo_usuario')) {
                    $tiposUsuario = $tiposUsuario->merge(DB::table('tipo_usuario')->pluck('id'));
                }
            } catch (\Throwable $e) {}

            $tiposUsuario = $tiposUsuario->filter()->unique()->values();

            $tipoUsuarioTables = ['menu_tipo_usuario', 'public.menu_tipo_usuario', 'pmieducar.menu_tipo_usuario'];
            foreach ($tipoUsuarioTables as $tabela) {
                try {
                    if (Schema::hasTable($tabela)) {
                        foreach ($tiposUsuario as $tipoId) {
                            foreach ($menuIdsCriados as $menuId) {
                                $existePermissao = DB::table($tabela)
                                    ->where('menu_id', $menuId)
                                    ->where('tipo_usuario_id', $tipoId)
                                    ->exists();

                                if (!$existePermissao) {
                                    DB::table($tabela)->insert([
                                        'menu_id'         => $menuId,
                                        'tipo_usuario_id' => $tipoId,
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            // Em ambientes sem as tabelas do i-Educar migradas ainda, não bloqueia o setup
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('menus')) {
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

            $menuIds = DB::table('menus')->whereIn('old', $processos)->pluck('id');

            foreach (['menu_tipo_usuario', 'pmieducar.menu_tipo_usuario'] as $tabela) {
                if (Schema::hasTable($tabela)) {
                    DB::table($tabela)->whereIn('menu_id', $menuIds)->delete();
                }
            }

            DB::table('menus')->whereIn('old', $processos)->delete();
        } catch (\Throwable $e) {
            // Ignore
        }
    }
}
