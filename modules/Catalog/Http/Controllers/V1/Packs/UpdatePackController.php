<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\DTOs\Inputs\UpdatePackInput;
use Modules\Catalog\Http\Requests\UpdatePackRequest;
use Modules\Catalog\UseCases\UpdatePackUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UpdatePackController extends BaseController
{
    public function __invoke(int $pack, UpdatePackRequest $request, UpdatePackUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Pack updated.', $useCase->execute($pack, UpdatePackInput::from($request->validated())));
    }
}
