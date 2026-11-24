<?php

namespace Modules\Catalog\Http\Controllers\V1\Packs;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Http\Requests\SetProductPrimaryImageRequest;
use Modules\Catalog\UseCases\SetPackPrimaryImageUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class SetPackPrimaryImageController extends BaseController
{
    public function __invoke(int $pack, SetProductPrimaryImageRequest $request, SetPackPrimaryImageUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Primary pack image updated.', $useCase->execute($pack, (int) $request->validated('primary_image_id')));
    }
}
