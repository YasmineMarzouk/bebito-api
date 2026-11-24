<?php

namespace Modules\Catalog\Http\Controllers\V1\Shop;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\ListShopCatalogInput;
use Modules\Catalog\Http\Requests\ListShopCatalogRequest;
use Modules\Catalog\UseCases\ListShopCatalogUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListShopCatalogController extends BaseController
{
    public function __invoke(ListShopCatalogRequest $request, ListShopCatalogUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Shop catalog retrieved.', $useCase->execute(ListShopCatalogInput::from($request->validated())));
    }
}
