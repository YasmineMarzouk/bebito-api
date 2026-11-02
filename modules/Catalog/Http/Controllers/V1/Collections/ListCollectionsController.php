<?php

namespace Modules\Catalog\Http\Controllers\V1\Collections;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\ListCollectionsUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListCollectionsController extends BaseController
{
    public function __invoke(ListCollectionsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Collections retrieved.', $useCase->execute());
    }
}
