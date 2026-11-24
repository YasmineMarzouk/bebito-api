<?php

namespace Modules\Orders\Events;

final readonly class OrderPlaced
{
    public function __construct(public int $orderId) {}
}
