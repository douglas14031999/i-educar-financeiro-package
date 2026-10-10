<?php

use iEducar\Financeiro\Constants\ProcessosFinanceiro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AtualizarMenusEPermissoesFinanceiro extends Migration
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
            $colunasMenus = Schema::getColumnListing('menus');

            // 1. Menu Raiz
            $rootData = [
                'title'       => 'Financeiro',
                'description' => 'Módulo Financeiro Escolar',
                'link'        => '/financeiro',
                'order'       => 9,
                'process'     => ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ,
                'old'         => ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ,
                'parent_id'   => null,
                'parent_old'  => null,
            ];

            if (in_array('icon', $colunasMenus)) {
                $rootData['icon'] = 'fa-usd';
            }
            if (in_array('type', $colunasMenus)) {
                $rootData['type'] = 1; // 1 = Menu Principal lateral
            }
            if (in_array('active', $colunasMenus)) {
                $rootData['active'] = true;
            }

            $rootMenu = DB::table('menus')->where('old', ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ)->first()
                ?: DB::table('menus')->where('title', 'Financeiro')->whereNull('parent_id')->first();

            if ($rootMenu) {
                $rootId = $rootMenu->id;
                DB::table('menus')->where('id', $rootId)->update($rootData);
            } else {
                $rootId = DB::table('menus')->insertGetId($rootData);
            }

            // 2. Submenus
            $submenus = [
                [
                    'old'         => ProcessosFinanceiro::MENU_DASHBOARD,
                    'title'       => 'Painel Geral',
                    'description' => 'Visão geral e faturamento',
                    'link'        => '/financeiro',
                    'order'       => 1,
                    'icon'        => 'fa-dashboard',
                ],
                [
                    'old'         => ProcessosFinanceiro::MENU_RESPONSAVEIS,
                    'title'       => 'Responsáveis Financeiros',
                    'description' => 'Cadastro e rateio de responsáveis',
                    'link'        => '/financeiro/responsaveis',
                    'order'       => 2,
                    'icon'        => 'fa-users',
                ],
                [
                    'old'         => ProcessosFinanceiro::MENU_PLANOS,
                    'title'       => 'Planos de Cobrança',
                    'description' => 'Tabelas de anuidade e parcelas',
                    'link'        => '/financeiro/planos',
                    'order'       => 3,
                    'icon'        => 'fa-tags',
                ],
                [
                    'old'         => ProcessosFinanceiro::MENU_CONTRATOS,
                    'title'       => 'Contratos',
                    'description' => 'Contratos financeiros por matrícula',
                    'link'        => '/financeiro/contratos',
                    'order'       => 4,
                    'icon'        => 'fa-file-text-o',
                ],
                [
                    'old'         => ProcessosFinanceiro::MENU_TITULOS,
                    'title'       => 'Mensalidades e Boletos',
                    'description' => 'Gestão de títulos e parcelas Asaas',
                    'link'        => '/financeiro/titulos',
                    'order'       => 5,
                    'icon'        => 'fa-barcode',
                ],
                [
                    'old'         => ProcessosFinanceiro::MENU_BAIXA_MANUAL,
                    'title'       => 'Baixa de Balcão (Caixa)',
                    'description' => 'Recebimento presencial na secretaria',
                    'link'        => '/financeiro/baixas/novo',
                    'order'       => 6,
                    'icon'        => 'fa-money',
                ],
                [
                    'old'         => ProcessosFinanceiro::MENU_RENEGOCIACAO,
                    'title'       => 'Renegociação de Dívidas',
                    'description' => 'Acordos financeiros e parcelamento',
                    'link'        => '/financeiro/renegociacao',
                    'order'       => 7,
                    'icon'        => 'fa-handshake-o',
                ],
                [
                    'old'         => ProcessosFinanceiro::MENU_CONTAS_PAGAR,
                    'title'       => 'Contas a Pagar',
                    'description' => 'Despesas operacionais e fornecedores',
                    'link'        => '/financeiro/contas-pagar',
                    'order'       => 8,
                    'icon'        => 'fa-credit-card',
                ],
                [
                    'old'         => ProcessosFinanceiro::MENU_RELATORIOS,
                    'title'       => 'Relatórios Gerenciais',
                    'description' => 'Inadimplência, DRE, IRPF e Quitação',
                    'link'        => '/financeiro/relatorios',
                    'order'       => 9,
                    'icon'        => 'fa-bar-chart',
                ],
                [
                    'old'         => ProcessosFinanceiro::MENU_GATEWAY_CONFIG,
                    'title'       => 'Configurações do Asaas',
                    'description' => 'Chaves de API, webhooks e régua',
                    'link'        => '/financeiro/configuracoes/gateway',
                    'order'       => 10,
                    'icon'        => 'fa-cogs',
                ],
            ];

            $menuIds = [$rootId];

            foreach ($submenus as $sub) {
                $subData = [
                    'parent_id'   => $rootId,
                    'parent_old'  => ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ,
                    'title'       => $sub['title'],
                    'description' => $sub['description'],
                    'link'        => $sub['link'],
                    'order'       => $sub['order'],
                    'process'     => $sub['old'],
                    'old'         => $sub['old'],
                ];

                if (in_array('type', $colunasMenus)) {
                    $subData['type'] = 2;
                }
                if (in_array('active', $colunasMenus)) {
                    $subData['active'] = true;
                }
                if (in_array('icon', $colunasMenus)) {
                    $subData['icon'] = $sub['icon'];
                }

                $existente = DB::table('menus')->where('old', $sub['old'])->first()
                    ?: DB::table('menus')->where('link', $sub['link'])->first();

                if ($existente) {
                    $subId = $existente->id;
                    DB::table('menus')->where('id', $subId)->update($subData);
                } else {
                    $subId = DB::table('menus')->insertGetId($subData);
                }

                $menuIds[] = $subId;
            }

            // 3. Vincular Permissões a Todos os Perfis
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

            $tabelasPermissoes = ['menu_tipo_usuario', 'public.menu_tipo_usuario', 'pmieducar.menu_tipo_usuario'];
            foreach ($tabelasPermissoes as $tabela) {
                try {
                    if (Schema::hasTable($tabela)) {
                        $colsPerm = Schema::getColumnListing($tabela);
                        $colTipoUser = in_array('ref_cod_tipo_usuario', $colsPerm) ? 'ref_cod_tipo_usuario' : 'tipo_usuario_id';

                        foreach ($tiposUsuario as $tipoId) {
                            foreach ($menuIds as $mId) {
                                $existe = DB::table($tabela)
                                    ->where('menu_id', $mId)
                                    ->where($colTipoUser, $tipoId)
                                    ->exists();

                                if (!$existe) {
                                    $permData = [
                                        'menu_id'    => $mId,
                                        $colTipoUser => $tipoId,
                                    ];
                                    if (in_array('cadastra', $colsPerm))  $permData['cadastra']  = 1;
                                    if (in_array('visualiza', $colsPerm)) $permData['visualiza'] = 1;
                                    if (in_array('exclui', $colsPerm))    $permData['exclui']    = 1;

                                    DB::table($tabela)->insert($permData);
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }

            // 4. Limpar cache
            try {
                Cache::flush();
            } catch (\Throwable $e) {}

        } catch (\Throwable $e) {
            // Em caso de falha não bloqueia execução
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Keep menus intact
    }
}
