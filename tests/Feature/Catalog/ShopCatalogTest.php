<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Collection;
use Modules\Catalog\Models\Product;
use Tests\TestCase;

class ShopCatalogTest extends TestCase
{
    use RefreshDatabase;

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
        $publishedProduct = $this->product('published');
        $draftProduct = $this->product('draft');

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
        $productInCollection = $this->product('published');
        $productOutsideCollection = $this->product('published');
        $productInCollection->collections()->attach($collection);

        $this->getJson('/api/v1/shop/products?collection_id='.$collection->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $productInCollection->id)
            ->assertJsonMissing(['id' => $productOutsideCollection->id]);
    }

    public function test_the_public_shop_product_detail_endpoint_returns_a_published_product_by_id(): void
    {
        $publishedProduct = $this->product('published');
        $draftProduct = $this->product('draft');

        $this->getJson('/api/v1/shop/products/'.$publishedProduct->id)
            ->assertOk()
            ->assertJsonPath('data.id', $publishedProduct->id);

        $this->getJson('/api/v1/shop/products/'.$draftProduct->id)
            ->assertNotFound();
    }

    public function test_the_public_shop_product_endpoint_can_list_only_discounted_products(): void
    {
        $discountedProduct = Product::query()->create([
            'status' => 'published',
            'price_amount' => 1000,
            'discount_percentage' => 15,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
        ]);
        $fullPriceProduct = $this->product('published');

        $this->getJson('/api/v1/shop/products?discounted=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $discountedProduct->id)
            ->assertJsonMissing(['id' => $fullPriceProduct->id]);
    }

    private function product(string $status): Product
    {
        return Product::query()->create([
            'status' => $status,
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
