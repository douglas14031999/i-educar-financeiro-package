<?php

namespace iEducar\Financeiro\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Titulo extends Model
{
    use SoftDeletes;

    protected $table = 'financeiro.titulos';

    protected $fillable = [
        'uuid',
        'contrato_id',
        'renegociacao_id',   // A19 — obrigatório para RenegociacaoService::confirmar()
        'aluno_id',
        'responsavel_financeiro_id',
        'escola_id',
        'tipo',
        'numero_parcela',
        'total_parcelas',
        'competencia',
        'data_vencimento',
        'data_prorrogacao',
        'valor_nominal',
        'valor_desconto',
        'valor_multa',
        'valor_juros',
        'valor_pago',
        'valor_saldo',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'contrato_id'               => 'integer',
        'renegociacao_id'           => 'integer',
        'aluno_id'                  => 'integer',
        'responsavel_financeiro_id' => 'integer',
        'escola_id'                 => 'integer',
        'numero_parcela'            => 'integer',
        'total_parcelas'            => 'integer',
        // A20 — decimal:2 evita imprecisão de ponto flutuante binário em comparações monetárias
        'valor_nominal'             => 'decimal:2',
        'valor_desconto'            => 'decimal:2',
        'valor_multa'               => 'decimal:2',
        'valor_juros'               => 'decimal:2',
        'valor_pago'                => 'decimal:2',
        'valor_saldo'               => 'decimal:2',
        'data_vencimento'           => 'date',
        'data_prorrogacao'          => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($titulo) {
            if (empty($titulo->uuid)) {
                $titulo->uuid = (string) Str::uuid();
            }
            if (!isset($titulo->valor_saldo)) {
                $titulo->valor_saldo = (float) $titulo->valor_nominal - (float) ($titulo->valor_desconto ?? 0);
            }
        });
    }

    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(ResponsavelFinanceiro::class, 'responsavel_financeiro_id');
    }

    public function responsavelFinanceiro()
    {
        return $this->responsavel();
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class, 'titulo_id')->orderBy('data_pagamento', 'desc');
    }

    public function cobrancaGateway()
    {
        return $this->hasOne(CobrancaGateway::class, 'titulo_id')->where('ativa', true);
    }

    public function cobrancas()
    {
        return $this->hasMany(CobrancaGateway::class, 'titulo_id');
    }

    public function cobrancasGateway()
    {
        return $this->hasMany(CobrancaGateway::class, 'titulo_id');
    }

    public function renegociacao()
    {
        return $this->belongsTo(Renegociacao::class, 'renegociacao_id');
    }

    public function scopeAbertos($query)
    {
        return $query->whereIn('status', ['aberto', 'parcial', 'vencido']);
    }

    public function scopeVencidos($query)
    {
        $hoje = Carbon::today()->toDateString();

        return $query->where(function ($queryScope) use ($hoje) {
            $queryScope->where('status', 'vencido')
                ->orWhere(function ($q) use ($hoje) {
                    $q->whereIn('status', ['aberto', 'parcial'])
                      ->whereRaw('COALESCE(data_prorrogacao, data_vencimento) < ?', [$hoje]);
                });
        });
    }

    /**
     * Elegibilidade estrita para baixa ordinária (FIN-010)
     */
    public function isElegivelBaixa(): bool
    {
        return in_array($this->status, ['aberto', 'parcial', 'vencido']) && (float) $this->valor_saldo >= 0.005;
    }

    /**
     * Elegibilidade para emissão de boleto/Pix no gateway Asaas (FIN-010)
     */
    public function isElegivelEmissaoGateway(): bool
    {
        return in_array($this->status, ['aberto', 'parcial', 'vencido']) && (float) $this->valor_saldo >= 0.005;
    }

    /**
     * Elegibilidade para inclusão em termo de acordo de renegociação (FIN-010)
     */
    public function isElegivelRenegociacao(): bool
    {
        return in_array($this->status, ['aberto', 'parcial', 'vencido']) && (float) $this->valor_saldo >= 0.005;
    }

    /**
     * A21 — Título liquidado = status 'pago' OU saldo efectivamente zero.
     * Exclui 'renegociado' e 'cancelado': nesses casos o saldo foi zerado
     * por operação, não por pagamento — não são consideráveis como quitados.
     */
    public function isLiquidado(): bool
    {
        if (in_array($this->status, ['cancelado', 'renegociado'])) {
            return false;
        }
        return $this->status === 'pago' || (float) $this->valor_saldo < 0.005;
    }

    /**
     * A22 — Usa data_prorrogacao quando existir, consistente com getDiasAtrasoAttribute.
     * Todos os caminhos de vencimento devem usar o mesmo critério.
     */
    public function isVencido(): bool
    {
        if (in_array($this->status, ['pago', 'cancelado', 'renegociado'])) {
            return false;
        }
        // A22 — data_prorrogacao tem precedência sobre data_vencimento original
        $vencimentoEfetivo = $this->data_prorrogacao ?? $this->data_vencimento;
        return Carbon::today()->startOfDay()->gt(Carbon::parse($vencimentoEfetivo)->startOfDay());
    }

    public function getDiasAtrasoAttribute(): int
    {
        if (!$this->isVencido()) {
            return 0;
        }
        $vencimentoEfetivo = $this->data_prorrogacao ?? $this->data_vencimento;
        $diasTolerancia = (int) config('financeiro.politicas.dias_tolerancia', 0);
        return \iEducar\Financeiro\Support\CalculoEncargos::diasAtraso($vencimentoEfetivo, null, $diasTolerancia);
    }

    /**
     * Calcula a multa de atraso (padrão 2% legal)
     */
    public function calcularMulta(): float
    {
        return \iEducar\Financeiro\Support\CalculoEncargos::calcularMulta((float) $this->valor_saldo, $this->dias_atraso);
    }

    /**
     * Calcula os juros diários pro-rata (padrão 1% a.m.)
     */
    public function calcularJuros(): float
    {
        return \iEducar\Financeiro\Support\CalculoEncargos::calcularJuros((float) $this->valor_saldo, $this->dias_atraso);
    }

    /**
     * Retorna o valor corrigido para pagamento hoje
     */
    public function getValorAtualizadoAttribute(): float
    {
        return round($this->valor_saldo + $this->calcularMulta() + $this->calcularJuros(), 2);
    }

    /**
     * Retorna o nome completo do aluno vinculado consultando AlunoLookup com cache de requisição.
     */
    public function getAlunoNomeAttribute(): string
    {
        if (empty($this->aluno_id)) {
            return '-';
        }

        return \iEducar\Financeiro\Support\AlunoLookup::nome((int) $this->aluno_id);
    }

    /**
     * Retorna o nome da turma do aluno no ano letivo do título via AlunoLookup.
     */
    public function getTurmaNomeAttribute(): string
    {
        if (empty($this->aluno_id)) {
            return '-';
        }

        $anoFiltro = $this->contrato?->ano_letivo
            ?? ($this->competencia ? (int) substr($this->competencia, 0, 4) : null);

        return \iEducar\Financeiro\Support\AlunoLookup::turma((int) $this->aluno_id, $anoFiltro);
    }
}
