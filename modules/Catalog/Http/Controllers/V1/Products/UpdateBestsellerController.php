<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Http\Requests\UpdateBestsellerRequest;
use Modules\Catalog\UseCases\UpdateBestsellerUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UpdateBestsellerController extends BaseController
{
    public function __invoke(UpdateBestsellerRequest $request, UpdateBestsellerUseCase $useCase): JsonResponse
    {
        $data = $request->validated();

        return $this->makeSuccess(
            'Bestseller updated.',
            $useCase->execute((int) $data['product_id'], (int) $data['position']),
        );
    }
}
