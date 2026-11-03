<?php

namespace Modules\Customer\Http\Controllers\V1\Customers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Customer\UseCases\DeleteCustomerUseCase;

final class DeleteCustomerController extends BaseController
{
    public function __invoke(int $customer, DeleteCustomerUseCase $useCase): JsonResponse
    {
        $useCase->execute($customer);

        return $this->makeSuccess('Customer deleted.', null);
    }
}
