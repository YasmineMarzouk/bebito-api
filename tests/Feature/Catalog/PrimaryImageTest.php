<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Core\Contracts\Repositories\AssetsRepositoryInterface;
use Tests\TestCase;

class PrimaryImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_asset_resource_always_has_exactly_one_primary_image(): void
    {
        $product = $this->product();
        $assets = $this->app->make(AssetsRepositoryInterface::class);

        $first = $assets->create($this->attributes($product->id, 0, false));
        $second = $assets->create($this->attributes($product->id, 1, false));

        $this->assertTrue((bool) $first->fresh()->primary_image);
        $this->assertFalse((bool) $second->fresh()->primary_image);

        $third = $assets->create($this->attributes($product->id, 2, true));

        $this->assertFalse((bool) $first->fresh()->primary_image);
        $this->assertFalse((bool) $second->fresh()->primary_image);
        $this->assertTrue((bool) $third->fresh()->primary_image);
        $this->assertSame(1, $product->assets()->where('primary_image', true)->count());

        $assets->delete($third);

        $this->assertTrue((bool) $first->fresh()->primary_image);
        $this->assertFalse((bool) $second->fresh()->primary_image);
        $this->assertSame(1, $product->assets()->where('primary_image', true)->count());
    }

    public function test_product_output_identifies_the_primary_image_by_asset_id(): void
    {
        $product = $this->product();
        $assets = $this->app->make(AssetsRepositoryInterface::class);
        $first = $assets->create($this->attributes($product->id, 0, false));
        $primary = $assets->create($this->attributes($product->id, 1, true));

        $this->getJson('/api/v1/shop/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.primaryImageId', $primary->id)
            ->assertJsonPath('data.images.0.id', $primary->id)
            ->assertJsonMissingPath('data.images.0.isPrimary');

        $this->assertFalse((bool) $first->fresh()->primary_image);
    }

    private function product(): Product
    {
        return Product::query()->create([
            'status' => ProductStatusEnum::Published->value,
            'price_amount' => 1000,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
        ]);
    }

    /** @return array<string, int|string|bool> */
    private function attributes(int $productId, int $sortOrder, bool $primaryImage): array
    {
        return [
            'resource_type' => 'product',
            'resource_id' => $productId,
            'disk' => 'public',
            'path' => "products/{$sortOrder}.jpg",
            'original_name' => "{$sortOrder}.jpg",
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'sort_order' => $sortOrder,
            'primary_image' => $primaryImage,
        ];
    }
}
