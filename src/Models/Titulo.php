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
        'contrato_id' => 'integer',
        'aluno_id' => 'integer',
        'responsavel_financeiro_id' => 'integer',
        'escola_id' => 'integer',
        'numero_parcela' => 'integer',
        'total_parcelas' => 'integer',
        'valor_nominal' => 'float',
        'valor_desconto' => 'float',
        'valor_multa' => 'float',
        'valor_juros' => 'float',
        'valor_pago' => 'float',
        'valor_saldo' => 'float',
        'data_vencimento' => 'date',
        'data_prorrogacao' => 'date',
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

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class, 'titulo_id')->orderBy('data_pagamento', 'desc');
    }

    public function cobrancaGateway()
    {
        return $this->hasOne(CobrancaGateway::class, 'titulo_id');
    }

    public function scopeAbertos($query)
    {
        return $query->whereIn('status', ['aberto', 'parcial', 'vencido']);
    }

    public function scopeVencidos($query)
    {
        return $query->where(function ($queryScope) {
            $queryScope->where('status', 'vencido')
                ->orWhere(function ($q) {
                    $q->whereIn('status', ['aberto', 'parcial'])
                      ->where('data_vencimento', '<', Carbon::today()->toDateString());
                });
        });
    }

    public function isLiquidado(): bool
    {
        return $this->status === 'pago' || (float)$this->valor_saldo <= 0.01;
    }

    public function isVencido(): bool
    {
        if ($this->status === 'pago' || $this->status === 'cancelado') {
            return false;
        }
        $vencimentoEfetivo = $this->data_prorrogacao ?? $this->data_vencimento;
        return Carbon::today()->gt($vencimentoEfetivo);
    }

    public function getDiasAtrasoAttribute(): int
    {
        if (!$this->isVencido()) {
            return 0;
        }
        $vencimentoEfetivo = $this->data_prorrogacao ?? $this->data_vencimento;
        return (int) Carbon::today()->diffInDays($vencimentoEfetivo);
    }

    /**
     * Calcula a multa de atraso (padrão 2% legal)
     */
    public function calcularMulta(): float
    {
        if (!$this->isVencido()) {
            return 0.00;
        }
        $percentual = (float) config('financeiro.politicas.multa_percentual', 2.00);
        return round(($this->valor_saldo * ($percentual / 100)), 2);
    }

    /**
     * Calcula os juros diários pro-rata (padrão 1% a.m.)
     */
    public function calcularJuros(): float
    {
        $dias = $this->dias_atraso;
        if ($dias <= 0) {
            return 0.00;
        }
        $jurosMes = (float) config('financeiro.politicas.juros_mensal_percentual', 1.00);
        $taxaDiaria = ($jurosMes / 100) / 30;
        return round($this->valor_saldo * $taxaDiaria * $dias, 2);
    }

    /**
     * Retorna o valor corrigido para pagamento hoje
     */
    public function getValorAtualizadoAttribute(): float
    {
        return round($this->valor_saldo + $this->calcularMulta() + $this->calcularJuros(), 2);
    }

    /**
     * Retorna o nome completo do aluno vinculado consultando a base do i-Educar
     */
    public function getAlunoNomeAttribute(): string
    {
        if (empty($this->aluno_id)) {
            return '-';
        }
        try {
            $nome = \DB::table('pmieducar.aluno')
                ->join('cadastro.pessoa', 'cadastro.pessoa.idpes', '=', 'pmieducar.aluno.ref_idpes')
                ->where('pmieducar.aluno.cod_aluno', $this->aluno_id)
                ->value('cadastro.pessoa.nome');

            return $nome ?: ('Aluno #' . $this->aluno_id);
        } catch (\Throwable $e) {
            return 'Aluno #' . $this->aluno_id;
        }
    }

    /**
     * Retorna o nome da turma ativa do aluno consultando a base do i-Educar
     */
    public function getTurmaNomeAttribute(): string
    {
        if (empty($this->aluno_id)) {
            return '-';
        }
        try {
            $turma = \DB::table('pmieducar.matricula')
                ->join('pmieducar.matricula_turma', 'pmieducar.matricula_turma.ref_cod_matricula', '=', 'pmieducar.matricula.cod_matricula')
                ->join('pmieducar.turma', 'pmieducar.turma.cod_turma', '=', 'pmieducar.matricula_turma.ref_cod_turma')
                ->where('pmieducar.matricula.ref_cod_aluno', $this->aluno_id)
                ->where('pmieducar.matricula.ativo', 1)
                ->value('pmieducar.turma.nm_turma');

            return $turma ?: '-';
        } catch (\Throwable $e) {
            return '-';
        }
    }
}
