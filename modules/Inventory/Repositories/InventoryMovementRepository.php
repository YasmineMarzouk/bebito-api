<?php

namespace Modules\Inventory\Repositories;

use Modules\Inventory\Contracts\Repositories\InventoryMovementRepositoryInterface;
use Modules\Inventory\Enums\InventoryMovementReasonEnum;
use Modules\Inventory\Models\InventoryMovement;

final class InventoryMovementRepository implements InventoryMovementRepositoryInterface
{
    public function record(
        int $inventoryItemId,
        int $quantityChange,
        InventoryMovementReasonEnum $reason,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
    ): void {
        InventoryMovement::query()->create([
            'inventory_item_id' => $inventoryItemId,
            'quantity_change' => $quantityChange,
            'reason' => $reason->value,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => $userId,
            'note' => $note,
        ]);
    }
}
