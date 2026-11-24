<?php

namespace Tests\Concerns;

use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Models\InventoryItem;
use Modules\Catalog\Models\Pack;
use Modules\Catalog\Models\PackVariant;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

trait CreatesPackFixtures
{
    protected function createSellableVariant(string $sku, int $quantity, int $priceAmount): ProductVariant
    {
        $product = Product::query()->create([
            'status' => ProductStatusEnum::Published,
            'sku' => $sku,
            'price_amount' => $priceAmount,
            'stock_quantity' => $quantity,
            'low_stock_threshold' => 1,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => $sku.'-01',
            'attributes' => ['size' => '3-6m'],
            'is_default' => true,
            'is_active' => true,
        ]);
        InventoryItem::query()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'low_stock_threshold' => 1,
        ]);

        return $variant;
    }

    /**
     * @param  array<int, array{variant: ProductVariant, quantity: int}>  $components
     * @return array{pack: Pack, variant: PackVariant}
     */
    protected function createSellablePack(string $sku, int $priceAmount, array $components): array
    {
        $pack = Pack::query()->create([
            'status' => ProductStatusEnum::Published,
            'sku' => $sku,
            'price_amount' => $priceAmount,
            'low_stock_threshold' => 1,
        ]);
        $pack->translations()->create([
            'locale_code' => 'fr',
            'name' => 'Pack '.$sku,
            'slug' => strtolower($sku),
        ]);

        $storedComponents = [];
        foreach ($components as $component) {
            $storedComponents[] = [
                'component' => $pack->components()->create([
                    'product_id' => $component['variant']->product_id,
                    'quantity' => $component['quantity'],
                ]),
                'variant' => $component['variant'],
            ];
        }

        $packVariant = $pack->allVariants()->create([
            'sku' => $sku.'-01',
            'attributes' => ['size' => '3-6m'],
            'is_default' => true,
            'is_active' => true,
        ]);
        foreach ($storedComponents as $storedComponent) {
            $packVariant->componentVariantMappings()->create([
                'pack_component_id' => $storedComponent['component']->id,
                'product_variant_id' => $storedComponent['variant']->id,
            ]);
        }

        return ['pack' => $pack, 'variant' => $packVariant];
    }
}
