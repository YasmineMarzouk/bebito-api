<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\GetProductUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class GetProductController extends BaseController
{
    public function __invoke(int $product, GetProductUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Product retrieved.', $useCase->execute($product));
    }
}
