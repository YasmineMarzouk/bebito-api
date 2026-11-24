<?php

namespace Modules\Orders\Enums;

enum OrderStatusEnum: int
{
    case PENDING = 1;
    case PROCESSING = 2;
    case READY = 3;
    case AWAITING_DELIVERY = 4;
    case DELIVERED = 5;
    case RETURN_TO_ORIGIN = 6;
    case CANCELLED = 7;
}
