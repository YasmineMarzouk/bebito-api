<?php

namespace Modules\Promotions\UseCases;

use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\DTOs\Inputs\UpdateCouponInput;
use Modules\Promotions\DTOs\Outputs\CouponOutput;
use Modules\Promotions\Exceptions\CouponCodeAlreadyExistsException;
use Modules\Promotions\Exceptions\CouponNotFoundException;

final class UpdateCouponUseCase
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    public function execute(int $couponId, UpdateCouponInput $input): CouponOutput
    {
        $coupon = $this->coupons->find($couponId);

        if ($coupon === null) {
            throw new CouponNotFoundException;
        }

        if ($this->coupons->codeExists($input->code, $couponId)) {
            throw new CouponCodeAlreadyExistsException;
        }

        return CouponOutput::fromModel($this->coupons->update($coupon, $input));
    }
}
