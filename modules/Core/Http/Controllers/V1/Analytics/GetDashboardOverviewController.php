<?php

namespace Modules\Core\Http\Controllers\V1\Analytics;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Core\UseCases\GetDashboardOverviewUseCase;

final class GetDashboardOverviewController extends BaseController
{
    public function __invoke(GetDashboardOverviewUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Dashboard overview retrieved.', $useCase->execute());
    }
}
