<?php

namespace Modules\Orders\DTOs\Outputs;

use Modules\Promotions\Contracts\ValueObjects\CouponApplication;

final readonly class CheckoutQuoteOutput
{
    public function __construct(
        public int $subtotalAmount,
        public int $shippingAmount,
        public string $couponCode,
        public int $couponDiscountAmount,
        public int $totalAmount,
        public string $currencyCode,
    ) {}

    public static function fromCoupon(CouponApplication $coupon, int $subtotalAmount, int $shippingAmount, string $currencyCode): self
    {
        return new self(
            subtotalAmount: $subtotalAmount,
            shippingAmount: $shippingAmount,
            couponCode: $coupon->couponCode,
            couponDiscountAmount: $coupon->discountAmount,
            totalAmount: max(0, $subtotalAmount + $shippingAmount - $coupon->discountAmount),
            currencyCode: $currencyCode,
        );
    }
}
