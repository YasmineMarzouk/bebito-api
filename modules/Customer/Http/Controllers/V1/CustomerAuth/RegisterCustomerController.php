<?php

namespace Modules\Customer\Http\Controllers\V1\CustomerAuth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\DTOs\Outputs\AuthenticatedUserOutput;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Customer\DTOs\Inputs\CreateCustomerInput;
use Modules\Customer\Http\Requests\RegisterCustomerRequest;
use Modules\Customer\UseCases\CreateCustomerUseCase;

final class RegisterCustomerController extends BaseController
{
    public function __invoke(RegisterCustomerRequest $request, CreateCustomerUseCase $useCase): JsonResponse
    {
        $input = CreateCustomerInput::from($request->validated());
        $customer = $useCase->execute($input);

        $user = app(User::class)->newQuery()->findOrFail($customer->id);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $this->makeSuccess('Customer registered.', AuthenticatedUserOutput::fromModel($user), 201);
    }
}
