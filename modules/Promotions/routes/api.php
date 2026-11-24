<?php

use Illuminate\Support\Facades\Route;
use Modules\Promotions\Http\Controllers\V1\Coupons\CreateCouponController;
use Modules\Promotions\Http\Controllers\V1\Coupons\DeleteCouponController;
use Modules\Promotions\Http\Controllers\V1\Coupons\GetCouponController;
use Modules\Promotions\Http\Controllers\V1\Coupons\ListCouponsController;
use Modules\Promotions\Http\Controllers\V1\Coupons\UpdateCouponController;

Route::middleware(['auth:admin', 'active.account:admin'])->prefix('v1/admin')->group(function (): void {
    Route::get('coupons', ListCouponsController::class)->middleware('staff.permission:coupons,read');
    Route::post('coupons', CreateCouponController::class)->middleware('staff.permission:coupons,write');
    Route::get('coupons/{coupon}', GetCouponController::class)->middleware('staff.permission:coupons,read')->whereNumber('coupon');
    Route::put('coupons/{coupon}', UpdateCouponController::class)->middleware('staff.permission:coupons,write')->whereNumber('coupon');
    Route::delete('coupons/{coupon}', DeleteCouponController::class)->middleware('staff.permission:coupons,execute')->whereNumber('coupon');
});
