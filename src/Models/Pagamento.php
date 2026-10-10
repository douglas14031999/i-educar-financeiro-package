<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    protected $table = 'financeiro.pagamentos';

    protected $fillable = [
        'titulo_id',
        'forma_pagamento',
        'data_pagamento',
        'data_compensacao',
        'valor_pago',
        'valor_juros',
        'valor_multa',
        'valor_desconto_concedido',
        'origem_baixa',
        'usuario_id',
        'comprovante_hash',
        'estornado',
        'motivo_estorno',
        'data_estorno',
        'estornado_por_usuario_id',
    ];

    protected $casts = [
        'titulo_id' => 'integer',
        'valor_pago' => 'float',
        'valor_juros' => 'float',
        'valor_multa' => 'float',
        'valor_desconto_concedido' => 'float',
        'estornado' => 'boolean',
        'data_pagamento' => 'date',
        'data_compensacao' => 'date',
        'data_estorno' => 'datetime',
    ];

    public function titulo()
    {
        return $this->belongsTo(Titulo::class, 'titulo_id');
    }
}
