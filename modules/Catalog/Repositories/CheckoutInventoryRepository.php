<?php

namespace Modules\Catalog\Repositories;

use Modules\Catalog\Contracts\Repositories\CheckoutInventoryRepositoryInterface;
use Modules\Catalog\Contracts\ValueObjects\ReservedProductVariant;
use Modules\Catalog\Exceptions\ProductVariantUnavailableException;
use Modules\Catalog\Models\ProductVariant;

final class CheckoutInventoryRepository implements CheckoutInventoryRepositoryInterface
{
    public function reserve(array $lines): array
    {
        $reservedVariants = [];

        foreach ($lines as $line) {
            $variant = ProductVariant::query()
                ->with(['product.translations', 'inventoryItem'])
                ->lockForUpdate()
                ->find($line['variantId']);

            if ($variant === null
                || $variant->product->status !== 'published'
                || $variant->inventoryItem === null
                || $variant->inventoryItem->quantity < $line['quantity']) {
                throw new ProductVariantUnavailableException;
            }

            $translation = $variant->product->translations->firstWhere('locale_code', 'fr')
                ?? $variant->product->translations->first();

            $reservedVariants[] = new ReservedProductVariant(
                $variant->id,
                $translation?->name ?? $variant->sku,
                $variant->sku,
                $variant->product->price_amount,
                $line['quantity'],
            );
            $variant->inventoryItem->decrement('quantity', $line['quantity']);
            $variant->product->decrement('stock_quantity', $line['quantity']);
        }

        return $reservedVariants;
    }
}
