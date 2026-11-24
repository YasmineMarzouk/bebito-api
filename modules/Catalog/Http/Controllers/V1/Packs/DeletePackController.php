<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\DeletePackUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class DeletePackController extends BaseController
{
    public function __invoke(int $pack, DeletePackUseCase $useCase): JsonResponse
    {
        $useCase->execute($pack);

        return $this->makeSuccess('Pack deleted.', null);
    }
}
