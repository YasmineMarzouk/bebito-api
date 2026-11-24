<?php

namespace Modules\Customer\Http\Controllers\V1\CustomerProfile;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Customer\DTOs\Inputs\UpdateCustomerProfileInput;
use Modules\Customer\Http\Requests\UpdateCustomerProfileRequest;
use Modules\Customer\UseCases\UpdateCustomerProfileUseCase;

final class UpdateCustomerProfileController extends BaseController
{
    public function __invoke(UpdateCustomerProfileRequest $request, UpdateCustomerProfileUseCase $useCase): JsonResponse
    {
        /** @var User $customer */
        $customer = $request->user('customer');

        return $this->makeSuccess('Customer profile updated.', $useCase->execute($customer, UpdateCustomerProfileInput::from($request->validated())));
    }
}
