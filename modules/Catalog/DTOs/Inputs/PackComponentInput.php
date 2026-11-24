<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class PackComponentInput
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}

    /** @param array{product_id: int|string, quantity: int|string} $data */
    public static function from(array $data): self
    {
        return new self((int) $data['product_id'], (int) $data['quantity']);
    }
}
