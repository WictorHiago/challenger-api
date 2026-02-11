<?php

use App\Support\ApiErrorResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'idempotency.cliente' => \App\Http\Middleware\IdempotencyClienteMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\PropostaStatusTransitionException $e) {
            return ApiErrorResponse::make($e->getMessage(), 422);
        });

        $exceptions->render(function (\App\Exceptions\ConflitoVersaoException $e) {
            return ApiErrorResponse::make($e->getMessage(), 409);
        });

        $exceptions->render(function (NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $previous = $e->getPrevious();
                if ($previous instanceof ModelNotFoundException) {
                    $model = class_basename($previous->getModel());
                    $message = match ($model) {
                        'Cliente' => 'Cliente não encontrado.',
                        'Proposta' => 'Proposta não encontrada.',
                        default => 'Recurso não encontrado.',
                    };
                    return ApiErrorResponse::make($message, 404);
                }
                return ApiErrorResponse::make('Recurso não encontrado.', 404);
            }
        });

        $exceptions->render(function (HttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: (\Symfony\Component\HttpFoundation\Response::$statusTexts[$status] ?? 'Erro');
                if (str_contains($message, 'No query results') || str_contains($message, 'App\\Models\\')) {
                    $message = 'Recurso não encontrado.';
                }
                return ApiErrorResponse::make($message, $status);
            }
        });
    })->create();
