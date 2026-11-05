<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\ListHomepageProductsUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListHomepageProductsController extends BaseController
{
    public function __invoke(ListHomepageProductsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Homepage products retrieved.', $useCase->execute());
    }
}
