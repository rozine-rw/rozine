<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\IdentityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('v1/identity', IdentityController::class)
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->name('api.v1.identity.show');
