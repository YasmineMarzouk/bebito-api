<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\ListShopPacksUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListShopPacksController extends BaseController
{
    public function __invoke(ListShopPacksUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Shop packs retrieved.', $useCase->execute());
    }
}
