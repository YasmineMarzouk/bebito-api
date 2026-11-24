<?php

namespace Modules\Inventory\Contracts\Repositories;

use Modules\Inventory\Enums\InventoryMovementReasonEnum;

interface InventoryMovementRepositoryInterface
{
    public function record(
        int $inventoryItemId,
        int $quantityChange,
        InventoryMovementReasonEnum $reason,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
    ): void;
}
