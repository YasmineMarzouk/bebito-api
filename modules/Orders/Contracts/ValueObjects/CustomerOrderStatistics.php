<?php

namespace Modules\Orders\Contracts\ValueObjects;

final readonly class CustomerOrderStatistics
{
    public function __construct(
        public int $orderCount,
        public int $deliveredOrderCount,
        public int $notPickedUpOrderCount,
        public int $totalSpentAmount,
    ) {}
}
