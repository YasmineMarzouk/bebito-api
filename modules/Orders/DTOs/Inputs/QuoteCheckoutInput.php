<?php

namespace Modules\Orders\DTOs\Inputs;

final readonly class QuoteCheckoutInput
{
    /** @param array<int, array{catalogItemType: string, variantId: int|null, packVariantId: int|null, quantity: int}> $items */
    public function __construct(
        public string $couponCode,
        public array $items,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            couponCode: strtoupper(trim((string) $data['coupon_code'])),
            items: array_map(fn (array $item): array => [
                'catalogItemType' => (string) ($item['catalog_item_type'] ?? 'product'),
                'variantId' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'packVariantId' => isset($item['pack_variant_id']) ? (int) $item['pack_variant_id'] : null,
                'quantity' => (int) $item['quantity'],
            ], $data['items']),
        );
    }
}
