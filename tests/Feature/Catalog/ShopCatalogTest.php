<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Collection;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Enums\ProductStatusEnum;
use Tests\Concerns\CreatesPackFixtures;
use Tests\TestCase;

class ShopCatalogTest extends TestCase
{
    use RefreshDatabase;
    use CreatesPackFixtures;

    public function test_the_shop_catalog_endpoint_returns_categories_and_a_paginated_mixed_catalog(): void
    {
        $category = Category::query()->create(['is_active' => true]);
        $productVariant = $this->createSellableVariant('SHOP-PRODUCT', 5, 10000);
        $productVariant->product->categories()->attach($category);
        $productVariant->product->translations()->create(['locale_code' => 'fr', 'name' => 'Shop product', 'slug' => 'shop-product']);
        $secondVariant = $this->createSellableVariant('SHOP-PACK-COMPONENT', 5, 5000);
        $pack = $this->createSellablePack('SHOP-PACK', 12000, [
            ['variant' => $productVariant, 'quantity' => 1],
            ['variant' => $secondVariant, 'quantity' => 1],
        ])['pack'];
        $pack->categories()->attach($category);

        $this->getJson('/api/v1/shop?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.categories.0.id', $category->id)
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.perPage', 1)
            ->assertJsonPath('data.lastPage', 3)
            ->assertJsonCount(1, 'data.items');

        $productVariant->product->update(['discount_percentage' => 10]);
        $pack->update(['compare_at_price_amount' => 15000]);

        $this->getJson('/api/v1/shop?discounted=1')
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_the_public_shop_category_endpoint_lists_only_active_categories(): void
    {
        $activeCategory = Category::query()->create(['is_active' => true]);
        $inactiveCategory = Category::query()->create(['is_active' => false]);

        $this->getJson('/api/v1/shop/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeCategory->id)
            ->assertJsonMissing(['id' => $inactiveCategory->id]);
    }

    public function test_the_public_shop_product_endpoint_lists_only_published_products(): void
    {
        $publishedProduct = $this->product(ProductStatusEnum::Published);
        $draftProduct = $this->product(ProductStatusEnum::Draft);

        $this->getJson('/api/v1/shop/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publishedProduct->id)
            ->assertJsonMissing(['id' => $draftProduct->id]);
    }

    public function test_the_public_shop_collection_endpoint_lists_active_collections_newest_first(): void
    {
        $olderCollection = $this->collection(1);
        $newerCollection = $this->collection(2);
        $this->collection(3, false);

        $this->getJson('/api/v1/shop/collections')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerCollection->id)
            ->assertJsonPath('data.1.id', $olderCollection->id);
    }

    public function test_the_public_shop_product_endpoint_can_filter_by_collection(): void
    {
        $collection = $this->collection(1);
        $productInCollection = $this->product(ProductStatusEnum::Published);
        $productOutsideCollection = $this->product(ProductStatusEnum::Published);
        $productInCollection->collections()->attach($collection);

        $this->getJson('/api/v1/shop/products?collection_id='.$collection->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $productInCollection->id)
            ->assertJsonMissing(['id' => $productOutsideCollection->id]);
    }

    public function test_the_public_shop_product_detail_endpoint_returns_a_published_product_by_id(): void
    {
        $publishedProduct = $this->product(ProductStatusEnum::Published);
        $draftProduct = $this->product(ProductStatusEnum::Draft);

        $this->getJson('/api/v1/shop/products/'.$publishedProduct->id)
            ->assertOk()
            ->assertJsonPath('data.id', $publishedProduct->id);

        $this->getJson('/api/v1/shop/products/'.$draftProduct->id)
            ->assertNotFound();
    }

    public function test_the_public_shop_product_endpoint_can_list_only_discounted_products(): void
    {
        $discountedProduct = Product::query()->create([
            'status' => ProductStatusEnum::Published->value,
            'price_amount' => 1000,
            'discount_percentage' => 15,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
        ]);
        $fullPriceProduct = $this->product(ProductStatusEnum::Published);

        $this->getJson('/api/v1/shop/products?discounted=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $discountedProduct->id)
            ->assertJsonMissing(['id' => $fullPriceProduct->id]);
    }

    public function test_public_catalog_counts_and_product_categories_exclude_unpublished_and_inactive_data(): void
    {
        $activeCategory = Category::query()->create(['is_active' => true]);
        $inactiveCategory = Category::query()->create(['is_active' => false]);
        $publishedProduct = $this->product(ProductStatusEnum::Published);
        $draftProduct = $this->product(ProductStatusEnum::Draft);
        $publishedProduct->categories()->attach([$activeCategory->id, $inactiveCategory->id]);
        $draftProduct->categories()->attach($activeCategory);

        $this->getJson('/api/v1/shop/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeCategory->id)
            ->assertJsonPath('data.0.productCount', 1);

        $this->getJson('/api/v1/shop/products')
            ->assertOk()
            ->assertJsonPath('data.0.id', $publishedProduct->id)
            ->assertJsonCount(1, 'data.0.categories')
            ->assertJsonPath('data.0.categories.0.id', $activeCategory->id);
    }

    private function product(ProductStatusEnum $status): Product
    {
        return Product::query()->create([
            'status' => $status->value,
            'price_amount' => 1000,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
        ]);
    }

    private function collection(int $number, bool $isActive = true): Collection
    {
        return Collection::query()->create([
            'name' => "Collection {$number}",
            'slug' => "collection-{$number}",
            'is_active' => $isActive,
        ]);
    }
}
