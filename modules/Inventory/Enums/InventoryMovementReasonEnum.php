<?php

namespace Modules\Inventory\Enums;

enum InventoryMovementReasonEnum: int
{
    case InitialStock = 1;
    case ManualAdjustment = 2;
    case OrderPlaced = 3;
}
