<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model para configurações de gateway de pagamento persistidas em banco.
 *
 * A16 — Fonte de verdade em runtime: tabela financeiro.gateway_configuracoes.
 * O config/financeiro.php serve apenas como valor padrão inicial.
 *
 * @property string      $gateway        Identificador (asaas, pagseguro, etc.)
 * @property string|null $api_key        Chave de API do gateway
 * @property string      $environment    sandbox|production
 * @property string|null $webhook_token  Token de autenticação do webhook
 * @property bool        $ativo
 */
class GatewayConfiguracao extends Model
{
    protected $table = 'financeiro.gateway_configuracoes';

    protected $fillable = [
        'gateway',
        'api_key',
        'environment',
        'webhook_token',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Oculta api_key e webhook_token na serialização JSON (logs, APIs)
     */
    protected $hidden = [
        'api_key',
        'webhook_token',
    ];

    /**
     * Retorna a configuração ativa de um gateway por nome
     */
    public static function paraGateway(string $gateway): ?self
    {
        return static::where('gateway', $gateway)->where('ativo', true)->first();
    }
}
