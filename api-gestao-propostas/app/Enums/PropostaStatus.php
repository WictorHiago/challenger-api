<?php

namespace App\Enums;

enum PropostaStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case CANCELED = 'CANCELED';

    public function isFinal(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::REJECTED,
            self::CANCELED,
        ], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Rascunho',
            self::SUBMITTED => 'Submetida',
            self::APPROVED => 'Aprovada',
            self::REJECTED => 'Rejeitada',
            self::CANCELED => 'Cancelada',
        };
    }
}
