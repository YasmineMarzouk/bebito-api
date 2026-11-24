<?php

namespace Modules\Catalog\Services;

use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\Enums\InventoryStatusEnum;
use Modules\Catalog\Models\Pack;
use Modules\Catalog\Models\PackVariant;

final class PackAvailabilityService implements PackAvailabilityServiceInterface
{
    public function for(Pack $pack): array
    {
        $variants = $pack->variants->map(function (PackVariant $variant) use ($pack): array {
            $mappings = $variant->componentVariantMappings;

            if ($pack->components->isEmpty() || $mappings->count() !== $pack->components->count()) {
                return ['id' => $variant->id, 'stockQuantity' => 0, 'available' => false];
            }

            $available = $pack->components->map(function ($component) use ($mappings): int {
                $mapping = $mappings->firstWhere('pack_component_id', $component->id);
                $variant = $mapping?->productVariant;
                $inventory = $variant?->inventoryItem;

                if ($mapping === null
                    || $variant === null
                    || $variant->product_id !== $component->product_id
                    || ! $variant->is_active
                    || $variant->product?->status !== \Modules\Catalog\Enums\ProductStatusEnum::Published
                    || $inventory === null) {
                    return 0;
                }

                return intdiv(max(0, $inventory->quantity), $component->quantity);
            })->min();

            return ['id' => $variant->id, 'stockQuantity' => $available ?? 0, 'available' => ($available ?? 0) > 0];
        })->values()->all();

        $stockQuantity = array_sum(array_column($variants, 'stockQuantity'));
        $hasUnavailableVariants = collect($variants)->contains(fn (array $variant): bool => ! $variant['available']);
        $inventoryStatus = $stockQuantity === 0
            ? InventoryStatusEnum::SoldOut->value
            : ($hasUnavailableVariants
                ? InventoryStatusEnum::PartiallyAvailable->value
                : ($stockQuantity < $pack->low_stock_threshold ? InventoryStatusEnum::LastChance->value : InventoryStatusEnum::InStock->value));

        return compact('stockQuantity', 'inventoryStatus', 'variants');
    }
}
