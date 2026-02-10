<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PropostaAuditoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PropostaAuditoria */
class PropostaAuditoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposta_id' => $this->proposta_id,
            'actor' => $this->actor,
            'evento' => $this->evento->value,
            'payload' => $this->payload,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
