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

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class, 'titulo_id')->orderBy('data_pagamento', 'desc');
    }

    public function cobrancaGateway()
    {
        return $this->hasOne(CobrancaGateway::class, 'titulo_id');
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
        return $query->where(function ($queryScope) {
            $queryScope->where('status', 'vencido')
                ->orWhere(function ($q) {
                    $q->whereIn('status', ['aberto', 'parcial'])
                      ->where('data_vencimento', '<', Carbon::today()->toDateString());
                });
        });
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
     * A32 — Cache de nomes acadêmicos na instância para evitar N+1 em listagens.
     * Cada accessor faz 1 query por aluno; em uma página de 50 títulos isso seria 50 queries.
     * O cache vive apenas no ciclo de vida da instância (sem estado persistido).
     */
    private array $_cacheAcademico = [];

    /**
     * Retorna o nome completo do aluno vinculado consultando a base do i-Educar.
     * A32 — Resultado memoizado por instância.
     */
    public function getAlunoNomeAttribute(): string
    {
        if (empty($this->aluno_id)) {
            return '-';
        }

        $cacheKey = 'aluno_' . $this->aluno_id;
        if (isset($this->_cacheAcademico[$cacheKey])) {
            return $this->_cacheAcademico[$cacheKey];
        }

        try {
            $nome = \DB::table('pmieducar.aluno')
                ->join('cadastro.pessoa', 'cadastro.pessoa.idpes', '=', 'pmieducar.aluno.ref_idpes')
                ->where('pmieducar.aluno.cod_aluno', $this->aluno_id)
                ->value('cadastro.pessoa.nome');

            $resultado = $nome ?: ('Aluno #' . $this->aluno_id);
        } catch (\Throwable $e) {
            $resultado = 'Aluno #' . $this->aluno_id;
        }

        return $this->_cacheAcademico[$cacheKey] = $resultado;
    }

    /**
     * Retorna o nome da turma do aluno no ano letivo do título.
     * A32 — Resultado memoizado por instância.
     * A22 — Filtra pelo ano letivo do contrato para não retornar turma de outro ano.
     */
    public function getTurmaNomeAttribute(): string
    {
        if (empty($this->aluno_id)) {
            return '-';
        }

        // A22 — usa o ano do título (via competencia ou contrato) para filtrar a turma correta
        $anoFiltro = $this->contrato?->ano_letivo
            ?? ($this->competencia ? (int) substr($this->competencia, 0, 4) : null);

        $cacheKey = 'turma_' . $this->aluno_id . '_' . ($anoFiltro ?? 'any');
        if (isset($this->_cacheAcademico[$cacheKey])) {
            return $this->_cacheAcademico[$cacheKey];
        }

        try {
            $query = \DB::table('pmieducar.matricula')
                ->join('pmieducar.matricula_turma', 'pmieducar.matricula_turma.ref_cod_matricula', '=', 'pmieducar.matricula.cod_matricula')
                ->join('pmieducar.turma', 'pmieducar.turma.cod_turma', '=', 'pmieducar.matricula_turma.ref_cod_turma')
                ->where('pmieducar.matricula.ref_cod_aluno', $this->aluno_id)
                ->where('pmieducar.matricula.ativo', 1);

            // A22 — filtra pelo ano letivo quando disponível
            if ($anoFiltro) {
                $query->where('pmieducar.matricula.ano', $anoFiltro);
            }

            $turma = $query->value('pmieducar.turma.nm_turma');
            $resultado = $turma ?: '-';
        } catch (\Throwable $e) {
            $resultado = '-';
        }

        return $this->_cacheAcademico[$cacheKey] = $resultado;
    }
}
