@php
    if (!isset($config)) {
        $config = config('legacy') ?: ['app' => ['database' => ['dbname' => config('database.connections.pgsql.database', 'ieducar')]]];
    }
    if (!isset($loggedUser)) {
        $u = auth()->user();
        $loggedUser = $u ?: new \stdClass();
        if (!isset($loggedUser->personId)) $loggedUser->personId = $u->id ?? 1;
        if (!isset($loggedUser->name)) $loggedUser->name = $u->name ?? 'Administrador';
        if (!isset($loggedUser->email)) $loggedUser->email = $u->email ?? 'admin@ieducar.com.br';
        if (!isset($loggedUser->role)) $loggedUser->role = 'Administrador';
        if (!isset($loggedUser->created_at)) $loggedUser->created_at = time();
        if (!isset($loggedUser->institution)) $loggedUser->institution = '';
        if (!isset($loggedUser->city)) $loggedUser->city = '';
        if (!isset($loggedUser->state)) $loggedUser->state = '';
        if (!isset($loggedUser->students_count)) $loggedUser->students_count = 0;
        if (!isset($loggedUser->teachers_count)) $loggedUser->teachers_count = 0;
        if (!isset($loggedUser->classes_count)) $loggedUser->classes_count = 0;
    }

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
            $root = $rootMenu ? $rootMenu->getKey() : 649;
        } else {
            $root = 649;
        }
    }

    if (!isset($mainmenu)) {
        $mainmenu = $root ?? 649;
    }

    if (!isset($menuPaths)) {
        $menuPaths = [];
    }

    if (!isset($currentMenu) || is_null($currentMenu)) {
        if (class_exists(\App\Menu::class)) {
            $currentMenu = \App\Menu::where('link', request()->getPathInfo())->first() 
                ?: \App\Menu::where('id', $mainmenu)->first() 
                ?: new \App\Menu();
        } else {
            $currentMenu = new \stdClass();
        }
    }

    if (!isset($breadcrumb)) {
        $breadcrumb = null;
        if (class_exists(\iEducar\Support\Navigation\Breadcrumb::class)) {
            try {
                $breadcrumb = app(\iEducar\Support\Navigation\Breadcrumb::class)->currentPage('Módulo Financeiro');
            } catch (\Throwable $e) {}
        }
    }

    if (!isset($title)) {
        $title = 'Módulo Financeiro';
    }
@endphp
@extends(View::exists('layout.default') ? 'layout.default' : 'financeiro::layouts.standalone')

@push('styles')
    @include('financeiro::partials.styles')
@endpush

@section('content')
    @if(!View::exists('layout.default'))
        @include('financeiro::partials.subnav')
    @endif
    <div id="financeiro-module" class="open-sans" style="@yield('module-style', 'padding: 15px 20px;')">
        @include('financeiro::partials.alerts')
        @include('financeiro::partials.realtime')

        @yield('financeiro-content')
    </div>
@endsection

