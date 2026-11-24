<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\DeleteMainCategoryUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class DeleteMainCategoryController extends BaseController
{
    public function __invoke(int $position, DeleteMainCategoryUseCase $useCase): JsonResponse
    {
        $useCase->execute($position);

        return $this->makeSuccess('Main category removed.', null);
    }
}
