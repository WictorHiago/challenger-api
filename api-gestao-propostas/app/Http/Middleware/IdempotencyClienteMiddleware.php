<?php

namespace App\Http\Middleware;

use App\Contracts\ClienteRepositoryInterface;
use App\Http\Resources\Api\V1\ClienteResource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyClienteMiddleware
{
    public function __construct(
        private readonly ClienteRepositoryInterface $clienteRepository
    ) {}

    /**
     * Verifica Idempotency-Key antes da validação. Se encontrar cliente em cache, retorna early.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (empty($idempotencyKey)) {
            return $next($request);
        }

        $existente = $this->clienteRepository->findByIdempotencyKey($idempotencyKey);

        if ($existente !== null) {
            return (new ClienteResource($existente))
                ->response()
                ->setStatusCode(201);
        }

        return $next($request);
    }
}
