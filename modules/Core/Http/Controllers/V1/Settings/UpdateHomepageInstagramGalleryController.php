<?php

namespace Modules\Core\Http\Controllers\V1\Settings;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Core\Http\Requests\UpdateHomepageInstagramGalleryRequest;
use Modules\Core\Services\HomepageInstagramGalleryService;

final class UpdateHomepageInstagramGalleryController extends BaseController
{
    public function __invoke(UpdateHomepageInstagramGalleryRequest $request, HomepageInstagramGalleryService $gallery): JsonResponse
    {
        return $this->makeSuccess('Homepage Instagram gallery saved.', $gallery->replace($request->file('images')));
    }
}
