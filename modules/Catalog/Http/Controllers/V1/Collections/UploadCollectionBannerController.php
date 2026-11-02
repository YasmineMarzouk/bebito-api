<?php

namespace Modules\Catalog\Http\Controllers\V1\Collections;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Http\Requests\UploadBannerRequest;
use Modules\Catalog\UseCases\UploadCollectionBannerUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UploadCollectionBannerController extends BaseController
{
    public function __invoke(int $collection, UploadBannerRequest $request, UploadCollectionBannerUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Collection banner uploaded.', ['url' => $useCase->execute($collection, $request->file('banner'))]);
    }
}
