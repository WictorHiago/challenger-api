<?php

namespace App\Contracts;

use App\Models\Cliente;

interface ClienteRepositoryInterface
{
    public function create(array $data): Cliente;

    public function findById(int $id): ?Cliente;

    public function findByEmail(string $email): ?Cliente;

    public function findByIdempotencyKey(string $key): ?Cliente;

    public function storeIdempotencyKey(string $key, int $clienteId): void;
}
