<?php

namespace Modules\Auth\Http\Controllers\V1\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\DTOs\Outputs\AuthenticatedUserOutput;
use Modules\Auth\UseCases\GetAuthenticatedUserUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class GetAuthenticatedUserController extends BaseController
{
    public function __invoke(Request $request, GetAuthenticatedUserUseCase $useCase): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->makeSuccess('Authenticated user retrieved.', AuthenticatedUserOutput::fromModel($useCase->execute($user)));
    }
}
