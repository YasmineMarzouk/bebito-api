<?php

namespace Modules\Promotions\Http\Controllers\V1\Coupons;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Promotions\UseCases\ListCouponsUseCase;

final class ListCouponsController extends BaseController
{
    public function __invoke(ListCouponsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Coupons retrieved.', $useCase->execute());
    }
}
