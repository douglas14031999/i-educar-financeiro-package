<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class MovimentacaoConta extends Model
{
    protected $table = 'financeiro.movimentacoes_conta';

    protected $fillable = [
        'conta_bancaria_id',
        'tipo',
        'valor',
        'saldo_anterior',
        'saldo_posterior',
        'origem_tipo',
        'origem_id',
        'data_movimentacao',
        'descricao',
        'usuario_id',
    ];

    protected $casts = [
        'conta_bancaria_id' => 'integer',
        'valor' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_posterior' => 'decimal:2',
        'origem_id' => 'integer',
        'data_movimentacao' => 'date',
        'usuario_id' => 'integer',
    ];

    public function contaBancaria()
    {
        return $this->belongsTo(ContaBancaria::class, 'conta_bancaria_id');
    }
}
