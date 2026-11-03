<?php

namespace Modules\Customer\Http\Controllers\V1\Customers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Customer\UseCases\GetCustomerUseCase;

final class GetCustomerController extends BaseController
{
    public function __invoke(int $customer, GetCustomerUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Customer retrieved.', $useCase->execute($customer));
    }
}
