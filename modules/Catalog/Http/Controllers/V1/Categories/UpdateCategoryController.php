<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\UpdateCategoryInput;
use Modules\Catalog\Http\Requests\UpdateCategoryRequest;
use Modules\Catalog\UseCases\UpdateCategoryUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UpdateCategoryController extends BaseController
{
    public function __invoke(int $category, UpdateCategoryRequest $request, UpdateCategoryUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Category updated.', $useCase->execute($category, UpdateCategoryInput::from($request->validated())));
    }
}
