<?php

namespace Modules\Promotions\DTOs\Outputs;

use Modules\Promotions\Models\Coupon;

final readonly class CouponOutput
{
    public function __construct(
        public int $id,
        public string $code,
        public string $type,
        public ?int $percentage,
        public ?int $fixedAmount,
        public int $minOrderAmount,
        public ?int $usageLimit,
        public int $usageCount,
        public ?string $expiresAt,
        public bool $isActive,
    ) {}

    public static function fromModel(Coupon $coupon): self
    {
        return new self(
            id: $coupon->id,
            code: $coupon->code,
            type: $coupon->type,
            percentage: $coupon->percentage,
            fixedAmount: $coupon->fixed_amount,
            minOrderAmount: $coupon->min_order_amount,
            usageLimit: $coupon->usage_limit,
            usageCount: $coupon->usage_count,
            expiresAt: $coupon->expires_at?->format('Y-m-d'),
            isActive: $coupon->is_active,
        );
    }
}
