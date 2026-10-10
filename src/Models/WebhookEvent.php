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
    ];

    protected $casts = [
        'payload' => 'array',
        'processado' => 'boolean',
        'processado_em' => 'datetime',
    ];
}
