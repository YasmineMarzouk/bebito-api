<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class PackVariantMappingInput
{
    public function __construct(
        public int $productId,
        public int $productVariantId,
    ) {}

    /** @param array{product_id: int|string, product_variant_id: int|string} $data */
    public static function from(array $data): self
    {
        return new self((int) $data['product_id'], (int) $data['product_variant_id']);
    }
}
