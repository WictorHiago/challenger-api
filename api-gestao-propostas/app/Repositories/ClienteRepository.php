<?php

namespace App\Repositories;

use App\Contracts\ClienteRepositoryInterface;
use App\Models\Cliente;
use Illuminate\Support\Facades\Cache; // redis aqui (Cache usa Redis quando CACHE_STORE=redis)

class ClienteRepository implements ClienteRepositoryInterface
{
    private const IDEMPOTENCY_CACHE_PREFIX = 'idempotency:cliente:';
    private const IDEMPOTENCY_TTL = 86400; // 24 horas

    public function create(array $data): Cliente
    {
        return Cliente::query()->create($data);
    }

    public function findById(int $id): ?Cliente
    {
        return Cliente::query()->find($id);
    }

    public function findByEmail(string $email): ?Cliente
    {
        return Cliente::query()->where('email', $email)->first();
    }

    public function findByIdempotencyKey(string $key): ?Cliente
    {
        $clienteId = Cache::get(self::IDEMPOTENCY_CACHE_PREFIX . $key); // redis aqui

        if ($clienteId === null) {
            return null;
        }

        return $this->findById((int) $clienteId);
    }

    public function storeIdempotencyKey(string $key, int $clienteId): void
    {
        Cache::put(self::IDEMPOTENCY_CACHE_PREFIX . $key, $clienteId, self::IDEMPOTENCY_TTL); // redis aqui
    }
}
