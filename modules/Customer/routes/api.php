<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\V1\CustomerAuth\RegisterCustomerController;
use Modules\Customer\Http\Controllers\V1\Customers\BlockCustomerController;
use Modules\Customer\Http\Controllers\V1\Customers\CreateCustomerController;
use Modules\Customer\Http\Controllers\V1\Customers\DeleteCustomerController;
use Modules\Customer\Http\Controllers\V1\Customers\GetCustomerController;
use Modules\Customer\Http\Controllers\V1\Customers\ListCustomersController;
use Modules\Customer\Http\Controllers\V1\Customers\UpdateCustomerController;

Route::post('v1/customer-auth/register', RegisterCustomerController::class)->middleware('throttle:login');

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::get('customers', ListCustomersController::class);
    Route::post('customers', CreateCustomerController::class);
    Route::get('customers/{customer}', GetCustomerController::class)->whereNumber('customer');
    Route::put('customers/{customer}', UpdateCustomerController::class)->whereNumber('customer');
    Route::put('customers/{customer}/block', BlockCustomerController::class)->whereNumber('customer');
    Route::delete('customers/{customer}', DeleteCustomerController::class)->whereNumber('customer');
});
