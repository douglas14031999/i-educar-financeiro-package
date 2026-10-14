<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class CreditoResponsavel extends Model
{
    protected $table = 'financeiro.creditos_responsaveis';

    protected $fillable = [
        'responsavel_financeiro_id',
        'pagamento_id',
        'valor',
        'origem',
        'descricao',
        'utilizado',
        'cancelado',
    ];

    protected $casts = [
        'responsavel_financeiro_id' => 'integer',
        'pagamento_id'              => 'integer',
        'valor'                     => 'float',
        'utilizado'                 => 'boolean',
        'cancelado'                 => 'boolean',
    ];

    public function responsavel()
    {
        return $this->belongsTo(ResponsavelFinanceiro::class, 'responsavel_financeiro_id');
    }

    public function pagamento()
    {
        return $this->belongsTo(Pagamento::class, 'pagamento_id');
    }
}
