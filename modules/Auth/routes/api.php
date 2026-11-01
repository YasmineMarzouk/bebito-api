<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\V1\Auth\GetAuthenticatedUserController;
use Modules\Auth\Http\Controllers\V1\Auth\LoginController;
use Modules\Auth\Http\Controllers\V1\Auth\LogoutController;

Route::prefix('v1/auth')->group(function (): void {
    Route::post('login', LoginController::class)->middleware('throttle:login');
    Route::post('logout', LogoutController::class)->middleware('auth:sanctum');
    Route::get('user', GetAuthenticatedUserController::class)->middleware('auth:sanctum');
});
