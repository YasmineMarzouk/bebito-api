<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\V1\Orders\ApplyCouponToOrderController;
use Modules\Orders\Http\Controllers\V1\Orders\ListOrdersController;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::get('orders', ListOrdersController::class);
    Route::put('orders/{order}/coupon', ApplyCouponToOrderController::class)->whereNumber('order');
});
