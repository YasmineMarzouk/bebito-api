<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\CreateProductInput;
use Modules\Catalog\Http\Requests\CreateProductRequest;
use Modules\Catalog\UseCases\CreateProductUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class CreateProductController extends BaseController
{
    public function __invoke(CreateProductRequest $request, CreateProductUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess(
            'Product created.',
            $useCase->execute(CreateProductInput::from($request->validated())),
            201
        );
    }
}
