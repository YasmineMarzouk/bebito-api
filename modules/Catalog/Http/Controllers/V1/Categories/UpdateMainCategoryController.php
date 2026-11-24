<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Http\Requests\UpdateMainCategoryRequest;
use Modules\Catalog\UseCases\UpdateMainCategoryUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UpdateMainCategoryController extends BaseController
{
    public function __invoke(UpdateMainCategoryRequest $request, UpdateMainCategoryUseCase $useCase): JsonResponse
    {
        $data = $request->validated();

        return $this->makeSuccess(
            'Main category updated.',
            $useCase->execute((int) $data['category_id'], (int) $data['position']),
        );
    }
}
