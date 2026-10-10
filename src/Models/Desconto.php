<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class Desconto extends Model
{
    protected $table = 'financeiro.descontos_convenios';

    protected $fillable = [
        'nome',
        'tipo',
        'valor_regra',
        'cumulativo',
        'vigencia_inicio',
        'vigencia_fim',
        'ativo',
    ];

    protected $casts = [
        'valor_regra' => 'float',
        'cumulativo' => 'boolean',
        'ativo' => 'boolean',
        'vigencia_inicio' => 'date',
        'vigencia_fim' => 'date',
    ];
}
