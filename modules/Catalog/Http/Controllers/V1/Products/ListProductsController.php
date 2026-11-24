<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Http\Requests\ListProductsRequest;
use Modules\Catalog\UseCases\ListProductsUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListProductsController extends BaseController
{
    public function __invoke(ListProductsRequest $request, ListProductsUseCase $useCase): JsonResponse
    {
        $data = $request->validated();
        $search = trim((string) ($data['search'] ?? ''));

        return $this->makeSuccess('Products retrieved.', $useCase->execute(
            (int) ($data['per_page'] ?? 20),
            (int) ($data['page'] ?? 1),
            $search === '' ? null : $search,
        ));
    }
}
