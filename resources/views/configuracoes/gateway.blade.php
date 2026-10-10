@extends('financeiro::layouts.app')

@section('title', 'Configurações do Gateway Asaas')
@section('breadcrumb', 'Configurações Asaas')

@section('financeiro-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="color: #47728f; margin: 0; font-size: 18px; font-weight: 700;">
            <i class="fa fa-cogs"></i> Parâmetros e Credenciais do Gateway Asaas
        </h2>
    </div>

    <!-- Informações do Webhook para Copiar -->
    <div style="background-color: #f5f9fd; border: 1px solid #ccdce6; padding: 15px; margin-bottom: 20px; border-radius: 3px;">
        <h4 style="color: #47728f; margin: 0 0 8px 0; font-size: 14px; font-weight: 700;">
            <i class="fa fa-plug"></i> URL de Webhook para Configurar no Painel do Asaas
        </h4>
        <p style="font-size: 12px; color: #555; margin-bottom: 8px;">
            Copie a URL abaixo e cadastre em <em>Menu &raquo; Integrações &raquo; Webhooks</em> na sua conta do Asaas para receber confirmações instantâneas de pagamento de Boletos e Pix:
        </p>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" readonly value="{{ $webhookUrl }}" class="form-input" style="max-width: 550px; background-color: #fff; font-weight: 600;" id="input-webhook-url">
            <button type="button" class="btn btn-sm btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('input-webhook-url').value); alert('URL copiada para a área de transferência!');">
                <i class="fa fa-copy"></i> Copiar URL
            </button>
        </div>
    </div>

    <!-- Formulário de Configuração Oficial i-Educar -->
    <form id="formcadastro" method="post" action="{{ route('financeiro.configuracoes.gateway.salvar') }}">
        @csrf
        <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0">
            <tbody>
                <tr>
                    <td class="formdktd" colspan="2" height="24">
                        <b><i class="fa fa-key"></i> Chaves de API e Parâmetros de Integração</b>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" width="240" valign="top">
                        <label for="environment" class="form">Ambiente de Operação <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formmdtd">
                        <select name="environment" id="environment" class="form-input" style="max-width: 250px;">
                            <option value="sandbox" {{ ($config['environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Ambiente de Testes (Sandbox)</option>
                            <option value="production" {{ ($config['environment'] ?? '') === 'production' ? 'selected' : '' }}>Produção (Cobranças Reais)</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" valign="top">
                        <label for="api_key" class="form">API Key (Token de Acesso) <span class="campo_obrigatorio">*</span></label>
                    </td>
                    <td class="formlttd">
                        <input type="password" name="api_key" id="api_key" value="{{ old('api_key', $config['api_key'] ?? '') }}" required class="form-input" style="max-width: 550px;" placeholder="$aact_YTU5YTE0M2M6N2Z...">
                        <span style="font-size: 11px; color: #777; display: block; margin-top: 3px;">
                            Obtida no painel do Asaas em <em>Menu &raquo; Configurações da Conta &raquo; Integrações &raquo; Chaves de API</em>.
                        </span>
                    </td>
                </tr>

                <tr>
                    <td class="formmdtd" valign="top">
                        <label for="webhook_token" class="form">Token de Autenticação do Webhook</label>
                    </td>
                    <td class="formmdtd">
                        <input type="text" name="webhook_token" id="webhook_token" value="{{ old('webhook_token', $config['webhook_token'] ?? '') }}" class="form-input" style="max-width: 350px;">
                        <span style="font-size: 11px; color: #777; display: block; margin-top: 3px;">
                            Token secreto opcional configurado no Asaas para assinatura das requisições webhook.
                        </span>
                    </td>
                </tr>

                <tr>
                    <td class="formlttd" colspan="2" style="padding: 15px;">
                        <button type="submit" class="btn btn-green botaosalvar">
                            <i class="fa fa-save"></i> Salvar Parâmetros
                        </button>

                        <button type="button" class="btn btn-primary" style="margin-left: 10px;" onclick="testarAsaas();">
                            <i class="fa fa-exchange"></i> Testar Conectividade com o Asaas
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>

    <div id="box-resultado-conexao" style="display: none; margin-top: 20px;"></div>

    @push('scripts')
    <script>
        function testarAsaas() {
            var btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Testando...';

            var apiKey = document.getElementById('api_key').value;
            var env = document.getElementById('environment').value;

            fetch('{{ route("financeiro.configuracoes.gateway.testar-conexao") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ api_key: apiKey, environment: env })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-exchange"></i> Testar Conectividade com o Asaas';

                var box = document.getElementById('box-resultado-conexao');
                box.style.display = 'block';

                if (data.sucesso) {
                    box.className = 'alert alert-success';
                    box.innerHTML = '<i class="fa fa-check-circle"></i> ' + data.mensagem + ' <strong>Saldo consultado na conta: R$ ' + parseFloat(data.saldo || 0).toFixed(2) + '</strong>';
                } else {
                    box.className = 'alert alert-danger';
                    box.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + data.mensagem;
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-exchange"></i> Testar Conectividade com o Asaas';
                alert('Erro ao requisitar teste de conexão.');
            });
        }
    </script>
    @endpush
@endsection
