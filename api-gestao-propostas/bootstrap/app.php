<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
            return response()->json(['message' => $e->getMessage(), 'errors' => new \stdClass], 422);
        });

        $exceptions->render(function (\App\Exceptions\ConflitoVersaoException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => new \stdClass], 409);
        });

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') && $request->expectsJson() && $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $status = $e->getStatusCode();
                return response()->json([
                    'message' => $e->getMessage() ?: \Symfony\Component\HttpFoundation\Response::$statusTexts[$status] ?? 'Erro',
                    'errors' => new \stdClass,
                ], $status);
            }
        });
    })->create();
