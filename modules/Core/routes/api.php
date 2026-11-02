<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\V1\Translations\TranslateFrenchTextController;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::post('translations/french-to-english', TranslateFrenchTextController::class);
});
