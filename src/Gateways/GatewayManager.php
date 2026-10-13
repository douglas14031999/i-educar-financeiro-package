<?php

namespace iEducar\Financeiro\Gateways;

use iEducar\Financeiro\Models\GatewayConfiguracao;
use Illuminate\Support\Manager;

class GatewayManager extends Manager
{
    /**
     * Retorna o driver padrão configurado
     */
    public function getDefaultDriver(): string
    {
        return $this->container['config']['financeiro.gateway_padrao'] ?? 'asaas';
    }

    /**
     * Cria uma instância do driver Asaas.
     *
     * Prioridade de configuração: DB (gateway_configuracoes) → .env → defaults.
     * Isso permite que a API key seja gerenciada pela UI sem alterar arquivos de ambiente.
     */
    protected function createAsaasDriver(): Drivers\AsaasGateway
    {
        // Base: config file / .env
        $config = $this->container['config']['financeiro.gateways.asaas'] ?? [];

        // Sobrescreve com valores do banco quando disponíveis
        try {
            $dbConfig = GatewayConfiguracao::paraGateway('asaas');
            if ($dbConfig) {
                if (!empty($dbConfig->api_key)) {
                    $config['api_key'] = $dbConfig->api_key;
                }
                if (!empty($dbConfig->environment)) {
                    $config['environment'] = $dbConfig->environment;
                }
                if (!empty($dbConfig->webhook_token)) {
                    $config['webhook_token'] = $dbConfig->webhook_token;
                }
                // Recalcula base_url após environment possivelmente atualizado
                $config['base_url'] = ($config['environment'] ?? 'sandbox') === 'production'
                    ? 'https://api.asaas.com/v3'
                    : 'https://sandbox.asaas.com/api/v3';
            }
        } catch (\Throwable $e) {
            // Tabela pode não existir em ambientes de teste — silencia e usa .env
        }

        return new Drivers\AsaasGateway($config);
    }
}
