@extends(View::exists('layout.default') ? 'layout.default' : 'financeiro::layouts.standalone')

@section('content')
    <div id="financeiro-module" class="open-sans">
        @yield('financeiro-content')
    </div>
@endsection
