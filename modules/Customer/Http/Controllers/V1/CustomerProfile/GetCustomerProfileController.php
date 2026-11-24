<?php

namespace Modules\Customer\Http\Controllers\V1\CustomerProfile;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Customer\UseCases\GetCustomerProfileUseCase;

final class GetCustomerProfileController extends BaseController
{
    public function __invoke(Request $request, GetCustomerProfileUseCase $useCase): JsonResponse
    {
        /** @var User $customer */
        $customer = $request->user('customer');

        return $this->makeSuccess('Customer profile retrieved.', $useCase->execute($customer));
    }
}
