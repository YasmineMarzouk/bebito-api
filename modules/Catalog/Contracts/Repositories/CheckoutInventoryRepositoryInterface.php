<?php

namespace Modules\Catalog\Contracts\Repositories;

use Modules\Catalog\Contracts\ValueObjects\ReservedProductVariant;

interface CheckoutInventoryRepositoryInterface
{
    /**
     * @param  array<int, array{variantId: int, quantity: int}>  $lines
     * @return array<int, ReservedProductVariant>
     */
    public function reserve(array $lines): array;
}
