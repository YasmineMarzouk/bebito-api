<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class ProductVariantInput
{
    /** @param array<string, string> $attributes */
    public function __construct(
        public array $attributes,
        public int $stockQuantity,
        public int $lowStockThreshold,
    ) {}

    /** @param array{attributes: array<string, string>, stock_quantity: int, low_stock_threshold?: int} $data */
    public static function from(array $data): self
    {
        return new self(
            attributes: $data['attributes'],
            stockQuantity: (int) $data['stock_quantity'],
            lowStockThreshold: (int) ($data['low_stock_threshold'] ?? 10),
        );
    }
}
