<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\UpdateProductInput;
use Modules\Catalog\Http\Requests\UpdateProductRequest;
use Modules\Catalog\UseCases\UpdateProductUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UpdateProductController extends BaseController
{
    public function __invoke(int $product, UpdateProductRequest $request, UpdateProductUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Product updated.', $useCase->execute($product, UpdateProductInput::from($request->validated())));
    }
}
