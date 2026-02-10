<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Proposta\AprovarPropostaAction;
use OpenApi\Attributes as OA;
use App\Actions\Proposta\AtualizarPropostaAction;
use App\Actions\Proposta\CancelarPropostaAction;
use App\Actions\Proposta\CriarPropostaAction;
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
        private readonly PropostaRepositoryInterface $propostaRepository
    ) {}

    #[OA\Post(path: '/propostas', summary: 'Criar proposta', tags: ['Propostas'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['cliente_id', 'produto', 'valor_mensal'], properties: [new OA\Property(property: 'cliente_id', type: 'integer'), new OA\Property(property: 'produto', type: 'string'), new OA\Property(property: 'valor_mensal', type: 'number'), new OA\Property(property: 'origem', type: 'string', enum: ['APP', 'SITE', 'API'])])), responses: [new OA\Response(response: 201, description: 'Proposta criada', content: new OA\JsonContent(ref: '#/components/schemas/Proposta'))])]
    public function store(StorePropostaRequest $request): JsonResponse
    {
        $proposta = $this->criarPropostaAction->execute($request);
        return (new PropostaResource($proposta))->response()->setStatusCode(201);
    }

    #[OA\Get(path: '/propostas/{id}', summary: 'Obter proposta por ID', tags: ['Propostas'], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Proposta encontrada', content: new OA\JsonContent(ref: '#/components/schemas/Proposta')), new OA\Response(response: 404, description: 'Não encontrada')])]
    public function show(Proposta $proposta): PropostaResource
    {
        $proposta->load('cliente');
        return new PropostaResource($proposta);
    }

    public function update(UpdatePropostaRequest $request, Proposta $proposta): JsonResponse|PropostaResource
    {
        $proposta = $this->atualizarPropostaAction->execute($proposta, $request);
        return new PropostaResource($proposta);
    }

    public function submit(Request $request, Proposta $proposta): PropostaResource
    {
        $proposta = $this->submeterPropostaAction->execute($proposta, $request);
        return new PropostaResource($proposta);
    }

    public function approve(Proposta $proposta): PropostaResource
    {
        $proposta = $this->aprovarPropostaAction->execute($proposta);
        return new PropostaResource($proposta);
    }

    public function reject(Proposta $proposta): PropostaResource
    {
        $proposta = $this->rejeitarPropostaAction->execute($proposta);
        return new PropostaResource($proposta);
    }

    public function cancel(Proposta $proposta): PropostaResource
    {
        $proposta = $this->cancelarPropostaAction->execute($proposta);
        return new PropostaResource($proposta);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'cliente_id', 'produto', 'ordenar_por', 'direcao']);
        $perPage = min((int) $request->input('per_page', 15), 100);
        $propostas = $this->propostaRepository->list($filters, $perPage);
        return PropostaResource::collection($propostas)->response();
    }

    public function auditoria(Proposta $proposta): JsonResponse
    {
        $auditorias = $proposta->auditorias()->orderByDesc('created_at')->get();
        return PropostaAuditoriaResource::collection($auditorias)->response();
    }
}
