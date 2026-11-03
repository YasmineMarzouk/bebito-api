<?php

namespace Modules\Orders\Http\Controllers\V1\Orders;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Orders\UseCases\ListOrdersUseCase;

final class ListOrdersController extends BaseController
{
    public function __invoke(Request $request, ListOrdersUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Orders retrieved.', $useCase->execute($request->integer('customer_id') ?: null));
    }
}
