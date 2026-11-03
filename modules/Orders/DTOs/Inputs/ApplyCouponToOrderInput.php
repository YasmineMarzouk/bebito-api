<?php

namespace Modules\Orders\DTOs\Inputs;

final readonly class ApplyCouponToOrderInput
{
    public function __construct(public int $couponId) {}

    /** @param array{coupon_id: int} $data */
    public static function from(array $data): self
    {
        return new self((int) $data['coupon_id']);
    }
}
