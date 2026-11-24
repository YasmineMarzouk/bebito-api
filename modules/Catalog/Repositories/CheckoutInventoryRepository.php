<?php

namespace Modules\Catalog\Repositories;

use Modules\Catalog\Contracts\Repositories\CheckoutInventoryRepositoryInterface;
use Modules\Catalog\Contracts\ValueObjects\ReservedProductVariant;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Exceptions\ProductVariantUnavailableException;
use Modules\Catalog\Models\InventoryItem;
use Modules\Catalog\Models\Pack;
use Modules\Catalog\Models\PackVariant;
use Modules\Catalog\Models\ProductVariant;
use Modules\Inventory\Contracts\Repositories\InventoryMovementRepositoryInterface;
use Modules\Inventory\Enums\InventoryMovementReasonEnum;
use Modules\Orders\Contracts\ValueObjects\OrderReference;
use Modules\Orders\Enums\OrderItemTypeEnum;

final class CheckoutInventoryRepository implements CheckoutInventoryRepositoryInterface
{
    public function __construct(private readonly InventoryMovementRepositoryInterface $movements) {}

    public function reserve(array $lines): array
    {
        return $this->itemsFor($lines, true);
    }

    public function quote(array $lines): array
    {
        return $this->itemsFor($lines, false);
    }

    /**
     * @param array<int, array{catalogItemType: string, variantId: int|null, packVariantId: int|null, quantity: int}> $lines
     * @return array<int, ReservedProductVariant>
     */
    private function itemsFor(array $lines, bool $reserve): array
    {
        $directVariantIds = collect($lines)->where('catalogItemType', OrderItemTypeEnum::Product->value)->pluck('variantId')->filter()->unique()->sort()->values()->all();
        $packVariantIds = collect($lines)->where('catalogItemType', OrderItemTypeEnum::Pack->value)->pluck('packVariantId')->filter()->unique()->sort()->values()->all();
        $packVariants = PackVariant::query()
            ->with(['pack.translations', 'pack.components.product', 'componentVariantMappings.packComponent', 'componentVariantMappings.productVariant.product.translations'])
            ->whereIn('id', $packVariantIds)
            ->orderBy('id')
            ->when($reserve, fn ($query) => $query->lockForUpdate())
            ->get()
            ->keyBy('id');
        $lockedPacks = Pack::query()
            ->with(['translations', 'components.product'])
            ->whereIn('id', $packVariants->pluck('pack_id')->unique()->sort()->values()->all())
            ->orderBy('id')
            ->when($reserve, fn ($query) => $query->lockForUpdate())
            ->get()
            ->keyBy('id');
        $packVariants->each(function (PackVariant $variant) use ($lockedPacks): void {
            $pack = $lockedPacks->get($variant->pack_id);
            if ($pack !== null) {
                $variant->setRelation('pack', $pack);
            }
        });
        $componentVariantIds = $packVariants->flatMap(fn (PackVariant $variant) => $variant->componentVariantMappings->pluck('product_variant_id'))->unique()->sort()->values()->all();
        $variantIds = array_values(array_unique([...$directVariantIds, ...$componentVariantIds]));
        sort($variantIds);
        $variants = ProductVariant::query()
            ->with(['product.translations'])
            ->whereIn('id', $variantIds)
            ->orderBy('id')
            ->when($reserve, fn ($query) => $query->lockForUpdate())
            ->get()
            ->keyBy('id');
        $inventoryItems = InventoryItem::query()
            ->whereIn('product_variant_id', $variantIds)
            ->orderBy('product_variant_id')
            ->when($reserve, fn ($query) => $query->lockForUpdate())
            ->get()
            ->keyBy('product_variant_id');

        $inventoryDemand = [];
        $reservedItems = [];
        foreach ($lines as $line) {
            if ($line['catalogItemType'] === OrderItemTypeEnum::Product->value) {
                $variant = $line['variantId'] === null ? null : $variants->get($line['variantId']);
                $inventory = $variant === null ? null : $inventoryItems->get($variant->id);
                if ($variant === null || $inventory === null || ! $variant->is_active || $variant->product?->status !== ProductStatusEnum::Published) {
                    throw new ProductVariantUnavailableException;
                }
                $inventoryDemand[$variant->id] = ($inventoryDemand[$variant->id] ?? 0) + $line['quantity'];
                $reservedItems[] = $this->reservedProduct($variant, $inventory, $line['quantity']);

                continue;
            }

            $packVariant = $line['packVariantId'] === null ? null : $packVariants->get($line['packVariantId']);
            if ($packVariant === null || ! $packVariant->is_active || $packVariant->pack?->status !== ProductStatusEnum::Published || $packVariant->componentVariantMappings->count() !== $packVariant->pack->components->count()) {
                throw new ProductVariantUnavailableException;
            }

            $componentSnapshots = [];
            foreach ($packVariant->componentVariantMappings as $mapping) {
                $component = $mapping->packComponent;
                $variant = $variants->get($mapping->product_variant_id);
                $inventory = $variant === null ? null : $inventoryItems->get($mapping->product_variant_id);
                if ($component === null || $variant === null || $inventory === null || $variant->product_id !== $component->product_id || ! $variant->is_active || $variant->product?->status !== ProductStatusEnum::Published) {
                    throw new ProductVariantUnavailableException;
                }
                $consumedQuantity = $component->quantity * $line['quantity'];
                $inventoryDemand[$variant->id] = ($inventoryDemand[$variant->id] ?? 0) + $consumedQuantity;
                $componentSnapshots[] = $this->componentSnapshot($variant, $inventory, $component->quantity, $consumedQuantity);
            }

            $translation = $packVariant->pack->translations->firstWhere('locale_code', 'fr') ?? $packVariant->pack->translations->first();
            $reservedItems[] = new ReservedProductVariant(
                variantId: 0,
                inventoryItemId: 0,
                productName: $translation?->name ?? $packVariant->sku,
                sku: $packVariant->sku,
                attributes: $packVariant->attributes ?? [],
                unitPriceAmount: $packVariant->pack->price_amount,
                quantity: $line['quantity'],
                catalogItemType: OrderItemTypeEnum::Pack,
                packVariantId: $packVariant->id,
                packSnapshot: ['pack_id' => $packVariant->pack->id, 'pack_sku' => $packVariant->pack->sku, 'pack_variant_id' => $packVariant->id, 'pack_variant_sku' => $packVariant->sku, 'pack_variant_attributes' => $packVariant->attributes ?? []],
                componentSnapshots: $componentSnapshots,
            );
        }

        foreach ($inventoryDemand as $variantId => $quantity) {
            $inventory = $inventoryItems->get($variantId);
            if ($inventory === null || $inventory->quantity < $quantity) {
                throw new ProductVariantUnavailableException;
            }
        }
        if ($reserve) {
            $productDemand = [];
            foreach ($inventoryDemand as $variantId => $quantity) {
                $inventoryItems->get($variantId)->decrement('quantity', $quantity);
                $productId = $variants->get($variantId)->product_id;
                $productDemand[$productId] = ($productDemand[$productId] ?? 0) + $quantity;
            }
            foreach ($productDemand as $productId => $quantity) {
                $variants->first(fn (ProductVariant $variant): bool => $variant->product_id === $productId)->product->decrement('stock_quantity', $quantity);
            }
        }

        return $reservedItems;
    }

    private function reservedProduct(ProductVariant $variant, InventoryItem $inventory, int $quantity): ReservedProductVariant
    {
        $translation = $variant->product->translations->firstWhere('locale_code', 'fr') ?? $variant->product->translations->first();

        return new ReservedProductVariant($variant->id, $inventory->id, $translation?->name ?? $variant->sku, $variant->sku, $variant->attributes ?? [], $variant->product->price_amount, $quantity, componentSnapshots: [$this->componentSnapshot($variant, $inventory, 1, $quantity)]);
    }

    /** @return array{inventoryItemId: int, productVariantId: int, productId: int, quantity: int, quantityPerPack: int, productName: string, productSku: string, variantSku: string, variantAttributes: array<string, mixed>, unitPriceAmount: int} */
    private function componentSnapshot(ProductVariant $variant, InventoryItem $inventory, int $quantityPerPack, int $quantity): array
    {
        $translation = $variant->product->translations->firstWhere('locale_code', 'fr') ?? $variant->product->translations->first();

        return ['inventoryItemId' => $inventory->id, 'productVariantId' => $variant->id, 'productId' => $variant->product_id, 'quantity' => $quantity, 'quantityPerPack' => $quantityPerPack, 'productName' => $translation?->name ?? $variant->sku, 'productSku' => $variant->product->sku ?? '', 'variantSku' => $variant->sku, 'variantAttributes' => $variant->attributes ?? [], 'unitPriceAmount' => $variant->product->price_amount];
    }

    public function recordOrderReservation(int $orderId, ?int $userId, array $items): void
    {
        foreach ($items as $item) {
            foreach ($item->componentSnapshots as $component) {
                $this->movements->record($component['inventoryItemId'], -$component['quantity'], InventoryMovementReasonEnum::OrderPlaced, $userId, OrderReference::Type, $orderId);
            }
        }
    }

    public function reconcileOrderReservation(int $orderId, ?int $userId, array $existingItems, array $lines): array
    {
        $hasPackItems = collect($existingItems)->contains(fn (array $item): bool => ($item['catalog_item_type'] ?? OrderItemTypeEnum::Product->value) !== OrderItemTypeEnum::Product->value);
        $requestsPackItems = collect($lines)->contains(fn (array $line): bool => ($line['catalogItemType'] ?? OrderItemTypeEnum::Product->value) !== OrderItemTypeEnum::Product->value);
        if ($hasPackItems || $requestsPackItems) {
            return $this->reconcileSnapshotOrder($orderId, $userId, $existingItems, $lines);
        }

        $existingQuantities = [];
        foreach ($existingItems as $item) {
            if (($item['catalog_item_type'] ?? OrderItemTypeEnum::Product->value) === OrderItemTypeEnum::Pack->value) {
                foreach ($item['pack_components'] ?? [] as $component) {
                    $variantId = $component['product_variant_id'] ?? null;
                    if ($variantId === null) {
                        throw new ProductVariantUnavailableException;
                    }
                    $existingQuantities[$variantId] = ($existingQuantities[$variantId] ?? 0) + $component['quantity_consumed'];
                }

                continue;
            }
            if ($item['product_variant_id'] === null) {
                throw new ProductVariantUnavailableException;
            }
            $existingQuantities[$item['product_variant_id']] = ($existingQuantities[$item['product_variant_id']] ?? 0) + $item['quantity'];
        }
        $requestedQuantities = [];
        foreach ($lines as $line) {
            $variantId = $line['variantId'] ?? null;
            if ($variantId === null) {
                throw new ProductVariantUnavailableException;
            }
            $requestedQuantities[$variantId] = $line['quantity'];
        }
        $variantIds = array_keys($existingQuantities + $requestedQuantities);
        sort($variantIds);
        $variants = ProductVariant::query()->with(['product.translations'])->whereIn('id', $variantIds)->lockForUpdate()->get()->keyBy('id');
        $inventoryItems = InventoryItem::query()->whereIn('product_variant_id', $variantIds)->orderBy('product_variant_id')->lockForUpdate()->get()->keyBy('product_variant_id');
        foreach ($variantIds as $variantId) {
            $variant = $variants->get($variantId);
            $inventory = $inventoryItems->get($variantId);
            $requestedQuantity = $requestedQuantities[$variantId] ?? 0;
            $difference = $requestedQuantity - ($existingQuantities[$variantId] ?? 0);
            if ($variant === null || $inventory === null || $variant->product === null || ($requestedQuantity > 0 && (! $variant->is_active || $variant->product->status !== ProductStatusEnum::Published)) || ($difference > 0 && $inventory->quantity < $difference)) {
                throw new ProductVariantUnavailableException;
            }
        }
        $reservedItems = [];
        foreach ($variantIds as $variantId) {
            $variant = $variants->get($variantId);
            $inventory = $inventoryItems->get($variantId);
            $requestedQuantity = $requestedQuantities[$variantId] ?? 0;
            $difference = $requestedQuantity - ($existingQuantities[$variantId] ?? 0);
            if ($difference !== 0) {
                $inventory->increment('quantity', -$difference);
                $variant->product->increment('stock_quantity', -$difference);
                $this->movements->record($inventory->id, -$difference, InventoryMovementReasonEnum::ManualAdjustment, $userId, OrderReference::Type, $orderId, 'Admin order edit');
            }
            if ($requestedQuantity > 0) {
                $reservedItems[] = $this->reservedProduct($variant, $inventory, $requestedQuantity);
            }
        }

        return $reservedItems;
    }

    /**
     * @param array<int, array{catalog_item_type?: string, product_variant_id: int|null, quantity: int, pack_components?: array<int, array{product_variant_id: int|null, quantity_consumed: int}>}> $existingItems
     * @param array<int, array{catalogItemType: string, variantId: int|null, packVariantId: int|null, quantity: int}> $lines
     * @return array<int, ReservedProductVariant>
     */
    private function reconcileSnapshotOrder(int $orderId, ?int $userId, array $existingItems, array $lines): array
    {
        $restoreQuantities = [];
        foreach ($existingItems as $item) {
            if (($item['catalog_item_type'] ?? OrderItemTypeEnum::Product->value) === OrderItemTypeEnum::Pack->value) {
                foreach ($item['pack_components'] ?? [] as $component) {
                    if ($component['product_variant_id'] === null) {
                        throw new ProductVariantUnavailableException;
                    }
                    $restoreQuantities[$component['product_variant_id']] = ($restoreQuantities[$component['product_variant_id']] ?? 0) + $component['quantity_consumed'];
                }

                continue;
            }

            if ($item['product_variant_id'] === null) {
                throw new ProductVariantUnavailableException;
            }
            $restoreQuantities[$item['product_variant_id']] = ($restoreQuantities[$item['product_variant_id']] ?? 0) + $item['quantity'];
        }

        $variantIds = array_keys($restoreQuantities);
        sort($variantIds);
        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $variantIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $inventoryItems = InventoryItem::query()
            ->whereIn('product_variant_id', $variantIds)
            ->orderBy('product_variant_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_variant_id');
        $productRestorations = [];
        foreach ($restoreQuantities as $variantId => $quantity) {
            $variant = $variants->get($variantId);
            $inventory = $inventoryItems->get($variantId);
            if ($variant === null || $inventory === null || $variant->product === null) {
                throw new ProductVariantUnavailableException;
            }
            $inventory->increment('quantity', $quantity);
            $productRestorations[$variant->product_id] = ($productRestorations[$variant->product_id] ?? 0) + $quantity;
            $this->movements->record($inventory->id, $quantity, InventoryMovementReasonEnum::ManualAdjustment, $userId, OrderReference::Type, $orderId, 'Admin order edit restored previous reservation.');
        }
        foreach ($productRestorations as $productId => $quantity) {
            $variants->first(fn (ProductVariant $variant): bool => $variant->product_id === $productId)->product->increment('stock_quantity', $quantity);
        }

        $reservedItems = $this->reserve($lines);
        $this->recordOrderReservation($orderId, $userId, $reservedItems);

        return $reservedItems;
    }
}
