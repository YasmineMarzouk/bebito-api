<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\V1\Settings\GetSettingsController;
use Modules\Core\Http\Controllers\V1\Settings\UpdateSettingsController;
use Modules\Core\Http\Controllers\V1\Translations\TranslateFrenchTextController;

Route::prefix('v1')->group(function (): void {
    Route::get('settings', GetSettingsController::class);
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::put('settings', UpdateSettingsController::class);
    Route::post('translations/french-to-english', TranslateFrenchTextController::class);
});
