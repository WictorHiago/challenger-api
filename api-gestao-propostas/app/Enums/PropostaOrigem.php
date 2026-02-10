<?php

namespace App\Enums;

enum PropostaOrigem: string
{
    case APP = 'APP';
    case SITE = 'SITE';
    case API = 'API';

    public function label(): string
    {
        return match ($this) {
            self::APP => 'Aplicativo',
            self::SITE => 'Site',
            self::API => 'API',
        };
    }
}
