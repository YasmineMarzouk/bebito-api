<?php

namespace Modules\Orders\Http\Controllers\V1\Orders;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Orders\Http\Requests\ListCustomerOrdersRequest;
use Modules\Orders\UseCases\ListCustomerOrdersUseCase;

final class ListCustomerOrdersController extends BaseController
{
    public function __invoke(ListCustomerOrdersRequest $request, ListCustomerOrdersUseCase $useCase): JsonResponse
    {
        /** @var User $customer */
        $customer = $request->user('customer');

        return $this->makeSuccess('Orders retrieved.', $useCase->execute($customer->id, $request->integer('page', 1)));
    }
}
