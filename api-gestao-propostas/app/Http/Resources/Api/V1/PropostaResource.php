<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Proposta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Proposta */
class PropostaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'cliente' => $this->whenLoaded('cliente', fn () => new ClienteResource($this->cliente)),
            'produto' => $this->produto,
            'valor_mensal' => (float) $this->valor_mensal,
            'status' => $this->status->value,
            'origem' => $this->origem->value,
            'versao' => $this->versao,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
