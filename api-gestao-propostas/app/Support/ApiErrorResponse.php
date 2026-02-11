<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiErrorResponse
{
    /**
     * Retorna resposta JSON padronizada de erro para a API.
     *
     * @param  string  $message  Mensagem amigável (não expõe detalhes técnicos ou IDs)
     * @param  int  $status  Código HTTP (400, 404, 409, 422, etc.)
     * @param  array<string, array<int, string>>  $errors  Erros de validação por campo
     */
    public static function make(string $message, int $status = 422, array $errors = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => empty($errors) ? new \stdClass : $errors,
        ], $status);
    }
}
