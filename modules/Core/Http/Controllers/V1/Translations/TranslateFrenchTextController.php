<?php

namespace Modules\Core\Http\Controllers\V1\Translations;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Core\Http\Requests\TranslateFrenchTextRequest;
use Modules\Core\UseCases\TranslateFrenchTextUseCase;

final class TranslateFrenchTextController extends BaseController
{
    public function __invoke(TranslateFrenchTextRequest $request, TranslateFrenchTextUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Text translated.', $useCase->execute($request->validated()));
    }
}
