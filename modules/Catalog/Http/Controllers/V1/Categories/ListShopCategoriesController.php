<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\ListShopCategoriesUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListShopCategoriesController extends BaseController
{
    public function __invoke(ListShopCategoriesUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Shop categories retrieved.', $useCase->execute());
    }
}
