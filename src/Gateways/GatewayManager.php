<?php

namespace iEducar\Financeiro\Gateways;

use Illuminate\Support\Manager;

class GatewayManager extends Manager
{
    /**
     * Retorna o driver padrão configurado
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->container['config']['financeiro.gateway_padrao'] ?? 'asaas';
    }

    /**
     * Cria uma instância do driver Asaas
     *
     * @return Drivers\AsaasGateway
     */
    protected function createAsaasDriver()
    {
        $config = $this->container['config']['financeiro.gateways.asaas'] ?? [];
        return new Drivers\AsaasGateway($config);
    }
}
