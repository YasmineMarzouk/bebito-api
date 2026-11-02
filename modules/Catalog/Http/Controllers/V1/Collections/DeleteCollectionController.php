<?php

namespace Modules\Catalog\Http\Controllers\V1\Collections;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\DeleteCollectionUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class DeleteCollectionController extends BaseController
{
    public function __invoke(int $collection, DeleteCollectionUseCase $useCase): JsonResponse
    {
        $useCase->execute($collection);

        return $this->makeSuccess('Collection deleted.', null);
    }
}
