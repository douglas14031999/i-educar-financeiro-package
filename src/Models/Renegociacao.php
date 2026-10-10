<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class Renegociacao extends Model
{
    protected $table = 'financeiro.renegociacoes';

    protected $fillable = [
        'numero_acordo',
        'aluno_id',
        'responsavel_financeiro_id',
        'valor_original',
        'valor_desconto_concedido',
        'valor_acordo',
        'qtd_parcelas',
        'status',
        'usuario_id',
    ];

    protected $casts = [
        'valor_original' => 'decimal:2',
        'valor_desconto_concedido' => 'decimal:2',
        'valor_acordo' => 'decimal:2',
        'qtd_parcelas' => 'integer',
    ];

    public function responsavel()
    {
        return $this->belongsTo(ResponsavelFinanceiro::class, 'responsavel_financeiro_id');
    }

    public function titulosOriginais()
    {
        return $this->belongsToMany(Titulo::class, 'financeiro.renegociacao_titulos', 'renegociacao_id', 'titulo_id')
            ->withTimestamps();
    }

    public function novasParcelas()
    {
        return $this->hasMany(Titulo::class, 'renegociacao_id');
    }
}
