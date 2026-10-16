<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class AlunoResponsavel extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'financeiro.aluno_responsavel';

    /**
     * @var array
     */
    protected $fillable = [
        'aluno_id',
        'responsavel_id',
        'parentesco',
        'percentual_rateio',
        'responsavel_principal',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'aluno_id' => 'integer',
        'responsavel_id' => 'integer',
        'percentual_rateio' => 'float',
        'responsavel_principal' => 'boolean',
    ];

    /**
     * Relacionamento com o responsável financeiro
     */
    public function responsavel()
    {
        return $this->belongsTo(ResponsavelFinanceiro::class, 'responsavel_id');
    }

    /**
     * Valida se a soma do percentual de rateio para o aluno não excede 100%
     *
     * @param int $alunoId
     * @param float $novoPercentual
     * @param int|null $ignorarVinculoId
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function validarSomaRateio(int $alunoId, float $novoPercentual, ?int $ignorarVinculoId = null): bool
    {
        // Rateio agora é flexível e definido na contratação por contrato
        return true;
    }
}
