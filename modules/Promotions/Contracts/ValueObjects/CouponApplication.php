<?php

namespace Modules\Promotions\Contracts\ValueObjects;

final readonly class CouponApplication
{
    public function __construct(
        public int $couponId,
        public string $couponCode,
        public int $discountAmount,
    ) {}
}
