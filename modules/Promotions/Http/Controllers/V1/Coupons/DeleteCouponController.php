<?php

namespace Modules\Promotions\Http\Controllers\V1\Coupons;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Promotions\UseCases\DeleteCouponUseCase;

final class DeleteCouponController extends BaseController
{
    public function __invoke(int $coupon, DeleteCouponUseCase $useCase): JsonResponse
    {
        $useCase->execute($coupon);

        return $this->makeSuccess('Coupon deleted.', null);
    }
}
