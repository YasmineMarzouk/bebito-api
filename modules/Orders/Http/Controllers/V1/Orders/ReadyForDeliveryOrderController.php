<?php

namespace Modules\Orders\Http\Controllers\V1\Orders;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Orders\DTOs\Inputs\OrderLifecycleInput;
use Modules\Orders\Http\Requests\OrderLifecycleRequest;
use Modules\Orders\UseCases\ManageOrderUseCase;

final class ReadyForDeliveryOrderController extends BaseController
{
    public function __invoke(int $order, OrderLifecycleRequest $request, ManageOrderUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Order is ready and the customer has been notified.', $useCase->readyForDelivery($order, (int) $request->user('admin')->id, OrderLifecycleInput::from($request->validated())));
    }
}
