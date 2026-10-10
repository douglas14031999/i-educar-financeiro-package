<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContaBancaria extends Model
{
    use SoftDeletes;

    protected $table = 'financeiro.contas_bancarias';

    protected $fillable = [
        'escola_id',
        'nome',
        'tipo',
        'banco_codigo',
        'agencia',
        'conta',
        'saldo_inicial',
        'saldo_atual',
        'ativo',
    ];

    protected $casts = [
        'escola_id' => 'integer',
        'saldo_inicial' => 'float',
        'saldo_atual' => 'float',
        'ativo' => 'boolean',
    ];
}
