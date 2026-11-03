<?php

namespace Modules\Customer\Http\Controllers\V1\Customers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Customer\DTOs\Inputs\CreateCustomerInput;
use Modules\Customer\Http\Requests\CreateCustomerRequest;
use Modules\Customer\UseCases\CreateCustomerUseCase;

final class CreateCustomerController extends BaseController
{
    public function __invoke(CreateCustomerRequest $request, CreateCustomerUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Customer created.', $useCase->execute(CreateCustomerInput::from($request->validated())), 201);
    }
}
