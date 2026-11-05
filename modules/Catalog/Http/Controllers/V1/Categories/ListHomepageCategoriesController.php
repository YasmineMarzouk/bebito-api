<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\ListHomepageCategoriesUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListHomepageCategoriesController extends BaseController
{
    public function __invoke(ListHomepageCategoriesUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Homepage categories retrieved.', $useCase->execute());
    }
}
