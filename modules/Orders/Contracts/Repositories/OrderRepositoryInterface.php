<?php

namespace Modules\Orders\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Orders\Models\Order;
use Modules\Promotions\Contracts\ValueObjects\CouponApplication;

interface OrderRepositoryInterface
{
    /** @return Collection<int, Order> */
    public function all(?int $customerId = null): Collection;

    public function find(int $id): ?Order;

    public function applyCoupon(Order $order, CouponApplication $coupon, int $grossAmount): Order;
}
