<?php

namespace Modules\Promotions\UseCases;

use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\DTOs\Outputs\CouponOutput;
use Modules\Promotions\Exceptions\CouponNotFoundException;

final class GetCouponUseCase
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    public function execute(int $couponId): CouponOutput
    {
        $coupon = $this->coupons->find($couponId);

        if ($coupon === null) {
            throw new CouponNotFoundException;
        }

        return CouponOutput::fromModel($coupon);
    }
}
