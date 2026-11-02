<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\DeleteCategoryUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class DeleteCategoryController extends BaseController
{
    public function __invoke(int $category, DeleteCategoryUseCase $useCase): JsonResponse
    {
        $useCase->execute($category);

        return $this->makeSuccess('Category deleted.', null);
    }
}
