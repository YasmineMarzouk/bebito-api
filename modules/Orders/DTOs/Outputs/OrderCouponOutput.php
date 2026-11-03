<?php

namespace Modules\Orders\DTOs\Outputs;

use Modules\Orders\Models\Order;

final readonly class OrderCouponOutput
{
    public function __construct(
        public int $orderId,
        public ?int $couponId,
        public ?string $couponCode,
        public int $couponDiscountAmount,
        public int $totalAmount,
    ) {}

    public static function fromModel(Order $order): self
    {
        return new self(
            orderId: $order->id,
            couponId: $order->coupon_id,
            couponCode: $order->coupon_code,
            couponDiscountAmount: $order->coupon_discount_amount,
            totalAmount: $order->total_amount,
        );
    }
}
