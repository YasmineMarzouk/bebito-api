<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\UseCases\ListCategoriesUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListCategoriesController extends BaseController
{
    public function __invoke(Request $request, ListCategoriesUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Categories retrieved.', $useCase->execute(10, $this->page($request)));
    }

    private function page(Request $request): int { return max(1, (int) $request->query('page', 1)); }
}
