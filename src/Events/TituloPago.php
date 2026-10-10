<?php

namespace iEducar\Financeiro\Events;

use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TituloPago
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Titulo
     */
    public $titulo;

    /**
     * @var Pagamento
     */
    public $pagamento;

    /**
     * @var array Payload fiscal estruturado pronto para provedores de NFS-e
     */
    public $payloadFiscal;

    public function __construct(Titulo $titulo, Pagamento $pagamento)
    {
        $this->titulo = $titulo->load(['responsavel', 'contrato']);
        $this->pagamento = $pagamento;

        $responsavel = $this->titulo->responsavel;

        // Estrutura padrão para emissão de NFS-e (Nota Fiscal de Serviços Eletrônica)
        $this->payloadFiscal = [
            'referencia_externa' => "TITULO-{$titulo->id}-PAG-{$pagamento->id}",
            'data_emissao' => date('Y-m-d\TH:i:sP'),
            'valor_servicos' => (float) $pagamento->valor_pago,
            'discriminacao' => sprintf(
                'Serviços educacionais e instrução escolar referente à competência %s do Aluno #%d (%s).',
                $titulo->competencia,
                $titulo->aluno_id,
                $titulo->observacoes ?? 'Mensalidade Escolar'
            ),
            'tomador' => [
                'cpf_cnpj' => preg_replace('/\D/', '', $responsavel->cpf_cnpj ?? ''),
                'razao_social' => $responsavel->nome ?? '',
                'email' => $responsavel->email ?? '',
                'telefone' => preg_replace('/\D/', '', $responsavel->celular ?? $responsavel->telefone ?? ''),
                'endereco' => [
                    'logradouro' => $responsavel->endereco ?? '',
                    'bairro' => $responsavel->bairro ?? '',
                    'cep' => preg_replace('/\D/', '', $responsavel->cep ?? ''),
                    'cidade' => $responsavel->cidade ?? '',
                    'uf' => $responsavel->uf ?? '',
                ],
            ],
            'dados_pagamento' => [
                'forma' => $pagamento->forma_pagamento,
                'origem' => $pagamento->origem_baixa,
                'data_pagamento' => $pagamento->data_pagamento,
                'hash_autenticacao' => $pagamento->comprovante_hash,
            ],
        ];
    }
}
