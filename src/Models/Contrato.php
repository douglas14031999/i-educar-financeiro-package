<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contrato extends Model
{
    use SoftDeletes;

    protected $table = 'financeiro.contratos';

    protected $fillable = [
        'numero_contrato',
        'matricula_id',
        'aluno_id',
        'responsavel_financeiro_id',
        'plano_id',
        'ano_letivo',
        'escola_id',     // A28/TALK-1: escola obrigatória
        'data_inicio',
        'data_termino',
        'valor_total_bruto',
        'valor_total_descontos',
        'valor_total_liquido',
        'dia_vencimento',
        'status',
        'motivo_cancelamento',
    ];

    protected $casts = [
        'matricula_id' => 'integer',
        'aluno_id' => 'integer',
        'responsavel_financeiro_id' => 'integer',
        'plano_id' => 'integer',
        'ano_letivo' => 'integer',
        'valor_total_bruto' => 'float',
        'valor_total_descontos' => 'float',
        'valor_total_liquido' => 'float',
        'dia_vencimento' => 'integer',
        'data_inicio' => 'date',
        'data_termino' => 'date',
    ];

    public function responsavel()
    {
        return $this->belongsTo(ResponsavelFinanceiro::class, 'responsavel_financeiro_id');
    }

    public function plano()
    {
        return $this->belongsTo(PlanoPagamento::class, 'plano_id');
    }

    public function titulos()
    {
        return $this->hasMany(Titulo::class, 'contrato_id')->orderBy('numero_parcela');
    }

    /**
     * Gera número sequencial único e seguro para concorrência.
     *
     * A23 — COUNT sem lock produz duplicações sob carga concorrente.
     * Solução: sequence dedicada por ano no PostgreSQL, garantida pelo próprio banco.
     *
     * @param  int    $anoLetivo
     * @return string e.g. "CTR-2026-00042"
     */
    public static function gerarNumeroContrato(int $anoLetivo): string
    {
        $sequenceName = "financeiro.seq_contratos_{$anoLetivo}";

        // Cria a sequence do ano se ainda não existir (idempotente)
        \DB::statement(
            "CREATE SEQUENCE IF NOT EXISTS {$sequenceName} START 1 INCREMENT 1 NO CYCLE"
        );

        $row = \DB::selectOne("SELECT nextval('{$sequenceName}') AS seq");
        $seq = (int) $row->seq;

        return sprintf('CTR-%d-%05d', $anoLetivo, $seq);
    }
}
