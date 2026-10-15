<?php

namespace iEducar\Financeiro\Events;

use Illuminate\Queue\SerializesModels;

class MatriculaCancelada
{
    use SerializesModels;

    public int $matriculaId;
    public string $motivo;

    public function __construct(int $matriculaId, string $motivo = 'Cancelamento de matrícula')
    {
        $this->matriculaId = $matriculaId;
        $this->motivo = $motivo;
    }
}
