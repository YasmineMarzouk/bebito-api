<?php

namespace Modules\Catalog\Http\Controllers\V1\Collections;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\UpdateCollectionInput;
use Modules\Catalog\Http\Requests\UpdateCollectionRequest;
use Modules\Catalog\UseCases\UpdateCollectionUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UpdateCollectionController extends BaseController
{
    public function __invoke(int $collection, UpdateCollectionRequest $request, UpdateCollectionUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Collection updated.', $useCase->execute($collection, UpdateCollectionInput::from($request->validated())));
    }
}
