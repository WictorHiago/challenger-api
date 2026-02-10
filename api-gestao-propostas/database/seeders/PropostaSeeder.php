<?php

namespace Database\Seeders;

use App\Enums\PropostaOrigem;
use App\Enums\PropostaStatus;
use App\Models\Cliente;
use App\Models\Proposta;
use Illuminate\Database\Seeder;

class PropostaSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Cliente::all();
        if ($clientes->isEmpty()) {
            return;
        }

        $propostas = [
            ['produto' => 'Seguro Residencial', 'valor_mensal' => 89.90, 'status' => PropostaStatus::DRAFT, 'origem' => PropostaOrigem::SITE],
            ['produto' => 'Seguro Automóvel', 'valor_mensal' => 199.00, 'status' => PropostaStatus::SUBMITTED, 'origem' => PropostaOrigem::APP],
            ['produto' => 'Seguro Vida', 'valor_mensal' => 149.50, 'status' => PropostaStatus::APPROVED, 'origem' => PropostaOrigem::API],
            ['produto' => 'Seguro Saúde', 'valor_mensal' => 350.00, 'status' => PropostaStatus::DRAFT, 'origem' => PropostaOrigem::API],
            ['produto' => 'Seguro Empresarial', 'valor_mensal' => 1200.00, 'status' => PropostaStatus::REJECTED, 'origem' => PropostaOrigem::SITE],
        ];

        foreach ($propostas as $i => $dados) {
            $cliente = $clientes->get($i % $clientes->count());
            Proposta::firstOrCreate(
                [
                    'cliente_id' => $cliente->id,
                    'produto' => $dados['produto'],
                ],
                [
                    'valor_mensal' => $dados['valor_mensal'],
                    'status' => $dados['status'],
                    'origem' => $dados['origem'],
                    'versao' => 1,
                ]
            );
        }
    }
}
