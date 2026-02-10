<?php

namespace App\Contracts;

use App\Enums\AuditoriaEvento;
use App\Models\Proposta;

interface PropostaAuditoriaInterface
{
    public function registrar(Proposta $proposta, AuditoriaEvento $evento, array $payload = [], string $actor = 'system'): void;
}
