<?php

namespace Modules\Promotions\Http\Controllers\V1\Coupons;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Promotions\UseCases\ListCouponsUseCase;

final class ListCouponsController extends BaseController
{
    public function __invoke(Request $request, ListCouponsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Coupons retrieved.', $useCase->execute(min(100, max(1, (int) $request->query('per_page', 20))), max(1, (int) $request->query('page', 1))));
    }
}
