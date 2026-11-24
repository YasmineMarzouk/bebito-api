<?php

namespace Modules\Orders\Events;

use Modules\Orders\Enums\OrderManagementNotificationTypeEnum;

final readonly class OrderManagementNotification
{
    public function __construct(public int $orderId, public OrderManagementNotificationTypeEnum $type) {}
}
