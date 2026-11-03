<?php

namespace Modules\Promotions\UseCases;

use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\Exceptions\CouponNotFoundException;

final class DeleteCouponUseCase
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    public function execute(int $couponId): void
    {
        $coupon = $this->coupons->find($couponId);

        if ($coupon === null) {
            throw new CouponNotFoundException;
        }

        $this->coupons->delete($coupon);
    }
}
