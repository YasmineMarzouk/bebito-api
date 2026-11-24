<?php

namespace Modules\Customer\Http\Controllers\V1\Customers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Customer\UseCases\ListCustomersUseCase;

final class ListCustomersController extends BaseController
{
    public function __invoke(Request $request, ListCustomersUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Customers retrieved.', $useCase->execute(min(100, max(1, (int) $request->query('per_page', 20))), max(1, (int) $request->query('page', 1))));
    }
}
