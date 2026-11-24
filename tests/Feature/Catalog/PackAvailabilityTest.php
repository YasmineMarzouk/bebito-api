<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\Enums\InventoryStatusEnum;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Models\InventoryItem;
use Modules\Catalog\Models\Pack;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Tests\TestCase;

class PackAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_derives_stock_from_the_lowest_component_capacity_without_persisting_pack_stock(): void
    {
        $firstProduct = Product::query()->create([
            'status' => ProductStatusEnum::Published,
            'sku' => 'FIRST',
            'price_amount' => 10000,
            'stock_quantity' => 8,
        ]);
        $secondProduct = Product::query()->create([
            'status' => ProductStatusEnum::Published,
            'sku' => 'SECOND',
            'price_amount' => 5000,
            'stock_quantity' => 9,
        ]);
        $firstVariant = ProductVariant::query()->create(['product_id' => $firstProduct->id, 'sku' => 'FIRST-1', 'is_active' => true]);
        $secondVariant = ProductVariant::query()->create(['product_id' => $secondProduct->id, 'sku' => 'SECOND-1', 'is_active' => true]);
        InventoryItem::query()->create(['product_variant_id' => $firstVariant->id, 'quantity' => 8]);
        InventoryItem::query()->create(['product_variant_id' => $secondVariant->id, 'quantity' => 9]);

        $pack = Pack::query()->create([
            'status' => ProductStatusEnum::Published,
            'sku' => 'BPK-000001',
            'price_amount' => 20000,
            'low_stock_threshold' => 5,
        ]);
        $firstComponent = $pack->components()->create(['product_id' => $firstProduct->id, 'quantity' => 2]);
        $secondComponent = $pack->components()->create(['product_id' => $secondProduct->id, 'quantity' => 3]);
        $packVariant = $pack->allVariants()->create(['sku' => 'BPK-000001-01', 'is_default' => true, 'is_active' => true]);
        $packVariant->componentVariantMappings()->create(['pack_component_id' => $firstComponent->id, 'product_variant_id' => $firstVariant->id]);
        $packVariant->componentVariantMappings()->create(['pack_component_id' => $secondComponent->id, 'product_variant_id' => $secondVariant->id]);

        $loadedPack = Pack::query()->with([
            'components.product',
            'variants.componentVariantMappings.packComponent',
            'variants.componentVariantMappings.productVariant.inventoryItem',
            'variants.componentVariantMappings.productVariant.product',
        ])->findOrFail($pack->id);
        $availability = app(PackAvailabilityServiceInterface::class)->for($loadedPack);

        $this->assertSame(3, $availability['stockQuantity']);
        $this->assertSame(InventoryStatusEnum::LastChance->value, $availability['inventoryStatus']);
        $this->assertSame(3, $availability['variants'][0]['stockQuantity']);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('packs', 'stock_quantity'));
    }
}
