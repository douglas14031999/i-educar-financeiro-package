<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class ReguaEnvioLog extends Model
{
    protected $table = 'financeiro.regua_envios_log';

    protected $fillable = [
        'regra_id',
        'titulo_id',
        'responsavel_id',
        'canal',
        'destinatario',
        'mensagem_enviada',
        'sucesso',
        'erro',
        'enviado_em',
    ];

    protected $casts = [
        'sucesso' => 'boolean',
        'enviado_em' => 'datetime',
    ];

    public function regra()
    {
        return $this->belongsTo(ReguaCobrancaRegra::class, 'regra_id');
    }

    public function titulo()
    {
        return $this->belongsTo(Titulo::class, 'titulo_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(ResponsavelFinanceiro::class, 'responsavel_id');
    }
}
