<?php

namespace Modules\Orders\Http\Controllers\V1\Orders;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Orders\DTOs\Inputs\ReplaceOrderInput;
use Modules\Orders\Http\Requests\ReplaceOrderRequest;
use Modules\Orders\UseCases\ManageOrderUseCase;

final class ReplaceOrderController extends BaseController
{
    public function __invoke(int $order, ReplaceOrderRequest $request, ManageOrderUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Order updated.', $useCase->replace($order, (int) $request->user('admin')->id, ReplaceOrderInput::from($request->validated())));
    }
}
