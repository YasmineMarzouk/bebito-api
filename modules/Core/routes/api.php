<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\V1\Translations\TranslateFrenchTextController;
use Modules\Core\Http\Controllers\V1\Settings\GetSettingsController;
use Modules\Core\Http\Controllers\V1\Settings\UpdateSettingsController;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::get('settings', GetSettingsController::class);
    Route::put('settings', UpdateSettingsController::class);
    Route::post('translations/french-to-english', TranslateFrenchTextController::class);
});
