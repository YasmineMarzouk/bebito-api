<?php

namespace Modules\Orders\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;
use Modules\Promotions\Contracts\ValueObjects\CouponApplication;

final class OrderRepository implements OrderRepositoryInterface
{
    public function all(?int $customerId = null): Collection
    {
        return Order::query()->when($customerId !== null, fn ($query) => $query->where('user_id', $customerId))->orderByDesc('id')->get();
    }

    public function find(int $id): ?Order
    {
        return Order::query()->find($id);
    }

    public function applyCoupon(Order $order, CouponApplication $coupon, int $grossAmount): Order
    {
        $order->update([
            'coupon_id' => $coupon->couponId,
            'coupon_code' => $coupon->couponCode,
            'coupon_discount_amount' => $coupon->discountAmount,
            'total_amount' => max(0, $grossAmount - $coupon->discountAmount),
        ]);

        return $order->refresh();
    }
}
