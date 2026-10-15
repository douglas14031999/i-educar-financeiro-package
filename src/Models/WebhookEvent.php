<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $table = 'financeiro.webhook_events';

    protected $fillable = [
        'gateway',
        'event_id',
        'event_type',
        'payload',
        'processado',
        'processado_em',
        'erro',
        'tentativas',
        'proxima_tentativa_em',
        'requer_acao',
        'motivo_acao',
    ];

    protected $casts = [
        'payload' => 'array',
        'processado' => 'boolean',
        'processado_em' => 'datetime',
        'tentativas' => 'integer',
        'proxima_tentativa_em' => 'datetime',
        'requer_acao' => 'boolean',
    ];
}
