<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Cliente\CriarClienteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\StoreClienteRequest;
use App\Http\Resources\Api\V1\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ClienteController extends Controller
{
    public function __construct(
        private readonly CriarClienteAction $criarClienteAction
    ) {}

    #[OA\Post(
        path: '/clientes',
        summary: 'Criar cliente',
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(
                name: 'Idempotency-Key',
                in: 'header',
                required: false,
                description: 'Chave para garantir idempotência (evita duplicatas)',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome', 'email', 'documento'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', example: 'João Silva'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@example.com'),
                    new OA\Property(property: 'documento', type: 'string', example: '12345678901', description: 'CPF (11 dígitos) ou CNPJ (14 dígitos)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cliente criado com sucesso', content: new OA\JsonContent(ref: '#/components/schemas/Cliente')),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = $this->criarClienteAction->execute($request);

        return (new ClienteResource($cliente))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/clientes/{id}',
        summary: 'Obter cliente por ID',
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Cliente')),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
        ]
    )]
    public function show(Cliente $cliente): ClienteResource
    {
        return new ClienteResource($cliente);
    }
}
