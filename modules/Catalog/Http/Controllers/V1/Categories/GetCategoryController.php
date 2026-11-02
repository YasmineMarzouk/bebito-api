<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\GetCategoryUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class GetCategoryController extends BaseController
{
    public function __invoke(int $category, GetCategoryUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Category retrieved.', $useCase->execute($category));
    }
}
