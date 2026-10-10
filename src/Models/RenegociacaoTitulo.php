<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class RenegociacaoTitulo extends Model
{
    protected $table = 'financeiro.renegociacao_titulos';

    protected $fillable = [
        'renegociacao_id',
        'titulo_id',
    ];

    public function renegociacao()
    {
        return $this->belongsTo(Renegociacao::class, 'renegociacao_id');
    }

    public function titulo()
    {
        return $this->belongsTo(Titulo::class, 'titulo_id');
    }
}
