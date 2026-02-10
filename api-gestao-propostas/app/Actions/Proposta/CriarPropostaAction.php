<?php

namespace App\Actions\Proposta;

use App\Contracts\ClienteRepositoryInterface;
use App\Contracts\PropostaAuditoriaInterface;
use App\Contracts\PropostaRepositoryInterface;
use App\Enums\AuditoriaEvento;
use App\Enums\PropostaOrigem;
use App\Enums\PropostaStatus;
use App\Models\Proposta;
use Illuminate\Http\Request;

class CriarPropostaAction
{
    public function __construct(
        private readonly PropostaRepositoryInterface $propostaRepository,
        private readonly PropostaAuditoriaInterface $auditoria,
        private readonly ClienteRepositoryInterface $clienteRepository
    ) {}

    public function execute(Request $request): Proposta
    {
        $cliente = $this->clienteRepository->findById((int) $request->input('cliente_id'));
        if ($cliente === null) {
            throw new \InvalidArgumentException('Cliente não encontrado.');
        }

        $data = [
            'cliente_id' => $cliente->id,
            'produto' => $request->input('produto'),
            'valor_mensal' => $request->input('valor_mensal'),
            'status' => PropostaStatus::DRAFT,
            'origem' => PropostaOrigem::from($request->input('origem', PropostaOrigem::API->value)),
            'versao' => 1,
        ];

        $proposta = $this->propostaRepository->create($data);
        $this->auditoria->registrar($proposta, AuditoriaEvento::CREATED, $proposta->toArray(), 'system');

        return $proposta;
    }
}
