<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\UseCases\ListPacksUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListPacksController extends BaseController
{
    public function __invoke(Request $request, ListPacksUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Packs retrieved.', $useCase->execute($this->perPage($request), $this->page($request)));
    }

    private function perPage(Request $request): int { return min(100, max(1, (int) $request->query('per_page', 20))); }
    private function page(Request $request): int { return max(1, (int) $request->query('page', 1)); }
}
