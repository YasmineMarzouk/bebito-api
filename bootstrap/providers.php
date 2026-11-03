<?php

use App\Providers\AppServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Catalog\Providers\CatalogServiceProvider;
use Modules\Customer\Providers\CustomerServiceProvider;
use Modules\Core\Providers\CoreServiceProvider;
use Modules\Orders\Providers\OrdersServiceProvider;
use Modules\Promotions\Providers\PromotionsServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    AuthServiceProvider::class,
    CatalogServiceProvider::class,
    CustomerServiceProvider::class,
    PromotionsServiceProvider::class,
    OrdersServiceProvider::class,
];
