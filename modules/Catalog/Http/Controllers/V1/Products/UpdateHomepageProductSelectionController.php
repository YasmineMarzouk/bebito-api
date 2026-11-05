<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\UpdateHomepageProductSelectionInput;
use Modules\Catalog\Http\Requests\UpdateHomepageProductSelectionRequest;
use Modules\Catalog\UseCases\UpdateHomepageProductSelectionUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UpdateHomepageProductSelectionController extends BaseController
{
    public function __invoke(UpdateHomepageProductSelectionRequest $request, UpdateHomepageProductSelectionUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Homepage products updated.', $useCase->execute(UpdateHomepageProductSelectionInput::from($request->validated())));
    }
}
