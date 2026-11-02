<?php

namespace Modules\Catalog\Http\Controllers\V1\Collections;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\CreateCollectionInput;
use Modules\Catalog\Http\Requests\CreateCollectionRequest;
use Modules\Catalog\UseCases\CreateCollectionUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class CreateCollectionController extends BaseController
{
    public function __invoke(CreateCollectionRequest $request, CreateCollectionUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess(
            'Collection created.',
            $useCase->execute(CreateCollectionInput::from($request->validated()), $request->file('banner')),
            201
        );
    }
}
