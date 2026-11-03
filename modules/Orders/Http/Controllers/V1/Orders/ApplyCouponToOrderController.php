<?php

namespace Modules\Orders\Http\Controllers\V1\Orders;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Orders\DTOs\Inputs\ApplyCouponToOrderInput;
use Modules\Orders\Http\Requests\ApplyCouponToOrderRequest;
use Modules\Orders\UseCases\ApplyCouponToOrderUseCase;

final class ApplyCouponToOrderController extends BaseController
{
    public function __invoke(int $order, ApplyCouponToOrderRequest $request, ApplyCouponToOrderUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Coupon applied to order.', $useCase->execute($order, ApplyCouponToOrderInput::from($request->validated())));
    }
}
