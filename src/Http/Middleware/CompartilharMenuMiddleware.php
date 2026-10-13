<?php

namespace iEducar\Financeiro\Http\Middleware;

use Closure;
use iEducar\Financeiro\Constants\ProcessosFinanceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

/**
 * A31 — Consolida a montagem do menu em um único lugar.
 * Antes estava repetido em 3 locais com fallbacks silenciosos distintos.
 *
 * Estratégia de resolução de menu (em ordem de prioridade):
 *  1. MenuCacheService (i-Educar 2.9+)
 *  2. App\Menu::user() (versões anteriores)
 *  3. App\Menu::roots() (fallback público — nunca é o contexto do usuário real)
 *
 * O fallback 3 é explicitamente documentado: exibe itens de raiz,
 * não os itens autorizados do usuário. Deve ser removido em produção
 * assim que MenuCacheService estiver disponível no host.
 */
class CompartilharMenuMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user     = Auth::user();
        $menuList = collect();

        // A31 — Resolução de menu centralizada; não repetir esta lógica em controllers ou views
        $menuList = $this->resolverMenu($user);

        // Localiza item ativo e ascendentes para breadcrumb
        [$topmenu, $rootId, $ancestors] = $this->resolverItemAtivo($request);

        // Breadcrumb nativo do i-Educar
        $breadcrumb = $this->resolverBreadcrumb();

        View::share([
            'menu'        => $menuList,
            'root'        => $rootId,
            'mainmenu'    => $rootId,
            'currentMenu' => $topmenu,
            'menuPaths'   => $ancestors,
            'breadcrumb'  => $breadcrumb,
            'title'       => 'Módulo Financeiro',
        ]);

        return $next($request);
    }

    /**
     * Resolve a árvore de menus do usuário em ordem de prioridade.
     * A31 — Método único; elimina triplicação de código de menu.
     */
    private function resolverMenu($user): \Illuminate\Support\Collection
    {
        // 1. MenuCacheService — i-Educar 2.9+ (preferencial)
        if ($user && class_exists(\App\Services\MenuCacheService::class)) {
            try {
                $menu = app(\App\Services\MenuCacheService::class)->getMenuByUser($user);
                if ($menu && (!method_exists($menu, 'isEmpty') || !$menu->isEmpty())) {
                    return collect($menu);
                }
            } catch (\Throwable $e) {
                // Não silencia completamente: loga para diagnóstico sem quebrar a página
                \Illuminate\Support\Facades\Log::debug('MenuCacheService indisponível: ' . $e->getMessage());
            }
        }

        // 2. App\Menu::user() — versões anteriores do i-Educar
        if ($user && class_exists(\App\Menu::class) && method_exists(\App\Menu::class, 'user')) {
            try {
                $menu = \App\Menu::user($user);
                if ($menu && (!method_exists($menu, 'isEmpty') || !$menu->isEmpty())) {
                    return collect($menu);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::debug('App\Menu::user() indisponível: ' . $e->getMessage());
            }
        }

        // 3. Fallback: raízes públicas — exibe menus de raiz, NÃO o menu do usuário autenticado.
        // A31 — Este fallback é intencional apenas para ambientes de desenvolvimento.
        // Em produção, garanta que MenuCacheService ou App\Menu::user() funcionem.
        if (class_exists(\App\Menu::class) && method_exists(\App\Menu::class, 'roots')) {
            try {
                return collect(\App\Menu::roots());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::debug('App\Menu::roots() indisponível: ' . $e->getMessage());
            }
        }

        return collect();
    }

    /**
     * Localiza o item de menu ativo para a rota atual e seus ascendentes.
     * Retorna [topmenu, rootId, ancestors].
     */
    private function resolverItemAtivo(Request $request): array
    {
        $topmenu   = null;
        $rootId    = null;
        $ancestors = [];

        if (!class_exists(\App\Menu::class)) {
            return [$topmenu, $rootId, $ancestors];
        }

        try {
            $path = '/' . ltrim($request->path(), '/');

            $topmenu = \App\Menu::where('link', $path)->first()
                ?: \App\Menu::where('link', 'like', "{$path}%")->first()
                ?: \App\Menu::where('process', ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ)->first()
                ?: \App\Menu::where('title', 'Financeiro')->whereNull('parent_id')->first();

            if ($topmenu) {
                $rootId = (method_exists($topmenu, 'root') && $topmenu->root())
                    ? $topmenu->root()->getKey()
                    : $topmenu->getKey();

                $ancestors = method_exists(\App\Menu::class, 'getMenuAncestors')
                    ? \App\Menu::getMenuAncestors($topmenu)
                    : [];
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::debug('Resolução de menu ativo falhou: ' . $e->getMessage());
        }

        return [$topmenu, $rootId, $ancestors];
    }

    /**
     * Resolve o breadcrumb nativo do i-Educar.
     */
    private function resolverBreadcrumb(): mixed
    {
        if (!class_exists(\iEducar\Support\Navigation\Breadcrumb::class)) {
            return null;
        }

        try {
            return app(\iEducar\Support\Navigation\Breadcrumb::class)
                ->currentPage('Módulo Financeiro');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
