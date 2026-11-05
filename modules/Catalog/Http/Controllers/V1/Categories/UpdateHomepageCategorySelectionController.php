<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\UpdateHomepageCategorySelectionInput;
use Modules\Catalog\Http\Requests\UpdateHomepageCategorySelectionRequest;
use Modules\Catalog\UseCases\UpdateHomepageCategorySelectionUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UpdateHomepageCategorySelectionController extends BaseController
{
    public function __invoke(UpdateHomepageCategorySelectionRequest $request, UpdateHomepageCategorySelectionUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Homepage categories updated.', $useCase->execute(UpdateHomepageCategorySelectionInput::from($request->validated())));
    }
}
