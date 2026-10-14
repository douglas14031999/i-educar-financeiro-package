<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;

class CobrancaGateway extends Model
{
    protected $table = 'financeiro.cobrancas_gateway';

    protected $fillable = [
        'titulo_id',
        'contrato_id',
        'gateway',
        'gateway_charge_id',
        'installment_id',
        'status_externo',
        'linha_digitavel',
        'codigo_barras',
        'link_boleto_pdf',
        'link_pagamento',
        'carne_url',
        'pix_copia_cola',
        'pix_qr_code_base64',
        'payload_requisicao',
        'payload_resposta',
    ];

    protected $casts = [
        'titulo_id'        => 'integer',
        'contrato_id'      => 'integer',
        'payload_requisicao' => 'array',
        'payload_resposta'   => 'array',
    ];

    public function titulo()
    {
        return $this->belongsTo(Titulo::class, 'titulo_id');
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
