<?php

namespace Modules\Orders\Enums;

enum OrderTypeEnum: int
{
    case DELIVERY = 1;
    case EXCHANGE = 2;
}
