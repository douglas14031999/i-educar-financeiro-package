<?php

namespace iEducar\Financeiro\Events;

use iEducar\Financeiro\Models\Pagamento;
use iEducar\Financeiro\Models\Titulo;
use iEducar\Financeiro\Support\AlunoLookup;
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
     * @var array Payload fiscal estruturado pronto para provedores de NFS-e (BUG-029)
     */
    public $payloadFiscal;

    public function __construct(Titulo $titulo, Pagamento $pagamento)
    {
        $this->titulo = $titulo->load(['responsavel', 'contrato']);
        $this->pagamento = $pagamento;

        $responsavel = $this->titulo->responsavel;

        // Monta o logradouro completo com número e complemento
        $enderecoPartes = array_filter([
            $responsavel?->logradouro,
            $responsavel?->numero ? 'nº ' . $responsavel->numero : null,
            $responsavel?->complemento,
        ]);
        $logradouroCompleto = implode(', ', $enderecoPartes);

        $nomeAluno = $titulo->aluno_nome ?: ("Aluno #" . $titulo->aluno_id);

        // Estrutura padrão para emissão de NFS-e (Nota Fiscal de Serviços Eletrônica)
        $this->payloadFiscal = [
            'referencia_externa' => "TITULO-{$titulo->id}-PAG-{$pagamento->id}",
            'data_emissao' => date('Y-m-d\TH:i:sP'),
            'competencia' => $titulo->competencia,
            'valor_servicos' => (float) $pagamento->valor_pago,
            'valor_deducoes' => (float) ($pagamento->valor_desconto_concedido ?? 0),
            'aliquota_iss' => (float) config('financeiro.fiscal.aliquota_iss', 0.0),
            'codigo_municipio' => config('financeiro.fiscal.codigo_municipio_ibge', ''),
            'discriminacao' => sprintf(
                'Serviços educacionais e instrução escolar referente à competência %s do Aluno %s (%s).',
                $titulo->competencia,
                $nomeAluno,
                $titulo->observacoes ?? 'Mensalidade Escolar'
            ),
            'tomador' => [
                'cpf_cnpj' => preg_replace('/\D/', '', $responsavel?->cpf_cnpj ?? ''),
                'razao_social' => $responsavel?->nome ?? '',
                'email' => $responsavel?->email ?? '',
                'telefone' => preg_replace('/\D/', '', $responsavel?->telefone_celular ?? ''),
                'endereco' => [
                    'logradouro' => $logradouroCompleto ?: ($responsavel?->logradouro ?? ''),
                    'numero' => $responsavel?->numero ?? '',
                    'complemento' => $responsavel?->complemento ?? '',
                    'bairro' => $responsavel?->bairro ?? '',
                    'cep' => preg_replace('/\D/', '', $responsavel?->cep ?? ''),
                    'cidade' => $responsavel?->cidade ?? '',
                    'uf' => $responsavel?->estado ?? '',
                    'estado' => $responsavel?->estado ?? '',
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
