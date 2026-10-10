<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class ReguaCobrancaRegra extends Model
{
    protected $table = 'financeiro.regua_cobranca_regras';

    protected $fillable = [
        'escola_id',
        'nome_regra',
        'tipo_gatilho',
        'dias_gatilho',
        'canal',
        'titulo_template',
        'corpo_template',
        'ativo',
    ];

    protected $casts = [
        'escola_id' => 'integer',
        'dias_gatilho' => 'integer',
        'ativo' => 'boolean',
    ];

    /**
     * Escopo para regras ativas
     */
    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Escopo para uma determinada escola ou regra geral
     */
    public function scopeParaEscola($query, $escolaId)
    {
        return $query->where(function ($q) use ($escolaId) {
            $q->whereNull('escola_id');
            if ($escolaId) {
                $q->orWhere('escola_id', $escolaId);
            }
        });
    }

    /**
     * Relacionamento com histórico de envios
     */
    public function envios()
    {
        return $this->hasMany(ReguaEnvioLog::class, 'regra_id');
    }
}
