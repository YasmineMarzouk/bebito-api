<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\CreateCategoryInput;
use Modules\Catalog\Http\Requests\CreateCategoryRequest;
use Modules\Catalog\UseCases\CreateCategoryUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class CreateCategoryController extends BaseController
{
    public function __invoke(CreateCategoryRequest $request, CreateCategoryUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Category created.', $useCase->execute(CreateCategoryInput::from($request->validated()), $request->file('banner')), 201);
    }
}
