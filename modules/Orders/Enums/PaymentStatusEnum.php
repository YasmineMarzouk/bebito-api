<?php

namespace Modules\Orders\Enums;

enum PaymentStatusEnum: int
{
    case Pending = 1;
    case Cancelled = 2;
}
