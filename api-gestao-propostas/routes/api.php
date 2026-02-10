<?php

use App\Http\Controllers\Api\V1\ClienteController;
use App\Http\Controllers\Api\V1\PropostaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('clientes', [ClienteController::class, 'store'])->middleware('idempotency.cliente');
    Route::get('clientes/{cliente}', [ClienteController::class, 'show']);

    Route::get('propostas', [PropostaController::class, 'index']);
    Route::post('propostas', [PropostaController::class, 'store']);
    Route::get('propostas/{proposta}', [PropostaController::class, 'show']);
    Route::patch('propostas/{proposta}', [PropostaController::class, 'update']);
    Route::post('propostas/{proposta}/submit', [PropostaController::class, 'submit']);
    Route::post('propostas/{proposta}/approve', [PropostaController::class, 'approve']);
    Route::post('propostas/{proposta}/reject', [PropostaController::class, 'reject']);
    Route::post('propostas/{proposta}/cancel', [PropostaController::class, 'cancel']);
    Route::get('propostas/{proposta}/auditoria', [PropostaController::class, 'auditoria']);
});
