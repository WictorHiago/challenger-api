<?php

namespace App\Contracts;

use App\Models\Proposta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PropostaRepositoryInterface
{
    public function create(array $data): Proposta;

    public function update(Proposta $proposta, array $data): Proposta;

    public function findById(int $id): ?Proposta;

    public function findByIdWithCliente(int $id): ?Proposta;

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findByIdempotencyKey(string $key): ?Proposta;

    public function storeIdempotencyKey(string $key, int $propostaId): void;
}
