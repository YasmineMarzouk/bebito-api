<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\CreatePackInput;
use Modules\Catalog\Http\Requests\CreatePackRequest;
use Modules\Catalog\UseCases\CreatePackUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class CreatePackController extends BaseController
{
    public function __invoke(CreatePackRequest $request, CreatePackUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Pack created.', $useCase->execute(CreatePackInput::from($request->validated())), 201);
    }
}
