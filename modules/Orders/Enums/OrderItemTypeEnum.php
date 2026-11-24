<?php

namespace Modules\Orders\Enums;

enum OrderItemTypeEnum: string
{
    case Product = 'product';
    case Pack = 'pack';
}
