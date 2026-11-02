<?php

namespace Modules\Catalog\Http\Controllers\V1\Categories;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Http\Requests\UploadBannerRequest;
use Modules\Catalog\UseCases\UploadCategoryBannerUseCase;
use Modules\Core\Http\Controllers\BaseController;

final class UploadCategoryBannerController extends BaseController
{
    public function __invoke(int $category, UploadBannerRequest $request, UploadCategoryBannerUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Category banner uploaded.', ['url' => $useCase->execute($category, $request->file('banner'))]);
    }
}
