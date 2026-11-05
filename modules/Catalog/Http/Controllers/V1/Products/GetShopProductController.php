<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\GetShopProductUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class GetShopProductController extends BaseController
{
    public function __invoke(string $identifier, GetShopProductUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Shop product retrieved.', $useCase->execute($identifier));
    }
}
