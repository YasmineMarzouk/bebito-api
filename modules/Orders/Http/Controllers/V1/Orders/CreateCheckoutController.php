<?php

namespace Modules\Orders\Http\Controllers\V1\Orders;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Orders\DTOs\Inputs\CreateCheckoutInput;
use Modules\Orders\DTOs\Outputs\CheckoutOrderOutput;
use Modules\Orders\Http\Requests\CreateCheckoutRequest;
use Modules\Orders\UseCases\CreateCheckoutUseCase;

final class CreateCheckoutController extends BaseController
{
    public function __invoke(CreateCheckoutRequest $request, CreateCheckoutUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Order created.', CheckoutOrderOutput::fromModel(
            $useCase->execute($request->user()?->id, CreateCheckoutInput::from($request->validated())),
        ), 201);
    }
}
