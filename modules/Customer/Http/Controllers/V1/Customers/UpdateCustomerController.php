<?php

namespace Modules\Customer\Http\Controllers\V1\Customers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Customer\DTOs\Inputs\UpdateCustomerInput;
use Modules\Customer\Http\Requests\UpdateCustomerRequest;
use Modules\Customer\UseCases\UpdateCustomerUseCase;

final class UpdateCustomerController extends BaseController
{
    public function __invoke(int $customer, UpdateCustomerRequest $request, UpdateCustomerUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Customer updated.', $useCase->execute($customer, UpdateCustomerInput::from($request->validated())));
    }
}
