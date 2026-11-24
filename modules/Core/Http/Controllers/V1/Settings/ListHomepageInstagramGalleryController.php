<?php

namespace Modules\Core\Http\Controllers\V1\Settings;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Core\Services\HomepageInstagramGalleryService;

final class ListHomepageInstagramGalleryController extends BaseController
{
    public function __invoke(HomepageInstagramGalleryService $gallery): JsonResponse
    {
        return $this->makeSuccess('Homepage Instagram gallery retrieved.', $gallery->list());
    }
}
