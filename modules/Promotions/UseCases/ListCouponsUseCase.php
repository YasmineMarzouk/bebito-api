<?php

namespace Modules\Promotions\UseCases;

use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\DTOs\Outputs\CouponOutput;

final class ListCouponsUseCase
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    /** @return array<int, CouponOutput> */
    public function execute(): array
    {
        return $this->coupons->all()->map(CouponOutput::fromModel(...))->all();
    }
}
