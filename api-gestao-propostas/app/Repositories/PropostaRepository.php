<?php

namespace App\Repositories;

use App\Contracts\PropostaRepositoryInterface;
use App\Models\Proposta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache; // redis aqui (Cache usa Redis quando CACHE_STORE=redis)

class PropostaRepository implements PropostaRepositoryInterface
{
    private const IDEMPOTENCY_CACHE_PREFIX = 'idempotency:proposta:';
    private const IDEMPOTENCY_TTL = 86400;

    public function create(array $data): Proposta
    {
        return Proposta::query()->create($data);
    }

    public function update(Proposta $proposta, array $data): Proposta
    {
        $proposta->update($data);
        return $proposta->fresh();
    }

    public function findById(int $id): ?Proposta
    {
        return Proposta::query()->find($id);
    }

    public function findByIdWithCliente(int $id): ?Proposta
    {
        return Proposta::query()->with('cliente')->find($id);
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Proposta::query()->with('cliente');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['cliente_id'])) {
            $query->where('cliente_id', $filters['cliente_id']);
        }
        if (!empty($filters['produto'])) {
            $query->whereRaw('produto ILIKE ?', ['%' . $filters['produto'] . '%']);
        }
        if (isset($filters['valor_min']) && $filters['valor_min'] !== '' && $filters['valor_min'] !== null) {
            $query->where('valor_mensal', '>=', (float) $filters['valor_min']);
        }
        if (isset($filters['valor_max']) && $filters['valor_max'] !== '' && $filters['valor_max'] !== null) {
            $query->where('valor_mensal', '<=', (float) $filters['valor_max']);
        }

        $ordenarPor = $filters['ordenar_por'] ?? 'created_at';
        $direcao = $filters['direcao'] ?? 'desc';
        $query->orderBy($ordenarPor, $direcao);

        return $query->paginate($perPage);
    }

    public function findByIdempotencyKey(string $key): ?Proposta
    {
        $propostaId = Cache::get(self::IDEMPOTENCY_CACHE_PREFIX . $key); // redis aqui

        if ($propostaId === null) {
            return null;
        }

        return $this->findById((int) $propostaId);
    }

    public function storeIdempotencyKey(string $key, int $propostaId): void
    {
        Cache::put(self::IDEMPOTENCY_CACHE_PREFIX . $key, $propostaId, self::IDEMPOTENCY_TTL); // redis aqui
    }
}
