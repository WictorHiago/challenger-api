<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(title: 'API Gestão de Propostas', version: '1.0.0', description: 'API REST para módulo de Gestão de Propostas'),
    servers: [new OA\Server(url: '/api/v1', description: 'API v1')]
)]
#[OA\Schema(
    schema: 'Cliente',
    title: 'Cliente',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'João Silva'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@example.com'),
        new OA\Property(property: 'documento', type: 'string', example: '12345678901'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class OpenApi
{
}
