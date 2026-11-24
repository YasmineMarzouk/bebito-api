<?php

namespace Modules\Catalog\Contracts\ValueObjects;

use Modules\Orders\Enums\OrderItemTypeEnum;

final readonly class ReservedProductVariant
{
    /** @param array<string, mixed> $attributes @param array<int, array{inventoryItemId: int, productVariantId: int, productId: int, quantity: int, productName: string, productSku: string, variantSku: string, variantAttributes: array<string, mixed>, unitPriceAmount: int}> $componentSnapshots */
    public function __construct(
        public int $variantId,
        public int $inventoryItemId,
        public string $productName,
        public string $sku,
        public array $attributes,
        public int $unitPriceAmount,
        public int $quantity,
        public OrderItemTypeEnum $catalogItemType = OrderItemTypeEnum::Product,
        public ?int $packVariantId = null,
        public ?array $packSnapshot = null,
        public array $componentSnapshots = [],
    ) {}
}
