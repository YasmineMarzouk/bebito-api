<?php

namespace Modules\Core\Http\Controllers\V1\Settings;

use Illuminate\Http\JsonResponse;
use Modules\Core\DTOs\Inputs\UpdateSettingsInput;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Core\Http\Requests\UpdateSettingsRequest;
use Modules\Core\UseCases\UpdateSettingsUseCase;

final class UpdateSettingsController extends BaseController
{
    public function __invoke(UpdateSettingsRequest $request, UpdateSettingsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Settings saved.', $useCase->execute(UpdateSettingsInput::from($request->validated())));
    }
}
