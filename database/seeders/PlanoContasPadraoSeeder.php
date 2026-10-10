<?php

namespace iEducar\Financeiro\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanoContasPadraoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $contas = [
            // RECEITAS
            ['codigo' => '1', 'nome' => 'RECEITAS OPERACIONAIS', 'tipo' => 'receita', 'pai_codigo' => null, 'aceita' => false],
            ['codigo' => '1.1', 'nome' => 'Mensalidades Escolares', 'tipo' => 'receita', 'pai_codigo' => '1', 'aceita' => false],
            ['codigo' => '1.1.01', 'nome' => 'Mensalidades - Educação Infantil', 'tipo' => 'receita', 'pai_codigo' => '1.1', 'aceita' => true],
            ['codigo' => '1.1.02', 'nome' => 'Mensalidades - Ensino Fundamental I', 'tipo' => 'receita', 'pai_codigo' => '1.1', 'aceita' => true],
            ['codigo' => '1.1.03', 'nome' => 'Mensalidades - Ensino Fundamental II', 'tipo' => 'receita', 'pai_codigo' => '1.1', 'aceita' => true],
            ['codigo' => '1.1.04', 'nome' => 'Mensalidades - Ensino Médio', 'tipo' => 'receita', 'pai_codigo' => '1.1', 'aceita' => true],
            ['codigo' => '1.2', 'nome' => 'Matrículas e Rematrículas', 'tipo' => 'receita', 'pai_codigo' => '1', 'aceita' => true],
            ['codigo' => '1.3', 'nome' => 'Material Didático e Apostilas', 'tipo' => 'receita', 'pai_codigo' => '1', 'aceita' => true],
            ['codigo' => '1.4', 'nome' => 'Taxas e Serviços Escolares', 'tipo' => 'receita', 'pai_codigo' => '1', 'aceita' => true],
            ['codigo' => '1.5', 'nome' => 'Juros e Multas Recebidos', 'tipo' => 'receita', 'pai_codigo' => '1', 'aceita' => true],

            // DESPESAS
            ['codigo' => '2', 'nome' => 'DESPESAS OPERACIONAIS', 'tipo' => 'despesa', 'pai_codigo' => null, 'aceita' => false],
            ['codigo' => '2.1', 'nome' => 'Pessoal e Folha de Pagamento', 'tipo' => 'despesa', 'pai_codigo' => '2', 'aceita' => false],
            ['codigo' => '2.1.01', 'nome' => 'Salários - Corpo Docente', 'tipo' => 'despesa', 'pai_codigo' => '2.1', 'aceita' => true],
            ['codigo' => '2.1.02', 'nome' => 'Salários - Administrativo e Secretaria', 'tipo' => 'despesa', 'pai_codigo' => '2.1', 'aceita' => true],
            ['codigo' => '2.1.03', 'nome' => 'Encargos e Benefícios Trabalhistas', 'tipo' => 'despesa', 'pai_codigo' => '2.1', 'aceita' => true],
            ['codigo' => '2.2', 'nome' => 'Manutenção e Instalações', 'tipo' => 'despesa', 'pai_codigo' => '2', 'aceita' => false],
            ['codigo' => '2.2.01', 'nome' => 'Água, Luz e Telefonia/Internet', 'tipo' => 'despesa', 'pai_codigo' => '2.2', 'aceita' => true],
            ['codigo' => '2.2.02', 'nome' => 'Limpeza, Manutenção e Conservação', 'tipo' => 'despesa', 'pai_codigo' => '2.2', 'aceita' => true],
            ['codigo' => '2.3', 'nome' => 'Materiais Pedagógicos e de Consumo', 'tipo' => 'despesa', 'pai_codigo' => '2', 'aceita' => true],
            ['codigo' => '2.4', 'nome' => 'Taxas Bancárias e Tarifas Asaas', 'tipo' => 'despesa', 'pai_codigo' => '2', 'aceita' => true],
            ['codigo' => '2.5', 'nome' => 'Impostos e Tributos', 'tipo' => 'despesa', 'pai_codigo' => '2', 'aceita' => true],
        ];

        $codigosMap = [];

        foreach ($contas as $conta) {
            $paiId = isset($conta['pai_codigo']) && isset($codigosMap[$conta['pai_codigo']])
                ? $codigosMap[$conta['pai_codigo']]
                : null;

            $existente = DB::table('financeiro.plano_contas')->where('codigo', $conta['codigo'])->first();
            if ($existente) {
                $id = $existente->id;
                DB::table('financeiro.plano_contas')->where('id', $id)->update([
                    'nome' => $conta['nome'],
                    'tipo' => $conta['tipo'],
                    'pai_id' => $paiId,
                    'aceita_lancamento' => $conta['aceita'],
                    'updated_at' => now(),
                ]);
            } else {
                $id = DB::table('financeiro.plano_contas')->insertGetId([
                    'codigo' => $conta['codigo'],
                    'nome' => $conta['nome'],
                    'tipo' => $conta['tipo'],
                    'pai_id' => $paiId,
                    'aceita_lancamento' => $conta['aceita'],
                    'ativo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $codigosMap[$conta['codigo']] = $id;
        }

        // Centro de Custo padrão
        $centros = ['Educação Infantil', 'Ensino Fundamental I', 'Ensino Fundamental II', 'Ensino Médio', 'Administrativo / Geral'];
        foreach ($centros as $index => $centro) {
            $codigo = 'CC-0' . ($index + 1);
            $exist = DB::table('financeiro.centros_custo')->where('codigo', $codigo)->exists();
            if (!$exist) {
                DB::table('financeiro.centros_custo')->insert([
                    'codigo' => $codigo,
                    'nome' => $centro,
                    'ativo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
