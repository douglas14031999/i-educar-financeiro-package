<?php

namespace iEducar\Financeiro\Events;

use Illuminate\Queue\SerializesModels;

class MatriculaCriada
{
    use SerializesModels;

    public int $matriculaId;
    public int $alunoId;
    public int $escolaId;
    public int $ano;

    public function __construct(int $matriculaId, int $alunoId, int $escolaId, int $ano)
    {
        $this->matriculaId = $matriculaId;
        $this->alunoId = $alunoId;
        $this->escolaId = $escolaId;
        $this->ano = $ano;
    }
}
