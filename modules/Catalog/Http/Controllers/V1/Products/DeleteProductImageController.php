<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\DeleteProductImageUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class DeleteProductImageController extends BaseController
{
    public function __invoke(int $product, int $asset, DeleteProductImageUseCase $useCase): JsonResponse
    {
        $useCase->execute($product, $asset);

        return $this->makeSuccess('Product image deleted.', null);
    }
}
