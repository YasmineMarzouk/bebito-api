<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\V1\ContactMessages\ArchiveContactMessageController;
use Modules\Support\Http\Controllers\V1\ContactMessages\CreateContactMessageController;
use Modules\Support\Http\Controllers\V1\ContactMessages\DeleteContactMessageController;
use Modules\Support\Http\Controllers\V1\ContactMessages\ListContactMessagesController;
use Modules\Support\Http\Controllers\V1\ContactMessages\MarkContactMessageReadController;
use Modules\Support\Http\Controllers\V1\NewsletterSubscriptions\CreateNewsletterSubscriptionController;

Route::prefix('v1')->group(function (): void {
    Route::post('contact-messages', CreateContactMessageController::class)->middleware('throttle:10,1');

    Route::prefix('store')->group(function (): void {
        Route::post('newsletter-subscriptions', CreateNewsletterSubscriptionController::class)->middleware('throttle:10,1');
    });
});

Route::middleware(['auth:admin', 'active.account:admin'])->prefix('v1/admin')->group(function (): void {
    Route::get('messages', ListContactMessagesController::class)->middleware('staff.permission:messages,read');
    Route::patch('messages/{message}/read', MarkContactMessageReadController::class)->middleware('staff.permission:messages,write')->whereNumber('message');
    Route::patch('messages/{message}/archive', ArchiveContactMessageController::class)->middleware('staff.permission:messages,write')->whereNumber('message');
    Route::delete('messages/{message}', DeleteContactMessageController::class)->middleware('staff.permission:messages,execute')->whereNumber('message');
});
