<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\V1\Auth\GetAuthenticatedUserController;
use Modules\Auth\Http\Controllers\V1\Auth\LoginController;
use Modules\Auth\Http\Controllers\V1\Auth\LogoutController;
use Modules\Auth\Http\Controllers\V1\CustomerAuth\CustomerLoginController;
use Modules\Auth\Http\Controllers\V1\CustomerAuth\GetAuthenticatedCustomerController;

Route::prefix('v1/auth')->group(function (): void {
    Route::post('login', LoginController::class)->middleware('throttle:login');
    Route::post('logout', LogoutController::class)->middleware('auth:sanctum');
    Route::get('user', GetAuthenticatedUserController::class)->middleware('auth:sanctum');
});

Route::prefix('v1/customer-auth')->group(function (): void {
    Route::post('login', CustomerLoginController::class)->middleware('throttle:login');
    Route::post('logout', LogoutController::class)->middleware('auth:sanctum');
    Route::get('user', GetAuthenticatedCustomerController::class)->middleware('auth:sanctum');
});
