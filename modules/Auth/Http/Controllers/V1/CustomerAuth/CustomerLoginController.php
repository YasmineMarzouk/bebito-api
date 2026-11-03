<?php

namespace Modules\Auth\Http\Controllers\V1\CustomerAuth;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\DTOs\Inputs\LoginInput;
use Modules\Auth\DTOs\Outputs\AuthenticatedUserOutput;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\UseCases\AuthenticateCustomerUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class CustomerLoginController extends BaseController
{
    public function __invoke(LoginRequest $request, AuthenticateCustomerUseCase $useCase): JsonResponse
    {
        $validated = $request->validated();
        $user = $useCase->execute(LoginInput::from($validated));

        Auth::guard('web')->login($user, (bool) ($validated['remember'] ?? false));
        $request->session()->regenerate();

        return $this->makeSuccess('Authenticated.', AuthenticatedUserOutput::fromModel($user));
    }
}
