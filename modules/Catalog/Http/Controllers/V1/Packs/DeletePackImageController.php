<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\DeletePackImageUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class DeletePackImageController extends BaseController
{
    public function __invoke(int $pack, int $asset, DeletePackImageUseCase $useCase): JsonResponse
    {
        $useCase->execute($pack, $asset);

        return $this->makeSuccess('Pack image deleted.', null);
    }
}
