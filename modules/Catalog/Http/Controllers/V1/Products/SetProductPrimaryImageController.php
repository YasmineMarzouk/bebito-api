<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Http\Requests\SetProductPrimaryImageRequest;
use Modules\Catalog\UseCases\SetProductPrimaryImageUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class SetProductPrimaryImageController extends BaseController
{
    public function __invoke(int $product, SetProductPrimaryImageRequest $request, SetProductPrimaryImageUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess(
            'Primary product image updated.',
            $useCase->execute($product, (int) $request->validated('primary_image_id')),
        );
    }
}
