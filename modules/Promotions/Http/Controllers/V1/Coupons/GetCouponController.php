<?php

namespace Modules\Promotions\Http\Controllers\V1\Coupons;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Promotions\UseCases\GetCouponUseCase;

final class GetCouponController extends BaseController
{
    public function __invoke(int $coupon, GetCouponUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Coupon retrieved.', $useCase->execute($coupon));
    }
}
