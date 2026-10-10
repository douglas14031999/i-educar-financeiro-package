<?php

namespace iEducar\Financeiro;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class FinanceiroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        // 1. Carregamento de Migrations e Seeds
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 2. Carregamento de Rotas Web e API
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // 3. Carregamento de Views com namespace 'financeiro::'
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'financeiro');

        // 4. Carregamento de Traduções / Linguagem
        if (is_dir(__DIR__ . '/../resources/lang')) {
            $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'financeiro');
        }

        // 5. Publicações para ambiente de produção
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/financeiro.php' => config_path('financeiro.php'),
            ], 'financeiro-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/financeiro'),
            ], 'financeiro-views');

            // Registro de Comandos Artisan
            $this->commands([
                Commands\ProcessarReguaCobrancaCommand::class,
                Commands\SincronizarCobrancasAsaasCommand::class,
                Commands\SincronizarMenusCommand::class,
            ]);
        }

        // 6. View Composer para integração visual nativa com layout/menus do i-Educar 2.10
        View::composer(['layout.menu', 'layout.topmenu', 'layout.default', 'financeiro::layouts.app'], function ($view) {
            $data = $view->getData();

            // Garantir que $menu esteja sempre definido
            if (!isset($data['menu']) || empty($data['menu'])) {
                $user = auth()->user();
                $menuList = collect();

                if ($user && class_exists(\App\Services\MenuCacheService::class)) {
                    try {
                        $menuList = app(\App\Services\MenuCacheService::class)->getMenuByUser($user);
                    } catch (\Throwable $e) {}
                }

                if (empty($menuList) || (is_object($menuList) && method_exists($menuList, 'isEmpty') && $menuList->isEmpty())) {
                    if ($user && class_exists(\App\Menu::class)) {
                        try {
                            $menuList = \App\Menu::user($user);
                        } catch (\Throwable $e) {}
                    }
                }

                if (empty($menuList) || (is_object($menuList) && method_exists($menuList, 'isEmpty') && $menuList->isEmpty())) {
                    if (class_exists(\App\Menu::class)) {
                        try {
                            $menuList = \App\Menu::roots();
                        } catch (\Throwable $e) {}
                    }
                }

                $view->with('menu', $menuList ?? collect());
            }

            // Garantir root, mainmenu, currentMenu e menuPaths
            if (empty($data['mainmenu']) || empty($data['root'])) {
                $path = '/' . ltrim(request()->path(), '/');
                if (empty($path) || strpos($path, '/financeiro') === 0 || strpos($path, 'financeiro') === 0) {
                    if (class_exists(\App\Menu::class)) {
                        $topmenu = \App\Menu::query()->where('link', $path)->first()
                            ?: \App\Menu::query()->where('link', 'like', "{$path}%")->first()
                            ?: \App\Menu::query()->where('process', \iEducar\Financeiro\Constants\ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ)->first()
                            ?: \App\Menu::query()->where('title', 'Financeiro')->whereNull('parent_id')->first();

                        if ($topmenu) {
                            $ancestors = method_exists(\App\Menu::class, 'getMenuAncestors')
                                ? \App\Menu::getMenuAncestors($topmenu)
                                : [];
                            $rootId = method_exists($topmenu, 'root') && $topmenu->root()
                                ? $topmenu->root()->getKey()
                                : $topmenu->getKey();

                            $view->with([
                                'mainmenu'    => $rootId,
                                'currentMenu' => $topmenu,
                                'menuPaths'   => $ancestors,
                                'root'        => $rootId,
                            ]);
                        }
                    }
                }
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Mescla arquivo de configuração
        $this->mergeConfigFrom(
            __DIR__ . '/../config/financeiro.php',
            'financeiro'
        );

        // Registro de Singletons e Gateways
        $this->app->singleton('financeiro.gateway', function ($app) {
            return new Gateways\GatewayManager($app);
        });
    }
}
