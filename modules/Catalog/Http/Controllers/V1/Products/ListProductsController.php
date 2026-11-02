<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\ListProductsUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListProductsController extends BaseController
{
    public function __invoke(ListProductsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Products retrieved.', $useCase->execute());
    }
}
