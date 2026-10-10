@php
    if (!isset($menu)) {
        $user = auth()->user();
        $menuList = collect();
        if ($user && class_exists(\App\Services\MenuCacheService::class)) {
            try {
                $menuList = app(\App\Services\MenuCacheService::class)->getMenuByUser($user);
            } catch (\Throwable $e) {}
        }
        if (empty($menuList) && class_exists(\App\Menu::class)) {
            try {
                $menuList = $user ? \App\Menu::user($user) : \App\Menu::roots();
            } catch (\Throwable $e) {
                $menuList = \App\Menu::roots();
            }
        }
        $menu = $menuList ?? collect();
    }

    if (!isset($root)) {
        if (class_exists(\App\Menu::class)) {
            $rootMenu = \App\Menu::where('process', \iEducar\Financeiro\Constants\ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ)->first()
                ?: \App\Menu::where('title', 'Financeiro')->whereNull('parent_id')->first();
            $root = $rootMenu ? $rootMenu->getKey() : null;
        } else {
            $root = null;
        }
    }
@endphp
@extends(View::exists('layout.default') ? 'layout.default' : 'financeiro::layouts.standalone')

@section('content')
    <div id="financeiro-module" class="open-sans">
        @yield('financeiro-content')
    </div>
@endsection
