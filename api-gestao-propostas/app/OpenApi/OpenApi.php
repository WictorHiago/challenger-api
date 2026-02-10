<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        title: 'API Gestão de Propostas',
        version: '1.0.0',
        description: 'API REST para gestão de clientes e propostas. Suporta idempotência via header Idempotency-Key e optimistic lock em atualizações.'
    ),
    servers: [
        new OA\Server(url: 'http://localhost:8000/api/v1', description: 'Local'),
        new OA\Server(url: '/api/v1', description: 'Relativo'),
    ]
)]
#[OA\Schema(
    schema: 'Cliente',
    title: 'Cliente',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'João Silva'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@example.com'),
        new OA\Property(property: 'documento', type: 'string', example: '12345678901', description: 'CPF (11 dígitos) ou CNPJ (14 dígitos)'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Proposta',
    title: 'Proposta',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
        new OA\Property(property: 'produto', type: 'string', example: 'Seguro Auto'),
        new OA\Property(property: 'valor_mensal', type: 'number', format: 'float', example: 150.00),
        new OA\Property(property: 'status', type: 'string', enum: ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'CANCELED']),
        new OA\Property(property: 'origem', type: 'string', enum: ['APP', 'SITE', 'API']),
        new OA\Property(property: 'versao', type: 'integer', example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PropostaAuditoria',
    title: 'PropostaAuditoria',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'proposta_id', type: 'integer', example: 1),
        new OA\Property(property: 'actor', type: 'string', example: 'system'),
        new OA\Property(property: 'evento', type: 'string', enum: ['CREATED', 'UPDATED_FIELDS', 'STATUS_CHANGED', 'DELETED_LOGICAL']),
        new OA\Property(property: 'payload', type: 'object', description: 'Dados da alteração'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Error',
    title: 'Error',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Mensagem de erro'),
        new OA\Property(property: 'errors', type: 'object', description: 'Detalhes de validação (quando aplicável)'),
    ]
)]
class OpenApi
{
}
