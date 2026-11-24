<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Http\Requests\UpdatePackStatusRequest;
use Modules\Catalog\UseCases\UpdatePackStatusUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UpdatePackStatusController extends BaseController
{
    public function __invoke(int $pack, UpdatePackStatusRequest $request, UpdatePackStatusUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Pack status updated.', $useCase->execute($pack, (int) $request->validated('status')));
    }
}
