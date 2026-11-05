<?php

namespace Modules\Catalog\Contracts\ValueObjects;

final readonly class ReservedProductVariant
{
    public function __construct(
        public int $variantId,
        public string $productName,
        public string $sku,
        public int $unitPriceAmount,
        public int $quantity,
    ) {}
}
