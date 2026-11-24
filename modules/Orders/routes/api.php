<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\V1\Orders\ApplyCouponToOrderController;
use Modules\Orders\Http\Controllers\V1\Orders\CancelOrderController;
use Modules\Orders\Http\Controllers\V1\Orders\ConfirmOrderController;
use Modules\Orders\Http\Controllers\V1\Orders\CreateCheckoutController;
use Modules\Orders\Http\Controllers\V1\Orders\GetOrderController;
use Modules\Orders\Http\Controllers\V1\Orders\ListCustomerOrdersController;
use Modules\Orders\Http\Controllers\V1\Orders\ListOrdersController;
use Modules\Orders\Http\Controllers\V1\Orders\QuoteCheckoutController;
use Modules\Orders\Http\Controllers\V1\Orders\ReadyForDeliveryOrderController;
use Modules\Orders\Http\Controllers\V1\Orders\ReplaceOrderController;

Route::prefix('v1')->group(function (): void {
    Route::prefix('store')->group(function (): void {
        Route::post('checkout', CreateCheckoutController::class)->middleware('throttle:checkout');
        Route::post('checkout/quote', QuoteCheckoutController::class)->middleware('throttle:coupon-quote');
    });

    Route::middleware(['auth:customer', 'active.account:customer'])->prefix('customer')->group(function (): void {
        Route::get('orders', ListCustomerOrdersController::class);
    });

    Route::middleware(['auth:admin', 'active.account:admin'])->prefix('admin')->group(function (): void {
        Route::get('orders', ListOrdersController::class)->middleware('staff.permission:orders,read');
        Route::get('orders/{order}', GetOrderController::class)->middleware('staff.permission:orders,read')->whereNumber('order');
        Route::put('orders/{order}', ReplaceOrderController::class)->middleware('staff.permission:orders,write')->whereNumber('order');
        Route::post('orders/{order}/confirm', ConfirmOrderController::class)->middleware('staff.permission:orders,write')->whereNumber('order');
        Route::post('orders/{order}/preparing', ConfirmOrderController::class)->middleware('staff.permission:orders,write')->whereNumber('order');
        Route::post('orders/{order}/ready-for-delivery', ReadyForDeliveryOrderController::class)->middleware('staff.permission:orders,write')->whereNumber('order');
        Route::post('orders/{order}/ready', ReadyForDeliveryOrderController::class)->middleware('staff.permission:orders,write')->whereNumber('order');
        Route::post('orders/{order}/cancel', CancelOrderController::class)->middleware('staff.permission:orders,execute')->whereNumber('order');
        Route::put('orders/{order}/coupon', ApplyCouponToOrderController::class)->middleware('staff.permission:orders,write')->whereNumber('order');
    });
});
