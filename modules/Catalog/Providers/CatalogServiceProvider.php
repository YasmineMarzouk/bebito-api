<?php

namespace Modules\Catalog\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\CheckoutInventoryRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\Contracts\Services\CatalogTranslationServiceInterface;
use Modules\Catalog\Repositories\CategoryRepository;
use Modules\Catalog\Repositories\CheckoutInventoryRepository;
use Modules\Catalog\Repositories\CollectionRepository;
use Modules\Catalog\Repositories\ProductRepository;
use Modules\Catalog\Services\CatalogTranslationService;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(CheckoutInventoryRepositoryInterface::class, CheckoutInventoryRepository::class);
        $this->app->bind(CollectionRepositoryInterface::class, CollectionRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(CatalogTranslationServiceInterface::class, CatalogTranslationService::class);
    }
}
