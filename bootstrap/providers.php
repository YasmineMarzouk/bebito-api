<?php

use App\Providers\AppServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Catalog\Providers\CatalogServiceProvider;
use Modules\Core\Providers\CoreServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    AuthServiceProvider::class,
    CatalogServiceProvider::class,
];
