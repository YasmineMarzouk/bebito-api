<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\DeleteProductUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class DeleteProductController extends BaseController
{
    public function __invoke(int $product, DeleteProductUseCase $useCase): JsonResponse
    {
        $useCase->execute($product);

        return $this->makeSuccess('Product deleted.', null);
    }
}
