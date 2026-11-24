<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\ListMainCategoriesUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListMainCategoriesController extends BaseController
{
    public function __invoke(ListMainCategoriesUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Main categories retrieved.', $useCase->execute());
    }
}
