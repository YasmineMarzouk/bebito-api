<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\V1\Categories\CreateCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\DeleteCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\DeleteMainCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\GetCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\ListCategoriesController;
use Modules\Catalog\Http\Controllers\V1\Categories\ListHomepageCategoriesController;
use Modules\Catalog\Http\Controllers\V1\Categories\ListMainCategoriesController;
use Modules\Catalog\Http\Controllers\V1\Categories\ListShopCategoriesController;
use Modules\Catalog\Http\Controllers\V1\Categories\UpdateCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\UpdateMainCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\UploadCategoryBannerController;
use Modules\Catalog\Http\Controllers\V1\Collections\CreateCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\DeleteCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\GetCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\ListCollectionsController;
use Modules\Catalog\Http\Controllers\V1\Collections\ListShopCollectionsController;
use Modules\Catalog\Http\Controllers\V1\Collections\UpdateCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\UploadCollectionBannerController;
use Modules\Catalog\Http\Controllers\V1\Packs\CreatePackController;
use Modules\Catalog\Http\Controllers\V1\Packs\DeletePackController;
use Modules\Catalog\Http\Controllers\V1\Packs\DeletePackImageController;
use Modules\Catalog\Http\Controllers\V1\Packs\GetPackController;
use Modules\Catalog\Http\Controllers\V1\Packs\GetShopPackController;
use Modules\Catalog\Http\Controllers\V1\Packs\ListPacksController;
use Modules\Catalog\Http\Controllers\V1\Packs\ListShopPacksController;
use Modules\Catalog\Http\Controllers\V1\Packs\SetPackPrimaryImageController;
use Modules\Catalog\Http\Controllers\V1\Packs\UpdatePackController;
use Modules\Catalog\Http\Controllers\V1\Packs\UpdatePackStatusController;
use Modules\Catalog\Http\Controllers\V1\Products\CreateProductController;
use Modules\Catalog\Http\Controllers\V1\Products\DeleteBestsellerController;
use Modules\Catalog\Http\Controllers\V1\Products\DeleteProductController;
use Modules\Catalog\Http\Controllers\V1\Products\DeleteProductImageController;
use Modules\Catalog\Http\Controllers\V1\Products\GetProductController;
use Modules\Catalog\Http\Controllers\V1\Products\GetShopProductController;
use Modules\Catalog\Http\Controllers\V1\Products\ListBestsellersController;
use Modules\Catalog\Http\Controllers\V1\Products\ListHomepageProductsController;
use Modules\Catalog\Http\Controllers\V1\Products\ListProductsController;
use Modules\Catalog\Http\Controllers\V1\Products\ListShopProductsController;
use Modules\Catalog\Http\Controllers\V1\Products\SetProductPrimaryImageController;
use Modules\Catalog\Http\Controllers\V1\Products\UpdateBestsellerController;
use Modules\Catalog\Http\Controllers\V1\Products\UpdateProductController;
use Modules\Catalog\Http\Controllers\V1\Products\UpdateProductStatusController;
use Modules\Catalog\Http\Controllers\V1\Shop\ListShopCatalogController;

Route::prefix('v1')->group(function (): void {
    Route::get('shop', ListShopCatalogController::class);
    Route::get('homepage/categories', ListHomepageCategoriesController::class);
    Route::get('homepage/products', ListHomepageProductsController::class);
    Route::get('shop/categories', ListShopCategoriesController::class);
    Route::get('shop/products', ListShopProductsController::class);
    Route::get('shop/products/{identifier}', GetShopProductController::class);
    Route::get('shop/packs', ListShopPacksController::class);
    Route::get('shop/packs/{identifier}', GetShopPackController::class);
    Route::get('shop/collections', ListShopCollectionsController::class);
});

Route::middleware(['auth:admin', 'active.account:admin'])->prefix('v1/admin')->group(function (): void {
    Route::get('homepage/main-categories', ListMainCategoriesController::class)->middleware('staff.permission:settings,read');
    Route::put('homepage/main-categories', UpdateMainCategoryController::class)->middleware('staff.permission:settings,write');
    Route::delete('homepage/main-categories/{position}', DeleteMainCategoryController::class)->middleware('staff.permission:settings,write')->where('position', '[1-6]');
    Route::get('homepage/bestsellers', ListBestsellersController::class)->middleware('staff.permission:settings,read');
    Route::put('homepage/bestsellers', UpdateBestsellerController::class)->middleware('staff.permission:settings,write');
    Route::delete('homepage/bestsellers/{position}', DeleteBestsellerController::class)->middleware('staff.permission:settings,write')->where('position', '[1-4]');

    Route::get('categories', ListCategoriesController::class)->middleware('staff.permission:categories,read');
    Route::post('categories', CreateCategoryController::class)->middleware('staff.permission:categories,write');
    Route::get('categories/{category}', GetCategoryController::class)->middleware('staff.permission:categories,read')->whereNumber('category');
    Route::put('categories/{category}', UpdateCategoryController::class)->middleware('staff.permission:categories,write')->whereNumber('category');
    Route::delete('categories/{category}', DeleteCategoryController::class)->middleware('staff.permission:categories,execute')->whereNumber('category');
    Route::post('categories/{category}/banner', UploadCategoryBannerController::class)->middleware('staff.permission:categories,write')->whereNumber('category');

    Route::get('collections', ListCollectionsController::class)->middleware('staff.permission:collections,read');
    Route::post('collections', CreateCollectionController::class)->middleware('staff.permission:collections,write');
    Route::get('collections/{collection}', GetCollectionController::class)->middleware('staff.permission:collections,read')->whereNumber('collection');
    Route::put('collections/{collection}', UpdateCollectionController::class)->middleware('staff.permission:collections,write')->whereNumber('collection');
    Route::delete('collections/{collection}', DeleteCollectionController::class)->middleware('staff.permission:collections,execute')->whereNumber('collection');
    Route::post('collections/{collection}/banner', UploadCollectionBannerController::class)->middleware('staff.permission:collections,write')->whereNumber('collection');

    Route::get('products', ListProductsController::class)->middleware('staff.permission:products,read');
    Route::post('products', CreateProductController::class)->middleware('staff.permission:products,write');
    Route::put('products/{product}/primary-image', SetProductPrimaryImageController::class)->middleware('staff.permission:products,write')->whereNumber('product');
    Route::get('products/{product}', GetProductController::class)->middleware('staff.permission:products,read')->whereNumber('product');
    Route::put('products/{product}', UpdateProductController::class)->middleware('staff.permission:products,write')->whereNumber('product');
    Route::patch('products/{product}/status', UpdateProductStatusController::class)->middleware('staff.permission:products,write')->whereNumber('product');
    Route::delete('products/{product}/images/{asset}', DeleteProductImageController::class)->middleware('staff.permission:products,execute')->whereNumber(['product', 'asset']);
    Route::delete('products/{product}', DeleteProductController::class)->middleware('staff.permission:products,execute')->whereNumber('product');

    Route::get('packs', ListPacksController::class)->middleware('staff.permission:products,read');
    Route::post('packs', CreatePackController::class)->middleware('staff.permission:products,write');
    Route::put('packs/{pack}/primary-image', SetPackPrimaryImageController::class)->middleware('staff.permission:products,write')->whereNumber('pack');
    Route::get('packs/{pack}', GetPackController::class)->middleware('staff.permission:products,read')->whereNumber('pack');
    Route::put('packs/{pack}', UpdatePackController::class)->middleware('staff.permission:products,write')->whereNumber('pack');
    Route::patch('packs/{pack}/status', UpdatePackStatusController::class)->middleware('staff.permission:products,write')->whereNumber('pack');
    Route::delete('packs/{pack}', DeletePackController::class)->middleware('staff.permission:products,execute')->whereNumber('pack');
    Route::delete('packs/{pack}/images/{asset}', DeletePackImageController::class)->middleware('staff.permission:products,execute')->whereNumber(['pack', 'asset']);
});
