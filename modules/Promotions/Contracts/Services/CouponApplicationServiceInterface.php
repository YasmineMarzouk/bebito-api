<?php

namespace Modules\Promotions\Contracts\Services;

use Modules\Promotions\Contracts\ValueObjects\CouponApplication;

interface CouponApplicationServiceInterface
{
    public function calculate(int $couponId, int $orderAmount): CouponApplication;

    public function recalculate(int $couponId, int $orderAmount, ?int $currentCouponId): CouponApplication;

    public function previewByCode(string $couponCode, int $orderAmount): CouponApplication;

    public function calculateByCode(string $couponCode, int $orderAmount): CouponApplication;

    public function replaceUsage(?int $previousCouponId, int $couponId): void;

    public function releaseUsage(?int $couponId): void;
}
