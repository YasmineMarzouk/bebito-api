<?php

namespace Modules\Auth\Http\Controllers\V1\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\DTOs\Inputs\LoginInput;
use Modules\Auth\DTOs\Outputs\AuthenticatedUserOutput;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\UseCases\AuthenticateUserUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class LoginController extends BaseController
{
    public function __invoke(LoginRequest $request, AuthenticateUserUseCase $useCase): JsonResponse
    {
        $user = $useCase->execute(LoginInput::from($request->validated()));
        Auth::guard('web')->login($user, (bool) ($request->validated('remember') ?? false));
        $request->session()->regenerate();

        return $this->makeSuccess('Authenticated.', AuthenticatedUserOutput::fromModel($user));
    }
}
