<?php

use Illuminate\Support\Facades\Route;
use Modules\Promotions\Http\Controllers\V1\Coupons\CreateCouponController;
use Modules\Promotions\Http\Controllers\V1\Coupons\DeleteCouponController;
use Modules\Promotions\Http\Controllers\V1\Coupons\GetCouponController;
use Modules\Promotions\Http\Controllers\V1\Coupons\ListCouponsController;
use Modules\Promotions\Http\Controllers\V1\Coupons\UpdateCouponController;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::get('coupons', ListCouponsController::class);
    Route::post('coupons', CreateCouponController::class);
    Route::get('coupons/{coupon}', GetCouponController::class)->whereNumber('coupon');
    Route::put('coupons/{coupon}', UpdateCouponController::class)->whereNumber('coupon');
    Route::delete('coupons/{coupon}', DeleteCouponController::class)->whereNumber('coupon');
});
