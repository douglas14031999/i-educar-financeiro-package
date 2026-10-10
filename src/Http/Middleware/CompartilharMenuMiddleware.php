<?php

namespace iEducar\Financeiro\Http\Middleware;

use Closure;
use iEducar\Financeiro\Constants\ProcessosFinanceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class CompartilharMenuMiddleware
{
    /**
     * Trata a requisição garantindo que todas as variáveis de layout do i-Educar estejam presentes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $menuList = collect();

        // 1. Carrega a árvore de menus do i-Educar para o usuário autenticado
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

        // 2. Localiza o menu raiz do Financeiro e o item ativo
        $topmenu = null;
        $rootId = null;
        $ancestors = [];

        if (class_exists(\App\Menu::class)) {
            $path = '/' . ltrim($request->path(), '/');

            $topmenu = \App\Menu::where('link', $path)->first()
                ?: \App\Menu::where('link', 'like', "{$path}%")->first()
                ?: \App\Menu::where('process', ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ)->first()
                ?: \App\Menu::where('title', 'Financeiro')->whereNull('parent_id')->first();

            if ($topmenu) {
                $rootId = method_exists($topmenu, 'root') && $topmenu->root()
                    ? $topmenu->root()->getKey()
                    : $topmenu->getKey();

                $ancestors = method_exists(\App\Menu::class, 'getMenuAncestors')
                    ? \App\Menu::getMenuAncestors($topmenu)
                    : [];
            }
        }

        // 3. Compartilha as variáveis exigidas por layout.menu, layout.topmenu e layout.default
        View::share([
            'menu'        => $menuList ?? collect(),
            'root'        => $rootId,
            'mainmenu'    => $rootId,
            'currentMenu' => $topmenu,
            'menuPaths'   => $ancestors,
        ]);

        return $next($request);
    }
}
