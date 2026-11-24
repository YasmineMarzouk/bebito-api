<?php

namespace Modules\Orders\Http\Controllers\V1\Orders;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Orders\UseCases\ManageOrderUseCase;

final class GetOrderController extends BaseController
{
    public function __invoke(int $order, ManageOrderUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Order retrieved.', $useCase->get($order));
    }
}
