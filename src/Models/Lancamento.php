<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lancamento extends Model
{
    use SoftDeletes;

    protected $table = 'financeiro.lancamentos';

    protected $fillable = [
        'escola_id',
        'conta_bancaria_id',
        'plano_conta_id',
        'centro_custo_id',
        'tipo',
        'descricao',
        'fornecedor_nome',
        'data_competencia',
        'data_vencimento',
        'data_pagamento',
        'valor',
        'status',
        'documento_fiscal',
        'observacoes',
        'usuario_id',
        'valor_pago',
        'valor_juros',
        'valor_multa',
        'valor_desconto',
        'forma_pagamento',
        'liquidado_por',
        'liquidado_em',
        'cancelado_em',
        'cancelado_por',
    ];

    protected $casts = [
        'escola_id' => 'integer',
        'valor' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'valor_juros' => 'decimal:2',
        'valor_multa' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'data_competencia' => 'date',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'liquidado_em' => 'datetime',
        'cancelado_em' => 'datetime',
    ];

    public function contaBancaria()
    {
        return $this->belongsTo(ContaBancaria::class, 'conta_bancaria_id');
    }

    public function planoConta()
    {
        return $this->belongsTo(PlanoConta::class, 'plano_conta_id');
    }

    public function centroCusto()
    {
        return $this->belongsTo(CentroCusto::class, 'centro_custo_id');
    }

    public function isLiquidado(): bool
    {
        return $this->status === 'pago';
    }
}
