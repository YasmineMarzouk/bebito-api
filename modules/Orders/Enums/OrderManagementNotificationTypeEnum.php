<?php

namespace Modules\Orders\Enums;

enum OrderManagementNotificationTypeEnum: int
{
    case Confirmed = 1;
    case Updated = 2;
    case Cancelled = 3;
}
