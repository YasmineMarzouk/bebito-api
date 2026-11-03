<?php

namespace Modules\Promotions\Http\Controllers\V1\Coupons;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Promotions\DTOs\Inputs\CreateCouponInput;
use Modules\Promotions\Http\Requests\CreateCouponRequest;
use Modules\Promotions\UseCases\CreateCouponUseCase;

final class CreateCouponController extends BaseController
{
    public function __invoke(CreateCouponRequest $request, CreateCouponUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Coupon created.', $useCase->execute(CreateCouponInput::from($request->validated())), 201);
    }
}
