<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\V1\CustomerAuth\RegisterCustomerController;
use Modules\Customer\Http\Controllers\V1\CustomerProfile\GetCustomerProfileController;
use Modules\Customer\Http\Controllers\V1\CustomerProfile\UpdateCustomerProfileController;
use Modules\Customer\Http\Controllers\V1\Customers\BlockCustomerController;
use Modules\Customer\Http\Controllers\V1\Customers\CreateCustomerController;
use Modules\Customer\Http\Controllers\V1\Customers\DeleteCustomerController;
use Modules\Customer\Http\Controllers\V1\Customers\GetCustomerController;
use Modules\Customer\Http\Controllers\V1\Customers\ListCustomersController;
use Modules\Customer\Http\Controllers\V1\Customers\UpdateCustomerController;

Route::post('v1/customer/auth/register', RegisterCustomerController::class)->middleware('throttle:login');

Route::middleware(['auth:customer', 'active.account:customer'])->prefix('v1/customer')->group(function (): void {
    Route::get('profile', GetCustomerProfileController::class);
    Route::put('profile', UpdateCustomerProfileController::class);
});

Route::middleware(['auth:admin', 'active.account:admin'])->prefix('v1/admin')->group(function (): void {
    Route::get('customers', ListCustomersController::class)->middleware('staff.permission:customers,read');
    Route::post('customers', CreateCustomerController::class)->middleware('staff.permission:customers,write');
    Route::get('customers/{customer}', GetCustomerController::class)->middleware('staff.permission:customers,read')->whereNumber('customer');
    Route::put('customers/{customer}', UpdateCustomerController::class)->middleware('staff.permission:customers,write')->whereNumber('customer');
    Route::put('customers/{customer}/block', BlockCustomerController::class)->middleware('staff.permission:customers,write')->whereNumber('customer');
    Route::delete('customers/{customer}', DeleteCustomerController::class)->middleware('staff.permission:customers,execute')->whereNumber('customer');
});
