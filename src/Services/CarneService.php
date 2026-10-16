<?php

namespace iEducar\Financeiro\Services;

use Carbon\Carbon;
use iEducar\Financeiro\Gateways\GatewayManager;
use iEducar\Financeiro\Models\CarneGateway;
use iEducar\Financeiro\Models\CobrancaGateway;
use iEducar\Financeiro\Models\Contrato;
use iEducar\Financeiro\Models\Titulo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class CarneService
{
    /**
     * Valida os critérios de elegibilidade para geração do carnê Asaas (FEAT-01 / §7.2)
     *
     * @param  array $titulos Coleção ou array de modelos Titulo
     * @return array ['elegivel' => bool, 'motivos' => array]
     */
    public function verificarElegibilidade(array $titulos): array
    {
        $motivos = [];

        if (count($titulos) < 2) {
            $motivos[] = 'Um carnê escolar requer no mínimo 2 títulos para parcelamento.';
        }

        $contratoId = null;
        $responsavelId = null;

        foreach ($titulos as $titulo) {
            if (!$contratoId) {
                $contratoId = $titulo->contrato_id;
            } elseif ($titulo->contrato_id !== $contratoId) {
                $motivos[] = 'Todos os títulos do carnê devem pertencer ao mesmo contrato.';
                break;
            }

            if (!$responsavelId) {
                $responsavelId = $titulo->responsavel_financeiro_id;
            } elseif ($titulo->responsavel_financeiro_id !== $responsavelId) {
                $motivos[] = 'Todos os títulos do carnê devem pertencer ao mesmo responsável financeiro.';
                break;
            }

            if (in_array($titulo->status, ['pago', 'liquidado', 'cancelado', 'renegociado'])) {
                $motivos[] = "Título #{$titulo->id} está com status '{$titulo->status}'. Títulos já quitados ou cancelados não podem compor o carnê.";
            }

            // Se o título já possuía uma cobrança individual avulsa ativa que já foi paga no gateway, não pode compor o carnê
            if ($titulo->cobrancaGateway && in_array($titulo->cobrancaGateway->status_externo, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])) {
                $motivos[] = "Título #{$titulo->id} já foi liquidado no gateway Asaas.";
            }
        }

        $primeiroTitulo = reset($titulos);
        if ($primeiroTitulo && (!$primeiroTitulo->responsavel || empty($primeiroTitulo->responsavel->cpf_cnpj))) {
            $motivos[] = 'O responsável financeiro não possui CPF/CNPJ válido cadastrado.';
        }

        return [
            'elegivel' => empty($motivos),
            'motivos'  => array_unique($motivos),
        ];
    }

    /**
     * Gera o carnê Asaas (installment) e mapeia os pagamentos 1:1 com os títulos (FEAT-01)
     *
     * @param  Contrato $contrato
     * @param  array    $titulos
     * @param  int|null $usuarioId
     * @return CarneGateway
     * @throws InvalidArgumentException|RuntimeException
     */
    public function gerar(Contrato $contrato, array $titulos, ?int $usuarioId = null): CarneGateway
    {
        $elegibilidade = $this->verificarElegibilidade($titulos);
        if (!$elegibilidade['elegivel']) {
            throw new InvalidArgumentException(implode(' ', $elegibilidade['motivos']));
        }

        // Ordena títulos por data de vencimento
        usort($titulos, fn($a, $b) => strcmp((string) $a->data_vencimento, (string) $b->data_vencimento));

        return DB::transaction(function () use ($contrato, $titulos, $usuarioId) {
            // Checagem de idempotência: se já existe carnê ativo com os mesmos títulos
            $idsOrdenados = array_map(fn($t) => (int) $t->id, $titulos);
            $carneExistente = CarneGateway::where('contrato_id', $contrato->id)
                ->where('status', 'ativo')
                ->first();

            if ($carneExistente) {
                return $carneExistente;
            }

            /** @var \iEducar\Financeiro\Gateways\Drivers\AsaasGateway $gateway */
            $gateway = app(GatewayManager::class)->driver('asaas');

            $resultado = $gateway->criarInstallment($contrato, $titulos);
            $installmentId = $resultado['installment_id'];
            $payments = $resultado['payments'] ?? [];

            // Se o Asaas não retornou cobranças, tenta consultar novamente
            if (empty($payments)) {
                $payments = $gateway->listarPagamentosDoParcelamento($installmentId);
            }

            // Ordena pagamentos por vencimento
            usort($payments, fn($a, $b) => strcmp((string) ($a['dueDate'] ?? ''), (string) ($b['dueDate'] ?? '')));

            // Validação de integridade entre títulos locais e pagamentos remotos
            if (count($payments) !== count($titulos)) {
                // Compensação: cancela o parcelamento órfão no Asaas
                $gateway->cancelarParcelamento($installmentId);
                throw new RuntimeException(sprintf(
                    'Divergência no parcelamento Asaas: o Asaas gerou %d pagamentos mas esperavam-se %d títulos. A operação foi cancelada por segurança.',
                    count($payments),
                    count($titulos)
                ));
            }

            // 1. Cria o registro do carnê
            $carne = CarneGateway::create([
                'contrato_id'               => $contrato->id,
                'responsavel_financeiro_id' => $contrato->responsavel_financeiro_id,
                'gateway'                   => 'asaas',
                'installment_id'            => $installmentId,
                'status'                    => 'ativo',
                'qtd_parcelas'              => count($titulos),
                'valor_parcela'             => round((float) ($resultado['valor_total'] / count($titulos)), 2),
                'primeiro_vencimento'       => $titulos[0]->data_vencimento,
                'titulo_ids'                => $idsOrdenados,
                'payload'                   => $resultado,
                'criado_por'                => $usuarioId,
            ]);

            // 2. Mapeia cada pagamento do Asaas ao seu respectivo título local (BUG-003 corrigido)
            foreach ($titulos as $idx => $titulo) {
                $pay = $payments[$idx];
                $chargeId = $pay['id'];

                CobrancaGateway::create([
                    'titulo_id'          => $titulo->id,
                    'contrato_id'        => $contrato->id,
                    'gateway'            => 'asaas',
                    'gateway_charge_id'  => $chargeId,
                    'installment_id'     => $installmentId,
                    'carne_id'           => $carne->id,
                    'status_externo'     => $pay['status'] ?? 'PENDING',
                    'linha_digitavel'    => $pay['identificationField'] ?? null,
                    'codigo_barras'      => $pay['barCode'] ?? null,
                    'link_boleto_pdf'    => $pay['bankSlipUrl'] ?? null,
                    'link_pagamento'     => $pay['invoiceUrl'] ?? null,
                    'ativa'              => true,
                    'payload_resposta'   => $pay,
                ]);
            }

            Log::info("CarneService: Carnê #{$carne->id} gerado com sucesso para contrato #{$contrato->id} com " . count($titulos) . " parcelas mapeadas.");

            return $carne;
        });
    }

    /**
     * Cancela um carnê ativo
     */
    public function cancelar(CarneGateway $carne, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($carne, $usuarioId) {
            /** @var \iEducar\Financeiro\Gateways\Drivers\AsaasGateway $gateway */
            $gateway = app(GatewayManager::class)->driver('asaas');

            $gateway->cancelarParcelamento($carne->installment_id);

            $carne->update([
                'status'        => 'cancelado',
                'cancelado_em'  => now(),
                'cancelado_por' => $usuarioId,
            ]);

            CobrancaGateway::where('carne_id', $carne->id)->update([
                'ativa'        => false,
                'cancelada_em' => now(),
            ]);
        });
    }

    /**
     * Regenera um carnê cancelando o anterior (permitido se nenhuma parcela foi paga)
     */
    public function regenerar(CarneGateway $carne, ?int $usuarioId = null): CarneGateway
    {
        $contrato = $carne->contrato;

        // Verifica se algum título deste carnê já foi pago
        $temPago = Titulo::whereIn('id', $carne->titulo_ids ?? [])
            ->where('status', 'pago')
            ->exists();

        if ($temPago) {
            throw new RuntimeException('Não é possível regenerar o carnê pois já existem parcelas pagas vinculadas a ele.');
        }

        $this->cancelar($carne, $usuarioId);

        $titulosAbertos = $contrato->titulos()
            ->whereIn('status', ['aberto', 'parcial'])
            ->orderBy('data_vencimento')
            ->get();

        return $this->gerar($contrato, $titulosAbertos->all(), $usuarioId);
    }

    /**
     * Alias para compatibilidade de chamada
     */
    public function gerarCarneInstallment(Contrato $contrato, array $titulos, ?int $usuarioId = null): CarneGateway
    {
        return $this->gerar($contrato, $titulos, $usuarioId);
    }
}

