<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Model para configurações de gateway de pagamento persistidas em banco.
 *
 * BUG-012: api_key e webhook_token são criptografados no banco (APP_KEY)
 * e mascarados na interface do usuário para evitar vazamento.
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
     * Decripta a chave de API de forma segura com fallback para legados em texto puro
     */
    public function getApiKeyAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    /**
     * Encripta a chave de API antes de persistir no banco de dados
     */
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = !empty($value)
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Decripta o webhook token com fallback seguro
     */
    public function getWebhookTokenAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    /**
     * Encripta o webhook token antes de persistir
     */
    public function setWebhookTokenAttribute($value)
    {
        $this->attributes['webhook_token'] = !empty($value)
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Retorna a chave com máscara segura (apenas últimos 4 caracteres visíveis)
     */
    public function getApiKeyMascaradaAttribute(): ?string
    {
        $key = $this->api_key;
        if (empty($key)) {
            return null;
        }
        $len = strlen($key);
        if ($len <= 4) {
            return '••••';
        }
        return '••••••••' . substr($key, -4);
    }

    /**
     * Retorna a configuração ativa de um gateway por nome
     */
    public static function paraGateway(string $gateway): ?self
    {
        return static::where('gateway', $gateway)->where('ativo', true)->first();
    }
}
