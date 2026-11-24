<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\UseCases\ListProductsUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListProductsController extends BaseController
{
    public function __invoke(Request $request, ListProductsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Products retrieved.', $useCase->execute(10, $this->page($request)));
    }

    private function page(Request $request): int { return max(1, (int) $request->query('page', 1)); }
}
