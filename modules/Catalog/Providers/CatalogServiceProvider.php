<?php

namespace Modules\Catalog\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\CheckoutInventoryRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\ShopCatalogRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Contracts\Services\CatalogTranslationServiceInterface;
use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\Contracts\Services\PackCompositionValidationServiceInterface;
use Modules\Catalog\Events\CollectionPublished;
use Modules\Catalog\Listeners\SendCollectionPublishedEmails;
use Modules\Catalog\Repositories\CategoryRepository;
use Modules\Catalog\Repositories\CheckoutInventoryRepository;
use Modules\Catalog\Repositories\CollectionRepository;
use Modules\Catalog\Repositories\ProductRepository;
use Modules\Catalog\Repositories\ShopCatalogRepository;
use Modules\Catalog\Repositories\PackRepository;
use Modules\Catalog\Services\CatalogTranslationService;
use Modules\Catalog\Services\PackAvailabilityService;
use Modules\Catalog\Services\PackCompositionValidationService;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(CheckoutInventoryRepositoryInterface::class, CheckoutInventoryRepository::class);
        $this->app->bind(CollectionRepositoryInterface::class, CollectionRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ShopCatalogRepositoryInterface::class, ShopCatalogRepository::class);
        $this->app->bind(PackRepositoryInterface::class, PackRepository::class);
        $this->app->bind(CatalogTranslationServiceInterface::class, CatalogTranslationService::class);
        $this->app->bind(PackAvailabilityServiceInterface::class, PackAvailabilityService::class);
        $this->app->bind(PackCompositionValidationServiceInterface::class, PackCompositionValidationService::class);
    }

    public function boot(): void
    {
        Event::listen(CollectionPublished::class, SendCollectionPublishedEmails::class);
    }
}
