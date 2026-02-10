<?php

namespace App\Enums;

enum AuditoriaEvento: string
{
    case CREATED = 'CREATED';
    case UPDATED_FIELDS = 'UPDATED_FIELDS';
    case STATUS_CHANGED = 'STATUS_CHANGED';
    case DELETED_LOGICAL = 'DELETED_LOGICAL';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Criada',
            self::UPDATED_FIELDS => 'Campos alterados',
            self::STATUS_CHANGED => 'Status alterado',
            self::DELETED_LOGICAL => 'Exclusão lógica',
        };
    }
}
