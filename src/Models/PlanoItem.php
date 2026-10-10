<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class PlanoItem extends Model
{
    /**
     * @var string
     */
    protected $table = 'financeiro.plano_itens';

    /**
     * @var array
     */
    protected $fillable = [
        'plano_id',
        'tipo_item',
        'descricao',
        'numero_parcela',
        'valor',
        'mes_competencia',
        'dia_vencimento_padrao',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'plano_id' => 'integer',
        'numero_parcela' => 'integer',
        'valor' => 'float',
        'mes_competencia' => 'integer',
        'dia_vencimento_padrao' => 'integer',
    ];

    /**
     * Plano de pagamento pai
     */
    public function plano()
    {
        return $this->belongsTo(PlanoPagamento::class, 'plano_id');
    }
}
