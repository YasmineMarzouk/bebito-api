<?php

namespace Modules\Core\Http\Controllers\V1\Analytics;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Core\UseCases\GetDashboardAnalyticsUseCase;

final class GetDashboardAnalyticsController extends BaseController
{
    public function __invoke(GetDashboardAnalyticsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Dashboard analytics retrieved.', $useCase->execute());
    }
}
