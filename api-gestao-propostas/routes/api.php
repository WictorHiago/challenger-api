<?php

use App\Http\Controllers\Api\V1\ClienteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('clientes', ClienteController::class)->only(['store', 'show']);
});
