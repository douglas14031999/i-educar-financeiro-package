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
        'tipo_cobranca',
        'gateway_charge_id',
        'installment_id',
        'status_externo',
        'linha_digitavel',
        'codigo_barras',
        'link_boleto_pdf',
        'link_pagamento',
        'carne_url',
        'carne_id',
        'ativa',
        'cancelada_em',
        'pix_copia_cola',
        'pix_qr_code_base64',
        'qr_code_base64',
        'expira_em',
        'payload_requisicao',
        'payload_resposta',
    ];

    protected $casts = [
        'titulo_id'        => 'integer',
        'contrato_id'      => 'integer',
        'carne_id'         => 'integer',
        'ativa'            => 'boolean',
        'cancelada_em'     => 'datetime',
        'expira_em'        => 'datetime',
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

    public function carne()
    {
        return $this->belongsTo(CarneGateway::class, 'carne_id');
    }
}
