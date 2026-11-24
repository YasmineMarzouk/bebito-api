<?php

namespace Modules\Catalog\Contracts\Repositories;

use Modules\Catalog\Contracts\ValueObjects\ReservedProductVariant;

interface CheckoutInventoryRepositoryInterface
{
    /**
     * @param  array<int, array{catalogItemType: string, variantId: int|null, packVariantId: int|null, quantity: int}>  $lines
     * @return array<int, ReservedProductVariant>
     */
    public function reserve(array $lines): array;

    /**
     * @param  array<int, array{catalogItemType: string, variantId: int|null, packVariantId: int|null, quantity: int}>  $lines
     * @return array<int, ReservedProductVariant>
     */
    public function quote(array $lines): array;

    /**
     * @param  array<int, ReservedProductVariant>  $items
     */
    public function recordOrderReservation(int $orderId, ?int $userId, array $items): void;

    /**
     * Reconcile an order's existing reservation with its replacement lines.
     *
     * @param  array<int, array{catalog_item_type?: string, product_variant_id: int|null, quantity: int, pack_components?: array<int, array{product_variant_id: int|null, quantity_consumed: int}>}>  $existingItems
     * @param  array<int, array{catalogItemType: string, variantId: int|null, packVariantId: int|null, quantity: int}>  $lines
     * @return array<int, ReservedProductVariant>
     */
    public function reconcileOrderReservation(int $orderId, ?int $userId, array $existingItems, array $lines): array;
}
