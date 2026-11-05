<?php

namespace Modules\Catalog\Http\Controllers\V1\Collections;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\ListShopCollectionsUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListShopCollectionsController extends BaseController
{
    public function __invoke(ListShopCollectionsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Shop collections retrieved.', $useCase->execute());
    }
}
