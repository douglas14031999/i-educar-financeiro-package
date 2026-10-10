<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class CentroCusto extends Model
{
    protected $table = 'financeiro.centros_custo';

    protected $fillable = [
        'escola_id',
        'codigo',
        'nome',
        'ativo',
    ];

    protected $casts = [
        'escola_id' => 'integer',
        'ativo' => 'boolean',
    ];
}
