<?php

namespace Modules\Catalog\Http\Controllers\V1\Collections;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\UseCases\ListCollectionsUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListCollectionsController extends BaseController
{
    public function __invoke(Request $request, ListCollectionsUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Collections retrieved.', $useCase->execute(6, $this->page($request)));
    }

    private function page(Request $request): int { return max(1, (int) $request->query('page', 1)); }
}
