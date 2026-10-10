@extends('financeiro::layouts.app')

@section('title', 'Configurações do Gateway Asaas')
@section('breadcrumb', 'Configurações Asaas')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="gateway-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Integração com Gateway Asaas
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Chaves de API, webhooks e parâmetros de automação de Boletos Bancários e Pix
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('financeiro.dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 btn-ieducar-action btn-ieducar-blue" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar ao Painel
                </a>
            </div>
        </header>

        <!-- Card de Configuração de Webhook -->
        <article class="bg-cyan-50/70 border border-cyan-200/90 p-5 space-y-3" data-purpose="webhook-card">
            <div class="flex items-center gap-2 text-brand-petroleum">
                <svg class="w-5 h-5 text-cyan-700" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
                <h2 class="text-sm md:text-base font-bold" style="margin: 0;">
                    URL de Webhook para Sincronização Automática
                </h2>
            </div>
            <p class="text-xs md:text-sm text-slate-600 leading-relaxed" style="margin: 0;">
                Cadastre a URL abaixo no painel do <strong>Asaas</strong> em <em>Configurações da Conta &raquo; Integrações &raquo; Webhooks para Cobranças</em> para receber confirmações instantâneas quando um aluno ou responsável pagar via Pix ou Boleto:
            </p>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pt-1">
                <input type="text" readonly value="{{ $webhookUrl }}" class="flex-1 px-3 py-2 bg-white border border-cyan-300 font-mono text-xs md:text-sm text-slate-800 focus:outline-none" id="input-webhook-url">
                <button type="button" onclick="copiarWebhook();" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-blue" id="btn-copiar-webhook">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <span>Copiar URL</span>
                </button>
            </div>
        </article>

        <!-- Formulário de Parâmetros -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="form-card">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Credenciais da API e Parâmetros Operacionais
                    </h2>
                </div>
                <span class="text-xs text-slate-300">
                    Autenticação Segura Asaas V3
                </span>
            </div>

            <form method="post" action="{{ route('financeiro.configuracoes.gateway.salvar') }}" class="p-5 md:p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    <div class="md:col-span-4">
                        <label for="environment" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Ambiente de Execução <span class="text-rose-500">*</span>
                        </label>
                        <select name="environment" id="environment" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum" required>
                            <option value="sandbox" {{ ($gatewayConfig['environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Ambiente de Testes (Sandbox)</option>
                            <option value="production" {{ ($gatewayConfig['environment'] ?? '') === 'production' ? 'selected' : '' }}>Ambiente de Produção (Cobranças Reais)</option>
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1" style="margin: 4px 0 0 0;">
                            Utilize o Sandbox para validações antes de emitir cobranças reais aos alunos
                        </p>
                    </div>

                    <div class="md:col-span-8">
                        <label for="api_key" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            API Key (Token de Acesso Asaas) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" name="api_key" id="api_key" value="{{ old('api_key', $gatewayConfig['api_key'] ?? '') }}" class="w-full px-3 py-2 bg-white border border-slate-300 font-mono text-sm text-slate-800 focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum pr-24" placeholder="$aact_YTU5YTE0M2M6N2Z..." required>
                            <button type="button" onclick="alternarVisibilidadeChave();" class="absolute right-2 top-1.5 px-2.5 py-1 text-xs text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 transition">
                                Mostrar
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1" style="margin: 4px 0 0 0;">
                            Localizada no painel do Asaas em <em>Menu &raquo; Configurações da Conta &raquo; Integrações &raquo; Chaves de API</em>
                        </p>
                    </div>

                    <div class="md:col-span-6">
                        <label for="webhook_token" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Token de Autenticação do Webhook
                        </label>
                        <input type="text" name="webhook_token" id="webhook_token" value="{{ old('webhook_token', $gatewayConfig['webhook_token'] ?? '') }}" class="w-full px-3 py-2 bg-white border border-slate-300 font-mono text-sm text-slate-800 focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum" placeholder="Chave secreta configurada no Asaas">
                        <p class="text-[11px] text-slate-500 mt-1" style="margin: 4px 0 0 0;">
                            Opcional: token para validação do cabeçalho <code>asaas-access-token</code> enviado nas notificações
                        </p>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="pt-4 border-t border-slate-200 flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 btn-ieducar-action btn-ieducar-green">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Salvar Parâmetros
                    </button>

                    <button type="button" onclick="testarAsaas();" class="inline-flex items-center gap-2 px-4 py-2.5 btn-ieducar-action btn-ieducar-blue" id="btn-testar-conexao">
                        <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        Testar Conectividade com Asaas
                    </button>
                </div>
            </form>
        </section>

        <!-- Box de Resultado do Teste de Conexão -->
        <div id="box-resultado-conexao" class="hidden p-4 border text-sm font-medium transition" data-purpose="test-result"></div>

    </main>
</div>

@push('scripts')
<script>
    function copiarWebhook() {
        var input = document.getElementById('input-webhook-url');
        input.select();
        navigator.clipboard.writeText(input.value);
        var btn = document.getElementById('btn-copiar-webhook');
        btn.classList.add('bg-emerald-600');
        btn.querySelector('span').innerText = 'Copiado com Sucesso!';
        setTimeout(function() {
            btn.classList.remove('bg-emerald-600');
            btn.querySelector('span').innerText = 'Copiar URL';
        }, 2500);
    }

    function alternarVisibilidadeChave() {
        var input = document.getElementById('api_key');
        var btn = event.target;
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerText = 'Ocultar';
        } else {
            input.type = 'password';
            btn.innerText = 'Mostrar';
        }
    }

    function testarAsaas() {
        var btn = document.getElementById('btn-testar-conexao');
        btn.disabled = true;
        var originalHtml = btn.innerHTML;
        btn.innerHTML = 'Testando comunicação...';

        var apiKey = document.getElementById('api_key').value;
        var env = document.getElementById('environment').value;

        fetch('{{ route("financeiro.configuracoes.gateway.testar-conexao") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '{{ csrf_token() }}'
            },
            body: JSON.stringify({ api_key: apiKey, environment: env })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;

            var box = document.getElementById('box-resultado-conexao');
            box.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-800', 'border-emerald-300', 'bg-rose-50', 'text-rose-800', 'border-rose-300');

            if (data.sucesso) {
                box.classList.add('bg-emerald-50', 'text-emerald-800', 'border-emerald-300');
                box.innerHTML = '<strong>Conexão estabelecida com sucesso!</strong> ' + (data.mensagem || '') + ' (Saldo verificado: R$ ' + parseFloat(data.saldo || 0).toFixed(2) + ')';
            } else {
                box.classList.add('bg-rose-50', 'text-rose-800', 'border-rose-300');
                box.innerHTML = '<strong>Falha na conexão:</strong> ' + (data.mensagem || 'Não foi possível validar o token de acesso junto à API do Asaas.');
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            alert('Erro ao requisitar teste de conexão com o servidor.');
        });
    }
</script>
@endpush
@endsection
