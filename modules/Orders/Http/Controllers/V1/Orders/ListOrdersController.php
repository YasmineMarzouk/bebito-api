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
        return $this->makeSuccess('Orders retrieved.', $useCase->execute($request->integer('customer_id') ?: null, min(100, max(1, (int) $request->query('per_page', 20))), max(1, (int) $request->query('page', 1))));
    }
}
