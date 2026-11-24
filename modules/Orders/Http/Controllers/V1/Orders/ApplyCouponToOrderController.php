<?php

namespace Modules\Orders\Http\Controllers\V1\Orders;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Orders\Http\Requests\ApplyCouponToOrderRequest;
use Modules\Orders\UseCases\ManageOrderUseCase;

final class ApplyCouponToOrderController extends BaseController
{
    public function __invoke(int $order, ApplyCouponToOrderRequest $request, ManageOrderUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Coupon applied to order.', $useCase->applyCoupon($order, (int) $request->user('admin')->id, $request->integer('coupon_id')));
    }
}
