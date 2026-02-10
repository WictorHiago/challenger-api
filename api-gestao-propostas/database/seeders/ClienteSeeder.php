<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = [
            ['nome' => 'Maria Silva', 'email' => 'maria.silva@example.com', 'documento' => '52998224725'],
            ['nome' => 'João Santos', 'email' => 'joao.santos@example.com', 'documento' => '01242576231'],
            ['nome' => 'Ana Oliveira', 'email' => 'ana.oliveira@example.com', 'documento' => '12345678901'],
            ['nome' => 'Pedro Costa', 'email' => 'pedro.costa@example.com', 'documento' => '98765432100'],
            ['nome' => 'Empresa ABC Ltda', 'email' => 'contato@empresaabc.com', 'documento' => '11222333000181'],
        ];

        foreach ($clientes as $dados) {
            Cliente::firstOrCreate(
                ['email' => $dados['email']],
                $dados
            );
        }
    }
}
