<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\V1\Categories\CreateCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\DeleteCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\GetCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\ListCategoriesController;
use Modules\Catalog\Http\Controllers\V1\Categories\UpdateCategoryController;
use Modules\Catalog\Http\Controllers\V1\Categories\UploadCategoryBannerController;
use Modules\Catalog\Http\Controllers\V1\Collections\CreateCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\DeleteCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\GetCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\ListCollectionsController;
use Modules\Catalog\Http\Controllers\V1\Collections\UpdateCollectionController;
use Modules\Catalog\Http\Controllers\V1\Collections\UploadCollectionBannerController;
use Modules\Catalog\Http\Controllers\V1\Products\CreateProductController;
use Modules\Catalog\Http\Controllers\V1\Products\DeleteProductController;
use Modules\Catalog\Http\Controllers\V1\Products\DeleteProductImageController;
use Modules\Catalog\Http\Controllers\V1\Products\GetProductController;
use Modules\Catalog\Http\Controllers\V1\Products\ListProductsController;
use Modules\Catalog\Http\Controllers\V1\Products\UpdateProductController;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::get('categories', ListCategoriesController::class);
    Route::post('categories', CreateCategoryController::class);
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
    Route::get('products/{product}', GetProductController::class)->whereNumber('product');
    Route::put('products/{product}', UpdateProductController::class)->whereNumber('product');
    Route::delete('products/{product}/images/{asset}', DeleteProductImageController::class)->whereNumber(['product', 'asset']);
    Route::delete('products/{product}', DeleteProductController::class)->whereNumber('product');
});
