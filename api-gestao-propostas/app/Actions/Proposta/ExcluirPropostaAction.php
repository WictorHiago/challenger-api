<?php

namespace App\Actions\Proposta;

use App\Contracts\PropostaAuditoriaInterface;
use App\Enums\AuditoriaEvento;
use App\Models\Proposta;

class ExcluirPropostaAction
{
    public function __construct(
        private readonly PropostaAuditoriaInterface $auditoria
    ) {}

    /**
     * Executa exclusão lógica (soft delete) da proposta e registra evento na auditoria.
     */
    public function execute(Proposta $proposta): void
    {
        $payload = $proposta->only(['id', 'cliente_id', 'produto', 'valor_mensal', 'status', 'versao']);

        $this->auditoria->registrar($proposta, AuditoriaEvento::DELETED_LOGICAL, $payload, 'system');

        $proposta->delete();
    }
}
