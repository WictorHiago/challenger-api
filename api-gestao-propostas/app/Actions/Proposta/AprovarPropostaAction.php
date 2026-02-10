<?php

namespace App\Actions\Proposta;

use App\Contracts\PropostaAuditoriaInterface;
use App\Contracts\PropostaRepositoryInterface;
use App\Enums\AuditoriaEvento;
use App\Enums\PropostaStatus;
use App\Exceptions\PropostaStatusTransitionException;
use App\Models\Proposta;

class AprovarPropostaAction
{
    public function __construct(
        private readonly PropostaRepositoryInterface $propostaRepository,
        private readonly PropostaAuditoriaInterface $auditoria
    ) {}

    public function execute(Proposta $proposta): Proposta
    {
        if (!$proposta->status->podeTransicionarPara(PropostaStatus::APPROVED)) {
            throw PropostaStatusTransitionException::transicaoInvalida($proposta->status, PropostaStatus::APPROVED);
        }

        $statusAnterior = $proposta->status;
        $proposta = $this->propostaRepository->update($proposta, [
            'status' => PropostaStatus::APPROVED,
            'versao' => $proposta->versao + 1,
        ]);

        $this->auditoria->registrar($proposta, AuditoriaEvento::STATUS_CHANGED, [
            'de' => $statusAnterior->value,
            'para' => PropostaStatus::APPROVED->value,
        ], 'system');

        return $proposta->load('cliente');
    }
}
