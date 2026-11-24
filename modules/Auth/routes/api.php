<?php

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use Modules\Auth\Http\Controllers\V1\Auth\GetAuthenticatedUserController;
use Modules\Auth\Http\Controllers\V1\Auth\LoginController;
use Modules\Auth\Http\Controllers\V1\Auth\LogoutController;
use Modules\Auth\Http\Controllers\V1\CustomerAuth\CustomerLoginController;
use Modules\Auth\Http\Controllers\V1\CustomerAuth\GetAuthenticatedCustomerController;

Route::prefix('v1/admin')->group(function (): void {
    Route::get('csrf-cookie', [CsrfCookieController::class, 'show']);

    Route::prefix('auth')->group(function (): void {
        Route::post('login', LoginController::class)->middleware('throttle:login');
        Route::post('logout', LogoutController::class)->middleware('auth:admin');
        Route::get('user', GetAuthenticatedUserController::class)->middleware(['auth:admin', 'active.account:admin']);
    });
});

Route::prefix('v1/customer')->group(function (): void {
    Route::get('csrf-cookie', [CsrfCookieController::class, 'show']);

    Route::prefix('auth')->group(function (): void {
        Route::post('login', CustomerLoginController::class)->middleware('throttle:login');
        Route::post('logout', LogoutController::class)->middleware('auth:customer');
        Route::get('user', GetAuthenticatedCustomerController::class)->middleware(['auth:customer', 'active.account:customer']);
    });
});
