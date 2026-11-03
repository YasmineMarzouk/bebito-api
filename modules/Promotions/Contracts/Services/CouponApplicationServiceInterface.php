<?php

namespace Modules\Promotions\Contracts\Services;

use Modules\Promotions\Contracts\ValueObjects\CouponApplication;

interface CouponApplicationServiceInterface
{
    public function calculate(int $couponId, int $orderAmount): CouponApplication;

    public function replaceUsage(?int $previousCouponId, int $couponId): void;
}
