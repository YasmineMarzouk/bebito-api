<?php

namespace Modules\Promotions\Http\Controllers\V1\Coupons;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Promotions\DTOs\Inputs\UpdateCouponInput;
use Modules\Promotions\Http\Requests\UpdateCouponRequest;
use Modules\Promotions\UseCases\UpdateCouponUseCase;

final class UpdateCouponController extends BaseController
{
    public function __invoke(int $coupon, UpdateCouponRequest $request, UpdateCouponUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Coupon updated.', $useCase->execute($coupon, UpdateCouponInput::from($request->validated())));
    }
}
