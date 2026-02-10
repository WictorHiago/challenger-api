<?php

namespace App\Actions\Proposta;

use App\Contracts\PropostaAuditoriaInterface;
use App\Contracts\PropostaRepositoryInterface;
use App\Enums\AuditoriaEvento;
use App\Enums\PropostaStatus;
use App\Exceptions\PropostaStatusTransitionException;
use App\Models\Proposta;
use Illuminate\Http\Request;

class SubmeterPropostaAction
{
    public function __construct(
        private readonly PropostaRepositoryInterface $propostaRepository,
        private readonly PropostaAuditoriaInterface $auditoria
    ) {}

    public function execute(Proposta $proposta, Request $request): Proposta
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existente = $this->propostaRepository->findByIdempotencyKey($idempotencyKey);
            if ($existente !== null) {
                return $existente->load('cliente');
            }
        }

        if (!$proposta->status->podeTransicionarPara(PropostaStatus::SUBMITTED)) {
            throw PropostaStatusTransitionException::transicaoInvalida($proposta->status, PropostaStatus::SUBMITTED);
        }

        $statusAnterior = $proposta->status;
        $proposta = $this->propostaRepository->update($proposta, [
            'status' => PropostaStatus::SUBMITTED,
            'versao' => $proposta->versao + 1,
        ]);

        $this->auditoria->registrar($proposta, AuditoriaEvento::STATUS_CHANGED, [
            'de' => $statusAnterior->value,
            'para' => PropostaStatus::SUBMITTED->value,
        ], 'system');

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $this->propostaRepository->storeIdempotencyKey($idempotencyKey, $proposta->id);
        }

        return $proposta->load('cliente');
    }
}
