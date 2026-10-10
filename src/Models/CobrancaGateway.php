<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class CobrancaGateway extends Model
{
    protected $table = 'financeiro.cobrancas_gateway';

    protected $fillable = [
        'titulo_id',
        'gateway',
        'gateway_charge_id',
        'status_externo',
        'linha_digitavel',
        'codigo_barras',
        'link_boleto_pdf',
        'link_pagamento',
        'pix_copia_cola',
        'pix_qr_code_base64',
        'payload_requisicao',
        'payload_resposta',
    ];

    protected $casts = [
        'titulo_id' => 'integer',
        'payload_requisicao' => 'array',
        'payload_resposta' => 'array',
    ];

    public function titulo()
    {
        return $this->belongsTo(Titulo::class, 'titulo_id');
    }
}
