<?php

use App\Providers\AppServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Catalog\Providers\CatalogServiceProvider;
use Modules\Core\Providers\CoreServiceProvider;
use Modules\Customer\Providers\CustomerServiceProvider;
use Modules\Inventory\Providers\InventoryServiceProvider;
use Modules\Orders\Providers\OrdersServiceProvider;
use Modules\Promotions\Providers\PromotionsServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    InventoryServiceProvider::class,
    AuthServiceProvider::class,
    CatalogServiceProvider::class,
    CustomerServiceProvider::class,
    PromotionsServiceProvider::class,
    OrdersServiceProvider::class,
];
