<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class CarneGateway extends Model
{
    protected $table = 'financeiro.carnes_gateway';

    protected $fillable = [
        'contrato_id',
        'responsavel_financeiro_id',
        'gateway',
        'installment_id',
        'status',
        'qtd_parcelas',
        'valor_parcela',
        'primeiro_vencimento',
        'titulo_ids',
        'payload',
        'cancelado_em',
        'cancelado_por',
        'criado_por',
    ];

    protected $casts = [
        'contrato_id'               => 'integer',
        'responsavel_financeiro_id' => 'integer',
        'qtd_parcelas'              => 'integer',
        'valor_parcela'             => 'float',
        'primeiro_vencimento'       => 'date',
        'titulo_ids'                => 'array',
        'payload'                   => 'array',
        'cancelado_em'              => 'datetime',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(ResponsavelFinanceiro::class, 'responsavel_financeiro_id');
    }

    public function cobrancas()
    {
        return $this->hasMany(CobrancaGateway::class, 'carne_id');
    }

    public function isAtivo(): bool
    {
        return $this->status === 'ativo';
    }
}
