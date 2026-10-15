<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class LoteProcessamento extends Model
{
    protected $table = 'financeiro.lotes_processamento';

    protected $fillable = [
        'tipo',
        'parametros',
        'total',
        'sucesso',
        'falha',
        'ignorados',
        'status',
        'usuario_id',
        'iniciado_em',
        'finalizado_em',
        'erros',
    ];

    protected $casts = [
        'parametros'    => 'array',
        'total'         => 'integer',
        'sucesso'       => 'integer',
        'falha'         => 'integer',
        'ignorados'     => 'integer',
        'usuario_id'    => 'integer',
        'iniciado_em'   => 'datetime',
        'finalizado_em' => 'datetime',
        'erros'         => 'array',
    ];
}
