<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Http\Requests\UpdateProductStatusRequest;
use Modules\Catalog\UseCases\UpdateProductStatusUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UpdateProductStatusController extends BaseController
{
    public function __invoke(int $product, UpdateProductStatusRequest $request, UpdateProductStatusUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Product status updated.', $useCase->execute($product, (int) $request->validated('status')));
    }
}
