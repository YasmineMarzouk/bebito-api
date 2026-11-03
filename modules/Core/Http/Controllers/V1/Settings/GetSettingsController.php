<?php

namespace Modules\Core\Http\Controllers\V1\Settings;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Core\UseCases\GetSettingsUseCase;

final class GetSettingsController extends BaseController
{
    public function __invoke(GetSettingsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Settings retrieved.', $useCase->execute());
    }
}
