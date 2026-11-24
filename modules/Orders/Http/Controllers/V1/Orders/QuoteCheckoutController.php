<?php

namespace Modules\Orders\Http\Controllers\V1\Orders;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Orders\DTOs\Inputs\QuoteCheckoutInput;
use Modules\Orders\Http\Requests\QuoteCheckoutRequest;
use Modules\Orders\UseCases\QuoteCheckoutUseCase;

final class QuoteCheckoutController extends BaseController
{
    public function __invoke(QuoteCheckoutRequest $request, QuoteCheckoutUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Checkout quote retrieved.', $useCase->execute(QuoteCheckoutInput::from($request->validated())));
    }
}
