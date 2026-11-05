<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class UpdateHomepageProductSelectionInput
{
    /** @param array<int, int> $productIds */
    public function __construct(public array $productIds) {}

    /** @param array{product_ids: array<int, int>} $data */
    public static function from(array $data): self
    {
        return new self(array_map('intval', $data['product_ids']));
    }
}
