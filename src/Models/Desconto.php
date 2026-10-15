<?php

namespace iEducar\Financeiro\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Desconto extends Model
{
    protected $table = 'financeiro.descontos_convenios';

    protected $fillable = [
        'nome',
        'tipo',               // percentual, valor_fixo, pontualidade, irmaos
        'escopo',             // global, plano, contrato, aluno, responsavel
        'alvo_id',            // ID correspondente ao escopo quando não for global
        'valor_regra',
        'teto_valor',         // Teto máximo em R$ que o desconto pode atingir
        'teto_percentual',    // Teto percentual máximo aplicável
        'cumulativo',
        'vigencia_inicio',
        'vigencia_fim',
        'ativo',
    ];

    protected $casts = [
        'alvo_id' => 'integer',
        'valor_regra' => 'float',
        'teto_valor' => 'float',
        'teto_percentual' => 'float',
        'cumulativo' => 'boolean',
        'ativo' => 'boolean',
        'vigencia_inicio' => 'date',
        'vigencia_fim' => 'date',
    ];

    /**
     * Scope para filtrar descontos que são aplicáveis ao título fornecido (FEAT-06)
     */
    public function scopeAplicavelAoTitulo($query, Titulo $titulo)
    {
        return $query->where('ativo', true)
            ->where(function ($q) use ($titulo) {
                // Escopo Global (vale para todos os títulos)
                $q->where('escopo', 'global')
                  ->orWhereNull('escopo');

                // Escopo Aluno
                if (!empty($titulo->aluno_id)) {
                    $q->orWhere(function ($sq) use ($titulo) {
                        $sq->where('escopo', 'aluno')->where('alvo_id', $titulo->aluno_id);
                    });
                }

                // Escopo Contrato
                if (!empty($titulo->contrato_id)) {
                    $q->orWhere(function ($sq) use ($titulo) {
                        $sq->where('escopo', 'contrato')->where('alvo_id', $titulo->contrato_id);
                    });
                }

                // Escopo Responsável Financeiro
                if (!empty($titulo->responsavel_financeiro_id)) {
                    $q->orWhere(function ($sq) use ($titulo) {
                        $sq->where('escopo', 'responsavel')->where('alvo_id', $titulo->responsavel_financeiro_id);
                    });
                }

                // Escopo Plano de Pagamento
                $planoId = $titulo->contrato?->plano_pagamento_id;
                if (!empty($planoId)) {
                    $q->orWhere(function ($sq) use ($planoId) {
                        $sq->where('escopo', 'plano')->where('alvo_id', $planoId);
                    });
                }
            });
    }

    /**
     * Calcula o valor em reais do desconto sobre uma base, respeitando tetos
     */
    public function calcularValorDesconto(float $valorBase, Carbon $data, Titulo $titulo): float
    {
        $valorCalculado = 0.00;

        if ($this->tipo === 'percentual') {
            $valorCalculado = round($valorBase * ($this->valor_regra / 100), 2);
        } elseif ($this->tipo === 'valor_fixo') {
            $valorCalculado = min($valorBase, (float) $this->valor_regra);
        } elseif ($this->tipo === 'pontualidade') {
            // Pontualidade: aplicável se data <= data_vencimento
            $vencimento = Carbon::parse($titulo->data_prorrogacao ?? $titulo->data_vencimento);
            if ($data->startOfDay()->lte($vencimento->startOfDay())) {
                $valorCalculado = round($valorBase * ($this->valor_regra / 100), 2);
            }
        } elseif ($this->tipo === 'irmaos') {
            $valorCalculado = round($valorBase * ($this->valor_regra / 100), 2);
        }

        // Aplicação de teto monetário
        if ($this->teto_valor !== null && $this->teto_valor > 0) {
            $valorCalculado = min($valorCalculado, (float) $this->teto_valor);
        }

        // Aplicação de teto percentual
        if ($this->teto_percentual !== null && $this->teto_percentual > 0) {
            $limiteTetoPercentual = round($valorBase * ($this->teto_percentual / 100), 2);
            $valorCalculado = min($valorCalculado, $limiteTetoPercentual);
        }

        return max(0.00, min($valorBase, $valorCalculado));
    }
}
