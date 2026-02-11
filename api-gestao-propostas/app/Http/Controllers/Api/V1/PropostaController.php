<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Proposta\AprovarPropostaAction;
use OpenApi\Attributes as OA;
use App\Actions\Proposta\AtualizarPropostaAction;
use App\Actions\Proposta\CancelarPropostaAction;
use App\Actions\Proposta\CriarPropostaAction;
use App\Actions\Proposta\ExcluirPropostaAction;
use App\Actions\Proposta\RejeitarPropostaAction;
use App\Actions\Proposta\SubmeterPropostaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Proposta\StorePropostaRequest;
use App\Http\Requests\Proposta\UpdatePropostaRequest;
use App\Http\Resources\Api\V1\PropostaAuditoriaResource;
use App\Http\Resources\Api\V1\PropostaResource;
use App\Models\Proposta;
use App\Contracts\PropostaRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropostaController extends Controller
{
    public function __construct(
        private readonly CriarPropostaAction $criarPropostaAction,
        private readonly AtualizarPropostaAction $atualizarPropostaAction,
        private readonly SubmeterPropostaAction $submeterPropostaAction,
        private readonly AprovarPropostaAction $aprovarPropostaAction,
        private readonly RejeitarPropostaAction $rejeitarPropostaAction,
        private readonly CancelarPropostaAction $cancelarPropostaAction,
        private readonly ExcluirPropostaAction $excluirPropostaAction,
        private readonly PropostaRepositoryInterface $propostaRepository
    ) {}

    #[OA\Post(
        path: '/propostas',
        summary: 'Criar proposta',
        tags: ['Propostas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cliente_id', 'produto', 'valor_mensal'],
                properties: [
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
                    new OA\Property(property: 'produto', type: 'string', example: 'Seguro Auto'),
                    new OA\Property(property: 'valor_mensal', type: 'number', example: 150.00),
                    new OA\Property(property: 'origem', type: 'string', enum: ['APP', 'SITE', 'API'], description: 'Opcional, default: API'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Proposta criada', content: new OA\JsonContent(ref: '#/components/schemas/Proposta')),
            new OA\Response(response: 422, description: 'Erro de validação ou regra de negócio', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function store(StorePropostaRequest $request): JsonResponse
    {
        $proposta = $this->criarPropostaAction->execute($request);
        return (new PropostaResource($proposta))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/propostas/{proposta}',
        summary: 'Obter proposta por ID',
        tags: ['Propostas'],
        parameters: [new OA\Parameter(name: 'proposta', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID da proposta')],
        responses: [
            new OA\Response(response: 200, description: 'Proposta encontrada', content: new OA\JsonContent(ref: '#/components/schemas/Proposta')),
            new OA\Response(response: 404, description: 'Proposta não encontrada', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function show(Proposta $proposta): PropostaResource
    {
        $proposta->load('cliente');
        return new PropostaResource($proposta);
    }

    #[OA\Patch(
        path: '/propostas/{proposta}',
        summary: 'Atualizar proposta',
        description: 'Atualiza campos da proposta em DRAFT. Requer versão para optimistic lock. Retorna 409 se versão desatualizada.',
        tags: ['Propostas'],
        parameters: [new OA\Parameter(name: 'proposta', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['versao'],
                properties: [
                    new OA\Property(property: 'versao', type: 'integer', example: 1, description: 'Versão atual para optimistic lock'),
                    new OA\Property(property: 'produto', type: 'string', example: 'Seguro Residencial'),
                    new OA\Property(property: 'valor_mensal', type: 'number', example: 199.90),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Proposta atualizada', content: new OA\JsonContent(ref: '#/components/schemas/Proposta')),
            new OA\Response(response: 409, description: 'Conflito de versão', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Apenas propostas em DRAFT podem ser editadas', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function update(UpdatePropostaRequest $request, Proposta $proposta): JsonResponse|PropostaResource
    {
        $proposta = $this->atualizarPropostaAction->execute($proposta, $request);
        return new PropostaResource($proposta);
    }

    #[OA\Post(
        path: '/propostas/{proposta}/submit',
        summary: 'Submeter proposta',
        description: 'Transiciona DRAFT → SUBMITTED. Aceita Idempotency-Key para evitar duplicatas em retries.',
        tags: ['Propostas'],
        parameters: [
            new OA\Parameter(name: 'proposta', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'Idempotency-Key', in: 'header', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Proposta submetida', content: new OA\JsonContent(ref: '#/components/schemas/Proposta')),
            new OA\Response(response: 422, description: 'Transição inválida ou proposta não está em DRAFT', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function submit(Request $request, Proposta $proposta): PropostaResource
    {
        $proposta = $this->submeterPropostaAction->execute($proposta, $request);
        return new PropostaResource($proposta);
    }

    #[OA\Post(
        path: '/propostas/{proposta}/approve',
        summary: 'Aprovar proposta',
        description: 'Transiciona SUBMITTED → APPROVED',
        tags: ['Propostas'],
        parameters: [new OA\Parameter(name: 'proposta', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Proposta aprovada', content: new OA\JsonContent(ref: '#/components/schemas/Proposta')),
            new OA\Response(response: 422, description: 'Transição inválida', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function approve(Proposta $proposta): PropostaResource
    {
        $proposta = $this->aprovarPropostaAction->execute($proposta);
        return new PropostaResource($proposta);
    }

    #[OA\Post(
        path: '/propostas/{proposta}/reject',
        summary: 'Rejeitar proposta',
        description: 'Transiciona SUBMITTED → REJECTED',
        tags: ['Propostas'],
        parameters: [new OA\Parameter(name: 'proposta', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Proposta rejeitada', content: new OA\JsonContent(ref: '#/components/schemas/Proposta')),
            new OA\Response(response: 422, description: 'Transição inválida', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function reject(Proposta $proposta): PropostaResource
    {
        $proposta = $this->rejeitarPropostaAction->execute($proposta);
        return new PropostaResource($proposta);
    }

    #[OA\Post(
        path: '/propostas/{proposta}/cancel',
        summary: 'Cancelar proposta',
        description: 'Transiciona SUBMITTED → CANCELED',
        tags: ['Propostas'],
        parameters: [new OA\Parameter(name: 'proposta', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Proposta cancelada', content: new OA\JsonContent(ref: '#/components/schemas/Proposta')),
            new OA\Response(response: 422, description: 'Transição inválida', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function cancel(Proposta $proposta): PropostaResource
    {
        $proposta = $this->cancelarPropostaAction->execute($proposta);
        return new PropostaResource($proposta);
    }

    #[OA\Delete(
        path: '/propostas/{proposta}',
        summary: 'Excluir proposta (soft delete)',
        description: 'Exclusão lógica: marca deleted_at e registra evento DELETED_LOGICAL na auditoria. GET na proposta excluída retorna 404.',
        tags: ['Propostas'],
        parameters: [new OA\Parameter(name: 'proposta', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Proposta excluída com sucesso'),
            new OA\Response(response: 404, description: 'Proposta não encontrada', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function destroy(Proposta $proposta): JsonResponse
    {
        $this->excluirPropostaAction->execute($proposta);
        return response()->json(null, 204);
    }

    #[OA\Get(
        path: '/propostas',
        summary: 'Listar propostas',
        description: 'Lista propostas com filtros, ordenação e paginação',
        tags: ['Propostas'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'CANCELED'], example: 'DRAFT')),
            new OA\Parameter(name: 'cliente_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'produto', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'Seguro'), description: 'Busca parcial (ILIKE)'),
            new OA\Parameter(name: 'valor_min', in: 'query', required: false, schema: new OA\Schema(type: 'number', example: 100), description: 'Valor mensal mínimo'),
            new OA\Parameter(name: 'valor_max', in: 'query', required: false, schema: new OA\Schema(type: 'number', example: 500), description: 'Valor mensal máximo'),
            new OA\Parameter(name: 'ordenar_por', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['created_at', 'valor_mensal', 'produto', 'status'], example: 'created_at'), description: 'Campo para ordenação'),
            new OA\Parameter(name: 'direcao', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'desc'), description: 'Direção da ordenação'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 15), description: 'Itens por página (máx. 100, default 15)'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada de propostas'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'cliente_id', 'produto', 'valor_min', 'valor_max', 'ordenar_por', 'direcao']);
        $perPage = min((int) $request->input('per_page', 15), 100);
        $propostas = $this->propostaRepository->list($filters, $perPage);
        return PropostaResource::collection($propostas)->response();
    }

    #[OA\Get(
        path: '/propostas/{proposta}/auditoria',
        summary: 'Histórico de auditoria',
        description: 'Retorna o histórico de alterações da proposta',
        tags: ['Propostas'],
        parameters: [new OA\Parameter(name: 'proposta', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Lista de eventos de auditoria', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/PropostaAuditoria'))),
            new OA\Response(response: 404, description: 'Proposta não encontrada', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function auditoria(Proposta $proposta): JsonResponse
    {
        $auditorias = $proposta->auditorias()->orderByDesc('created_at')->get();
        return PropostaAuditoriaResource::collection($auditorias)->response();
    }
}
