<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Http\Requests\ListPacksRequest;
use Modules\Catalog\UseCases\ListPacksUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class ListPacksController extends BaseController
{
    public function __invoke(ListPacksRequest $request, ListPacksUseCase $useCase): JsonResponse
    {
        $data = $request->validated();
        $search = trim((string) ($data['search'] ?? ''));

        return $this->makeSuccess('Packs retrieved.', $useCase->execute(
            (int) ($data['per_page'] ?? 20),
            (int) ($data['page'] ?? 1),
            $search === '' ? null : $search,
        ));
    }
}
