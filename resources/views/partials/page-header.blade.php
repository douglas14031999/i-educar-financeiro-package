@php
    $items = $breadcrumbs ?? [];
    // Se não tiver breadcrumb customizado, monta o padrão com base no título
    if (empty($items)) {
        $items = [
            ['label' => $title ?? 'Página Atual']
        ];
    }
@endphp

<header class="w-full mb-4 {{ $class ?? '' }}" data-purpose="ieducar-page-header">
    {{-- Breadcrumb Padrão i-Educar (Posicionado acima do título) --}}
    <nav class="ieducar-breadcrumb text-xs text-slate-500 mb-2 flex items-center flex-wrap gap-1.5" aria-label="Navegação Estrutural">
        <a href="/intranet" class="inline-flex items-center gap-1 text-[#006699] hover:underline transition" title="Ir para o Início">
            <svg class="w-3.5 h-3.5 text-slate-600" width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
            </svg>
            <span>Início</span>
        </a>

        <span class="text-slate-400 font-light">/</span>

        <a href="{{ route('financeiro.dashboard') }}" class="text-[#006699] hover:underline transition">
            Módulo Financeiro
        </a>

        @foreach($items as $index => $item)
            <span class="text-slate-400 font-light">/</span>
            @if(!empty($item['url']) && $index < count($items) - 1)
                <a href="{{ $item['url'] }}" class="text-[#006699] hover:underline transition">
                    {{ $item['label'] }}
                </a>
            @else
                <span class="text-slate-700 font-medium">
                    {{ $item['label'] }}
                </span>
            @endif
        @endforeach
    </nav>

    {{-- Título da Página e Ações (Sem ícones em caixa decorativa, seguindo o padrão nativo) --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-2 border-b border-slate-200/60" data-purpose="header-section">
        <div>
            <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0; line-height: 1.25;">
                {{ $title ?? 'Gestão Financeira' }}
            </h1>
            @if(!empty($subtitle))
                <p class="text-xs md:text-sm text-slate-500 mt-1" style="margin: 4px 0 0 0;">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if(!empty($actions) || !empty($slot))
            <div class="flex items-center gap-2.5 flex-wrap">
                {!! $actions ?? '' !!}
                {{ $slot ?? '' }}
            </div>
        @endif
    </div>
</header>
