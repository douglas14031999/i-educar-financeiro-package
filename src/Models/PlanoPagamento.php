<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanoPagamento extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'financeiro.planos';

    /**
     * @var array
     */
    protected $fillable = [
        'escola_id',
        'ano_letivo',
        'curso_id',
        'serie_id',
        'nome',
        'descricao',
        'valor_total',
        'qtd_parcelas',
        'ativo',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'escola_id' => 'integer',
        'ano_letivo' => 'integer',
        'curso_id' => 'integer',
        'serie_id' => 'integer',
        'valor_total' => 'float',
        'qtd_parcelas' => 'integer',
        'ativo' => 'boolean',
    ];

    /**
     * Itens do plano ordenados por parcela
     */
    public function itens()
    {
        return $this->hasMany(PlanoItem::class, 'plano_id')->orderBy('numero_parcela');
    }

    /**
     * Contratos associados a este plano
     */
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'plano_id');
    }

    /**
     * Recalcula o valor total com base nos itens cadastrados
     */
    public function recalcularValorTotal(): void
    {
        $this->valor_total = (float) $this->itens()->sum('valor');
        $this->qtd_parcelas = (int) $this->itens()->count();
        $this->save();
    }
}
