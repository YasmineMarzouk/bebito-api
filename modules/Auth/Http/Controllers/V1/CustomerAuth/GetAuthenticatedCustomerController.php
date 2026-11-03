<?php

namespace Modules\Auth\Http\Controllers\V1\CustomerAuth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\DTOs\Outputs\AuthenticatedUserOutput;
use Modules\Auth\UseCases\GetAuthenticatedCustomerUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class GetAuthenticatedCustomerController extends BaseController
{
    public function __invoke(Request $request, GetAuthenticatedCustomerUseCase $useCase): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->makeSuccess('Authenticated customer retrieved.', AuthenticatedUserOutput::fromModel($useCase->execute($user)));
    }
}
