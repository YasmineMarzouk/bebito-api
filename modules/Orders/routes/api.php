<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\V1\Orders\ApplyCouponToOrderController;
use Modules\Orders\Http\Controllers\V1\Orders\CreateCheckoutController;
use Modules\Orders\Http\Controllers\V1\Orders\ListOrdersController;

Route::prefix('v1')->group(function (): void {
    Route::post('checkout', CreateCheckoutController::class);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('orders', ListOrdersController::class);
        Route::put('orders/{order}/coupon', ApplyCouponToOrderController::class)->whereNumber('order');
    });
});
