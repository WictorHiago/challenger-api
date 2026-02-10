<?php

namespace App\Services;

use App\Contracts\PropostaAuditoriaInterface;
use App\Enums\AuditoriaEvento;
use App\Models\Proposta;
use App\Models\PropostaAuditoria;

class PropostaAuditoriaService implements PropostaAuditoriaInterface
{
    public function registrar(Proposta $proposta, AuditoriaEvento $evento, array $payload = [], string $actor = 'system'): void
    {
        PropostaAuditoria::query()->create([
            'proposta_id' => $proposta->id,
            'actor' => $actor,
            'evento' => $evento,
            'payload' => $payload,
        ]);
    }
}
