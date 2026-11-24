<?php

namespace Modules\Promotions\UseCases;

use Modules\Promotions\Contracts\Repositories\CouponRepositoryInterface;
use Modules\Promotions\DTOs\Outputs\CouponOutput;
use Modules\Core\DTOs\Outputs\PaginatedOutput;

final class ListCouponsUseCase
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    public function execute(int $perPage, int $page): PaginatedOutput
    {
        $coupons = $this->coupons->paginate($perPage, $page);

        return new PaginatedOutput($coupons->getCollection()->map(CouponOutput::fromModel(...))->all(), $coupons->currentPage(), $coupons->lastPage(), $coupons->perPage(), $coupons->total());
    }
}
