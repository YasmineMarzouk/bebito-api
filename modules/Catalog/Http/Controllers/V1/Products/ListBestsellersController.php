<?php

namespace Modules\Catalog\Http\Controllers\V1\Products;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\UseCases\ListBestsellersUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListBestsellersController extends BaseController
{
    public function __invoke(ListBestsellersUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Bestsellers retrieved.', $useCase->execute());
    }
}
