<?php

namespace Modules\Inventory\Models;

use Modules\Core\Models\BaseModel;
use Modules\Inventory\Enums\InventoryMovementReasonEnum;

class InventoryMovement extends BaseModel
{
    protected $casts = ['reason' => InventoryMovementReasonEnum::class];
}
