<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\DeleteBestsellerUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class DeleteBestsellerController extends BaseController
{
    public function __invoke(int $position, DeleteBestsellerUseCase $useCase): JsonResponse
    {
        $useCase->execute($position);

        return $this->makeSuccess('Bestseller removed.', null);
    }
}
