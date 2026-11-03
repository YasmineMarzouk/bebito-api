<?php

namespace Modules\Customer\Http\Controllers\V1\Customers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Customer\UseCases\ListCustomersUseCase;

final class ListCustomersController extends BaseController
{
    public function __invoke(ListCustomersUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Customers retrieved.', $useCase->execute());
    }
}
