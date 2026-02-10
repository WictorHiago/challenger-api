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

    /** @return list<PropostaStatus> */
    public function transicoesPermitidas(): array
    {
        return match ($this) {
            self::DRAFT => [self::SUBMITTED],
            self::SUBMITTED => [self::APPROVED, self::REJECTED, self::CANCELED],
            self::APPROVED, self::REJECTED, self::CANCELED => [],
        };
    }

    public function podeTransicionarPara(PropostaStatus $novoStatus): bool
    {
        return in_array($novoStatus, $this->transicoesPermitidas(), true);
    }
}
