<?php

namespace App\Actions\Cliente;

use App\Contracts\ClienteRepositoryInterface;
use App\Models\Cliente;
use Illuminate\Http\Request;

class CriarClienteAction
{
    public function __construct(
        private readonly ClienteRepositoryInterface $clienteRepository
    ) {}

    public function execute(Request $request): Cliente
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existente = $this->clienteRepository->findByIdempotencyKey($idempotencyKey);
            if ($existente !== null) {
                return $existente;
            }
        }

        $cliente = $this->clienteRepository->create($request->only(['nome', 'email', 'documento']));

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $this->clienteRepository->storeIdempotencyKey($idempotencyKey, $cliente->id);
        }

        return $cliente;
    }
}
