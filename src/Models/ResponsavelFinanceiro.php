<?php

namespace iEducar\Financeiro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResponsavelFinanceiro extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'financeiro.responsaveis_financeiros';

    /**
     * @var array
     */
    protected $fillable = [
        'pessoa_id',
        'nome',
        'cpf_cnpj',
        'rg_ie',
        'email',
        'telefone_celular',
        'telefone_fixo',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'observacoes',
        'ativo',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'ativo' => 'boolean',
        'pessoa_id' => 'integer',
    ];

    /**
     * Sanitiza o CPF ou CNPJ removendo pontuações
     *
     * @param string|null $value
     */
    public function setCpfCnpjAttribute($value)
    {
        $this->attributes['cpf_cnpj'] = preg_replace('/\D/', '', (string) $value);
    }

    /**
     * Retorna o CPF ou CNPJ formatado
     *
     * @return string
     */
    public function getCpfCnpjFormatadoAttribute(): string
    {
        $doc = $this->cpf_cnpj ?? '';
        if (strlen($doc) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
        }
        if (strlen($doc) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
        }
        return $doc;
    }

    /**
     * Vínculos N:N com alunos do i-Educar
     */
    public function alunoVinculos()
    {
        return $this->hasMany(AlunoResponsavel::class, 'responsavel_id');
    }

    /**
     * Contratos associados ao responsável
     */
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'responsavel_financeiro_id');
    }

    /**
     * Títulos / Parcelas financeiras associadas
     */
    public function titulos()
    {
        return $this->hasMany(Titulo::class, 'responsavel_financeiro_id');
    }

    /**
     * Validação estrita de CPF/CNPJ brasileiro
     *
     * @param string $doc
     * @return bool
     */
    public static function validarCpfCnpj(string $doc): bool
    {
        $doc = preg_replace('/\D/', '', $doc);

        if (strlen($doc) === 11) {
            if (preg_match('/(\d)\1{10}/', $doc)) {
                return false;
            }
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $doc[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($doc[$c] != $d) {
                    return false;
                }
            }
            return true;
        }

        if (strlen($doc) === 14) {
            if (preg_match('/(\d)\1{13}/', $doc)) {
                return false;
            }
            $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            $soma1 = 0;
            for ($i = 0; $i < 12; $i++) {
                $soma1 += $doc[$i] * $pesos1[$i];
            }
            $resto1 = $soma1 % 11;
            $dv1 = ($resto1 < 2) ? 0 : 11 - $resto1;
            if ($doc[12] != $dv1) {
                return false;
            }

            $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            $soma2 = 0;
            for ($i = 0; $i < 13; $i++) {
                $soma2 += $doc[$i] * $pesos2[$i];
            }
            $resto2 = $soma2 % 11;
            $dv2 = ($resto2 < 2) ? 0 : 11 - $resto2;
            return $doc[13] == $dv2;
        }

        return false;
    }
}
