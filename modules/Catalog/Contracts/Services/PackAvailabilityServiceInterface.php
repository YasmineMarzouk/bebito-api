<?php

namespace Modules\Catalog\Contracts\Services;

use Modules\Catalog\Models\Pack;

interface PackAvailabilityServiceInterface
{
    /** @return array{stockQuantity: int, inventoryStatus: string, variants: array<int, array{id: int, stockQuantity: int, available: bool}>} */
    public function for(Pack $pack): array;
}
