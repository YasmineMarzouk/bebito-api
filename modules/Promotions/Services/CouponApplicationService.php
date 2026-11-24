<?php

namespace Modules\Promotions\Services;

use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\Contracts\Services\CouponApplicationServiceInterface;
use Modules\Promotions\Contracts\ValueObjects\CouponApplication;
use Modules\Promotions\Enums\CouponTypeEnum;
use Modules\Promotions\Exceptions\CouponNotApplicableException;
use Modules\Promotions\Exceptions\CouponNotFoundException;
use Modules\Promotions\Models\Coupon;

final class CouponApplicationService implements CouponApplicationServiceInterface
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    public function calculate(int $couponId, int $orderAmount): CouponApplication
    {
        $coupon = $this->coupons->findForUpdate($couponId);

        if ($coupon === null) {
            throw new CouponNotFoundException;
        }

        return $this->applicationFor($coupon, $orderAmount);
    }

    public function recalculate(int $couponId, int $orderAmount, ?int $currentCouponId): CouponApplication
    {
        $coupon = $this->coupons->findForUpdate($couponId);

        if ($coupon === null) {
            throw new CouponNotFoundException;
        }

        return $this->applicationFor($coupon, $orderAmount, $couponId === $currentCouponId);
    }

    public function calculateByCode(string $couponCode, int $orderAmount): CouponApplication
    {
        $coupon = $this->coupons->findByCodeForUpdate(strtoupper(trim($couponCode)));

        if ($coupon === null) {
            throw new CouponNotApplicableException;
        }

        return $this->applicationFor($coupon, $orderAmount);
    }

    public function previewByCode(string $couponCode, int $orderAmount): CouponApplication
    {
        $coupon = $this->coupons->findByCode(strtoupper(trim($couponCode)));

        if ($coupon === null) {
            throw new CouponNotApplicableException;
        }

        return $this->applicationFor($coupon, $orderAmount);
    }

    public function replaceUsage(?int $previousCouponId, int $couponId): void
    {
        if ($previousCouponId === $couponId) {
            return;
        }

        if ($previousCouponId !== null) {
            $this->coupons->decrementUsage($previousCouponId);
        }

        if (! $this->coupons->incrementUsage($couponId)) {
            throw new CouponNotApplicableException;
        }
    }

    public function releaseUsage(?int $couponId): void
    {
        if ($couponId !== null) {
            $this->coupons->decrementUsage($couponId);
        }
    }

    private function applicationFor(Coupon $coupon, int $orderAmount, bool $isAlreadyApplied = false): CouponApplication
    {
        if (! $this->isApplicable($coupon, $orderAmount, $isAlreadyApplied)) {
            throw new CouponNotApplicableException;
        }

        return new CouponApplication($coupon->id, $coupon->code, $this->discountAmount($coupon, $orderAmount));
    }

    private function isApplicable(Coupon $coupon, int $orderAmount, bool $isAlreadyApplied = false): bool
    {
        return $coupon->is_active
            && ($coupon->expires_at === null || ! $coupon->expires_at->copy()->endOfDay()->isPast())
            && ($isAlreadyApplied || $coupon->usage_limit === null || $coupon->usage_count < $coupon->usage_limit)
            && $orderAmount >= $coupon->min_order_amount;
    }

    private function discountAmount(Coupon $coupon, int $orderAmount): int
    {
        $discount = $coupon->type === CouponTypeEnum::Percentage
            ? (int) round($orderAmount * ((int) $coupon->percentage) / 100)
            : (int) $coupon->fixed_amount;

        return min($discount, $orderAmount);
    }
}
