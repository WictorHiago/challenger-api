<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ConflitoVersaoException extends HttpException
{
    public function __construct(string $message = 'Conflito de versão. A proposta foi alterada por outro processo.')
    {
        parent::__construct(409, $message);
    }
}
