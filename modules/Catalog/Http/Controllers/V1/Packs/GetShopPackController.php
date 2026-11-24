<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\GetShopPackUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class GetShopPackController extends BaseController
{
    public function __invoke(string $identifier, GetShopPackUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Shop pack retrieved.', $useCase->execute($identifier));
    }
}
