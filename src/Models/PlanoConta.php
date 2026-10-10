<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class PlanoConta extends Model
{
    protected $table = 'financeiro.plano_contas';

    protected $fillable = [
        'codigo',
        'nome',
        'tipo',
        'pai_id',
        'aceita_lancamento',
        'ativo',
    ];

    protected $casts = [
        'pai_id' => 'integer',
        'aceita_lancamento' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function pai()
    {
        return $this->belongsTo(self::class, 'pai_id');
    }

    public function filhos()
    {
        return $this->hasMany(self::class, 'pai_id')->orderBy('codigo');
    }
}
