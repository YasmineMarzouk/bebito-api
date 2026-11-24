<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\GetPackUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class GetPackController extends BaseController
{
    public function __invoke(int $pack, GetPackUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Pack retrieved.', $useCase->execute($pack));
    }
}
