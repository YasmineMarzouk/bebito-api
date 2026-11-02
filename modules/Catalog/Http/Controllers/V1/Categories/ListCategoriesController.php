<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\ListCategoriesUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListCategoriesController extends BaseController
{
    public function __invoke(ListCategoriesUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Categories retrieved.', $useCase->execute());
    }
}
