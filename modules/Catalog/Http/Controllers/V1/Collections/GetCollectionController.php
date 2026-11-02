<?php

namespace Modules\Catalog\Http\Controllers\V1\Collections;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\GetCollectionUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class GetCollectionController extends BaseController
{
    public function __invoke(int $collection, GetCollectionUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Collection retrieved.', $useCase->execute($collection));
    }
}
