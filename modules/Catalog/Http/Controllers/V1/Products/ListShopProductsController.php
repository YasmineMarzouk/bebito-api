<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\ListShopProductsInput;
use Modules\Catalog\Http\Requests\ListShopProductsRequest;
use Modules\Catalog\UseCases\ListShopProductsUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListShopProductsController extends BaseController
{
    public function __invoke(ListShopProductsRequest $request, ListShopProductsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Shop products retrieved.', $useCase->execute(ListShopProductsInput::from($request->validated())));
    }
}
