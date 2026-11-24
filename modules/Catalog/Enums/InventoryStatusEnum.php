<?php

namespace Modules\Catalog\Enums;

enum InventoryStatusEnum: int
{
    case SoldOut = 1;
    case PartiallyAvailable = 2;
    case LastChance = 3;
    case InStock = 4;
}
