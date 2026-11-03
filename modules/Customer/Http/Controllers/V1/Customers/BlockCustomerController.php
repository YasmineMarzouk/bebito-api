<?php

namespace Modules\Customer\Http\Controllers\V1\Customers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Customer\DTOs\Inputs\BlockCustomerInput;
use Modules\Customer\Http\Requests\BlockCustomerRequest;
use Modules\Customer\UseCases\BlockCustomerUseCase;

final class BlockCustomerController extends BaseController
{
    public function __invoke(int $customer, BlockCustomerRequest $request, BlockCustomerUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Customer status updated.', $useCase->execute($customer, BlockCustomerInput::from($request->validated())));
    }
}
