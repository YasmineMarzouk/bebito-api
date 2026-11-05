<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\V1\Categories\CreateCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\DeleteCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\GetCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\ListCategoriesController;
use Modules\Catalog\Http\Controllers\V1\Categories\ListHomepageCategoriesController;
use Modules\Catalog\Http\Controllers\V1\Categories\ListShopCategoriesController;
use Modules\Catalog\Http\Controllers\V1\Categories\UpdateCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\UpdateHomepageCategorySelectionController;
use Modules\Catalog\Http\Controllers\V1\Categories\UploadCategoryBannerController;
use Modules\Catalog\Http\Controllers\V1\Collections\CreateCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\DeleteCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\GetCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\ListCollectionsController;
use Modules\Catalog\Http\Controllers\V1\Collections\ListShopCollectionsController;
use Modules\Catalog\Http\Controllers\V1\Collections\UpdateCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\UploadCollectionBannerController;
use Modules\Catalog\Http\Controllers\V1\Products\CreateProductController;
use Modules\Catalog\Http\Controllers\V1\Products\DeleteProductController;
use Modules\Catalog\Http\Controllers\V1\Products\DeleteProductImageController;
use Modules\Catalog\Http\Controllers\V1\Products\GetProductController;
use Modules\Catalog\Http\Controllers\V1\Products\GetShopProductController;
use Modules\Catalog\Http\Controllers\V1\Products\ListHomepageProductsController;
use Modules\Catalog\Http\Controllers\V1\Products\ListProductsController;
use Modules\Catalog\Http\Controllers\V1\Products\ListShopProductsController;
use Modules\Catalog\Http\Controllers\V1\Products\UpdateHomepageProductSelectionController;
use Modules\Catalog\Http\Controllers\V1\Products\UpdateProductController;

Route::prefix('v1')->group(function (): void {
    Route::get('homepage/categories', ListHomepageCategoriesController::class);
    Route::get('homepage/products', ListHomepageProductsController::class);
    Route::get('shop/categories', ListShopCategoriesController::class);
    Route::get('shop/products', ListShopProductsController::class);
    Route::get('shop/products/{identifier}', GetShopProductController::class);
    Route::get('shop/collections', ListShopCollectionsController::class);
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::get('categories', ListCategoriesController::class);
    Route::post('categories', CreateCategoryController::class);
    Route::put('categories/homepage-selection', UpdateHomepageCategorySelectionController::class);
    Route::get('categories/{category}', GetCategoryController::class)->whereNumber('category');
    Route::put('categories/{category}', UpdateCategoryController::class)->whereNumber('category');
    Route::delete('categories/{category}', DeleteCategoryController::class)->whereNumber('category');
    Route::post('categories/{category}/banner', UploadCategoryBannerController::class)->whereNumber('category');

    Route::get('collections', ListCollectionsController::class);
    Route::post('collections', CreateCollectionController::class);
    Route::get('collections/{collection}', GetCollectionController::class)->whereNumber('collection');
    Route::put('collections/{collection}', UpdateCollectionController::class)->whereNumber('collection');
    Route::delete('collections/{collection}', DeleteCollectionController::class)->whereNumber('collection');
    Route::post('collections/{collection}/banner', UploadCollectionBannerController::class)->whereNumber('collection');

    Route::get('products', ListProductsController::class);
    Route::post('products', CreateProductController::class);
    Route::put('products/homepage-selection', UpdateHomepageProductSelectionController::class);
    Route::get('products/{product}', GetProductController::class)->whereNumber('product');
    Route::put('products/{product}', UpdateProductController::class)->whereNumber('product');
    Route::delete('products/{product}/images/{asset}', DeleteProductImageController::class)->whereNumber(['product', 'asset']);
    Route::delete('products/{product}', DeleteProductController::class)->whereNumber('product');
});
