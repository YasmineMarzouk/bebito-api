<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\V1\Analytics\GetDashboardAnalyticsController;
use Modules\Core\Http\Controllers\V1\Analytics\GetDashboardOverviewController;
use Modules\Core\Http\Controllers\V1\Settings\GetSettingsController;
use Modules\Core\Http\Controllers\V1\Settings\ListHomepageInstagramGalleryController;
use Modules\Core\Http\Controllers\V1\Settings\UpdateHomepageInstagramGalleryController;
use Modules\Core\Http\Controllers\V1\Settings\UpdateSettingsController;
use Modules\Core\Http\Controllers\V1\Translations\TranslateFrenchTextController;

Route::prefix('v1')->group(function (): void {
    Route::get('settings', GetSettingsController::class);
    Route::get('homepage/instagram-images', ListHomepageInstagramGalleryController::class);
});

Route::middleware(['auth:admin', 'active.account:admin'])->prefix('v1/admin')->group(function (): void {
    Route::get('analytics', GetDashboardAnalyticsController::class)->middleware('staff.permission:analytics,read');
    Route::get('dashboard', GetDashboardOverviewController::class)->middleware('staff.permission:analytics,read');
    Route::put('settings', UpdateSettingsController::class)->middleware('staff.permission:settings,write');
    Route::post('settings/homepage-instagram-images', UpdateHomepageInstagramGalleryController::class)->middleware('staff.permission:settings,write');
    Route::post('translations/french-to-english', TranslateFrenchTextController::class)->middleware('staff.permission:settings,write');
});
