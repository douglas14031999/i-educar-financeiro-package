<?php

namespace iEducar\Financeiro\Commands;

use iEducar\Financeiro\Constants\ProcessosFinanceiro;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SincronizarMenusCommand extends Command
{
    /**
     * O nome e assinatura do comando no terminal.
     *
     * @var string
     */
    protected $signature = 'financeiro:sincronizar-menus';

    /**
     * A descrição do comando console.
     *
     * @var string
     */
    protected $description = 'Sincroniza os menus do Módulo Financeiro na barra lateral e concede permissões a todos os perfis do i-Educar';

    /**
     * Executa o comando console.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('================================================================');
        $this->info(' Sincronizador de Menus e Permissões do Módulo Financeiro');
        $this->info('================================================================');

        if (!Schema::hasTable('menus')) {
            $this->error('[-] Tabela "menus" não encontrada no banco de dados.');
            return 1;
        }

        $colunasMenus = Schema::getColumnListing('menus');
        $this->line('[*] Colunas detectadas na tabela menus: ' . implode(', ', $colunasMenus));

        // 1. Menu Raiz Financeiro
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
            $rootData['type'] = 1; // 1 = Menu Principal / Barra Lateral
        }
        if (in_array('active', $colunasMenus)) {
            $rootData['active'] = true;
        }

        $rootMenu = DB::table('menus')->where('old', ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ)->first()
            ?: DB::table('menus')->where('title', 'Financeiro')->whereNull('parent_id')->first();

        if ($rootMenu) {
            $rootId = $rootMenu->id;
            $updateData = $rootData;
            if (in_array('updated_at', $colunasMenus)) {
                $updateData['updated_at'] = now();
            }
            DB::table('menus')->where('id', $rootId)->update($updateData);
            $this->info("[✓] Menu Principal 'Financeiro' atualizado com sucesso (ID: {$rootId}).");
        } else {
            if (in_array('created_at', $colunasMenus)) {
                $rootData['created_at'] = now();
            }
            if (in_array('updated_at', $colunasMenus)) {
                $rootData['updated_at'] = now();
            }
            $rootId = DB::table('menus')->insertGetId($rootData);
            $this->info("[✓] Menu Principal 'Financeiro' criado com sucesso (ID: {$rootId}).");
        }

        // 2. Categorias Principais Nativas do i-Educar
        $categoriasDef = [
            'Cadastros'     => 2,
            'Movimentações' => 3,
            'Lançamentos'   => 4,
            'Relatórios'    => 5,
            'Configurações' => 6,
        ];

        $todosMenuIds = [$rootId];
        $catIds = [];

        foreach ($categoriasDef as $catTitle => $order) {
            $catData = [
                'parent_id' => $rootId,
                'title'     => $catTitle,
                'order'     => $order,
                'link'      => null,
            ];
            if (in_array('type', $colunasMenus)) {
                $catData['type'] = 1; // 1 = Categoria / Pasta
            }
            if (in_array('active', $colunasMenus)) {
                $catData['active'] = true;
            }

            $existente = DB::table('menus')->where('parent_id', $rootId)
                ->where(function($q) use ($catTitle) {
                    $q->where('title', $catTitle)
                      ->orWhere('title', str_replace(['ç', 'õ', 'ã'], ['c', 'o', 'a'], $catTitle));
                })->first();

            if ($existente) {
                $catId = $existente->id;
                $updateCat = $catData;
                if (in_array('updated_at', $colunasMenus)) {
                    $updateCat['updated_at'] = now();
                }
                DB::table('menus')->where('id', $catId)->update($updateCat);
            } else {
                if (in_array('created_at', $colunasMenus)) {
                    $catData['created_at'] = now();
                }
                if (in_array('updated_at', $colunasMenus)) {
                    $catData['updated_at'] = now();
                }
                $catId = DB::table('menus')->insertGetId($catData);
            }

            $catIds[$catTitle] = $catId;
            $todosMenuIds[] = $catId;
            $this->line("  ↳ Categoria [{$catTitle}] registrada (ID: {$catId}).");
        }

        // 3. Submenus e Itens de Ação vinculados às suas Categorias
        $submenusDef = [
            // Raiz: Painel Geral
            [
                'old'         => ProcessosFinanceiro::MENU_DASHBOARD,
                'title'       => 'Painel Geral',
                'description' => 'Visão geral e faturamento',
                'link'        => '/financeiro',
                'order'       => 1,
                'parent'      => 'root',
                'icon'        => 'fa-dashboard',
            ],
            // Categoria: Cadastros
            [
                'old'         => ProcessosFinanceiro::MENU_RESPONSAVEIS,
                'title'       => 'Responsáveis Financeiros',
                'description' => 'Cadastro e rateio de responsáveis',
                'link'        => '/financeiro/responsaveis',
                'order'       => 1,
                'parent'      => 'Cadastros',
                'icon'        => 'fa-users',
            ],
            [
                'old'         => ProcessosFinanceiro::MENU_PLANOS,
                'title'       => 'Planos de Cobrança',
                'description' => 'Tabelas de anuidade e parcelas',
                'link'        => '/financeiro/planos',
                'order'       => 2,
                'parent'      => 'Cadastros',
                'icon'        => 'fa-tags',
            ],
            [
                'old'         => ProcessosFinanceiro::MENU_CONTRATOS,
                'title'       => 'Contratos',
                'description' => 'Contratos financeiros por matrícula',
                'link'        => '/financeiro/contratos',
                'order'       => 3,
                'parent'      => 'Cadastros',
                'icon'        => 'fa-file-text-o',
            ],
            // Categoria: Movimentações
            [
                'old'         => ProcessosFinanceiro::MENU_TITULOS,
                'title'       => 'Mensalidades e Boletos',
                'description' => 'Gestão de títulos e parcelas Asaas',
                'link'        => '/financeiro/titulos',
                'order'       => 1,
                'parent'      => 'Movimentações',
                'icon'        => 'fa-barcode',
            ],
            [
                'old'         => ProcessosFinanceiro::MENU_BAIXA_MANUAL,
                'title'       => 'Baixa de Balcão (Caixa)',
                'description' => 'Recebimento presencial na secretaria',
                'link'        => '/financeiro/baixas/novo',
                'order'       => 2,
                'parent'      => 'Movimentações',
                'icon'        => 'fa-money',
            ],
            [
                'old'         => ProcessosFinanceiro::MENU_RENEGOCIACAO,
                'title'       => 'Renegociação de Dívidas',
                'description' => 'Acordos financeiros e parcelamento',
                'link'        => '/financeiro/renegociacao',
                'order'       => 3,
                'parent'      => 'Movimentações',
                'icon'        => 'fa-handshake-o',
            ],
            // Categoria: Lançamentos
            [
                'old'         => ProcessosFinanceiro::MENU_CONTAS_PAGAR,
                'title'       => 'Contas a Pagar',
                'description' => 'Despesas operacionais e fornecedores',
                'link'        => '/financeiro/contas-pagar',
                'order'       => 1,
                'parent'      => 'Lançamentos',
                'icon'        => 'fa-credit-card',
            ],
            // Categoria: Relatórios
            [
                'old'         => ProcessosFinanceiro::MENU_RELATORIOS,
                'title'       => 'Relatórios Gerenciais',
                'description' => 'Inadimplência, DRE, IRPF e Quitação',
                'link'        => '/financeiro/relatorios',
                'order'       => 1,
                'parent'      => 'Relatórios',
                'icon'        => 'fa-bar-chart',
            ],
            // Categoria: Configurações
            [
                'old'         => ProcessosFinanceiro::MENU_GATEWAY_CONFIG,
                'title'       => 'Configurações do Asaas',
                'description' => 'Chaves de API, webhooks e régua',
                'link'        => '/financeiro/configuracoes/gateway',
                'order'       => 1,
                'parent'      => 'Configurações',
                'icon'        => 'fa-cogs',
            ],
        ];

        foreach ($submenusDef as $sub) {
            $parentId = ($sub['parent'] === 'root') ? $rootId : ($catIds[$sub['parent']] ?? $rootId);

            $subData = [
                'parent_id'   => $parentId,
                'parent_old'  => ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ,
                'title'       => $sub['title'],
                'description' => $sub['description'],
                'link'        => $sub['link'],
                'order'       => $sub['order'],
                'process'     => $sub['old'],
                'old'         => $sub['old'],
            ];

            if (in_array('type', $colunasMenus)) {
                $subData['type'] = 2; // 2 = Submenu / Link interno
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
                $updateSub = $subData;
                if (in_array('updated_at', $colunasMenus)) {
                    $updateSub['updated_at'] = now();
                }
                DB::table('menus')->where('id', $subId)->update($updateSub);
            } else {
                if (in_array('created_at', $colunasMenus)) {
                    $subData['created_at'] = now();
                }
                if (in_array('updated_at', $colunasMenus)) {
                    $subData['updated_at'] = now();
                }
                $subId = DB::table('menus')->insertGetId($subData);
            }

            $todosMenuIds[] = $subId;
            $this->line("  ↳ Submenu [{$sub['title']}] registrado (ID: {$subId}).");
        }

        // 3. Obter todos os IDs de tipos de usuários disponíveis
        $tiposUsuario = collect();

        // Tentativa A: pegar de menu_tipo_usuario já populada
        try {
            if (Schema::hasTable('menu_tipo_usuario')) {
                $existentesNaTabela = DB::table('menu_tipo_usuario')->pluck('tipo_usuario_id')->unique();
                $tiposUsuario = $tiposUsuario->merge($existentesNaTabela);
            }
        } catch (\Throwable $e) {}

        // Tentativa B: pmieducar.tipo_usuario
        try {
            $fromPmi = DB::table('pmieducar.tipo_usuario')->pluck('cod_tipo_usuario');
            $tiposUsuario = $tiposUsuario->merge($fromPmi);
        } catch (\Throwable $e) {}

        // Tentativa C: tipo_usuario simples
        try {
            if (Schema::hasTable('tipo_usuario')) {
                $fromSimples = DB::table('tipo_usuario')->pluck('id');
                $tiposUsuario = $tiposUsuario->merge($fromSimples);
            }
        } catch (\Throwable $e) {}

        $tiposUsuario = $tiposUsuario->filter()->unique()->values();

        $this->info("[*] Perfis de usuário encontrados: " . ($tiposUsuario->isEmpty() ? 'Nenhum' : $tiposUsuario->implode(', ')));

        // 4. Inserir permissões em menu_tipo_usuario
        $tabelasPermissoes = ['menu_tipo_usuario', 'public.menu_tipo_usuario', 'pmieducar.menu_tipo_usuario'];
        $permissoesInseridas = 0;

        foreach ($tabelasPermissoes as $tabela) {
            try {
                if (Schema::hasTable($tabela)) {
                    $colsPerm = Schema::getColumnListing($tabela);
                    $colTipoUser = in_array('ref_cod_tipo_usuario', $colsPerm) ? 'ref_cod_tipo_usuario' : 'tipo_usuario_id';

                    foreach ($tiposUsuario as $tipoId) {
                        foreach ($todosMenuIds as $mId) {
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
                                $permissoesInseridas++;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Continua para próxima tabela caso não exista
            }
        }

        $this->info("[✓] Permissões vinculadas com sucesso ({$permissoesInseridas} novos vínculos criados).");

        // 5. Limpeza de Caches
        $this->info('[*] Limpando caches do sistema...');
        try {
            Cache::flush();
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            $this->info('[✓] Caches limpos com sucesso.');
        } catch (\Throwable $e) {
            $this->warn('[!] Aviso ao limpar caches: ' . $e->getMessage());
        }

        $this->info('================================================================');
        $this->info(' CONCLUÍDO! O Módulo Financeiro está ativo no i-Educar.');
        $this->warn(' IMPORTANTE: Faça LOGOUT (Sair) e LOGIN novamente no i-Educar');
        $this->warn(' para recarregar o menu na sua sessão atual do navegador!');
        $this->info('================================================================');

        return 0;
    }
}
