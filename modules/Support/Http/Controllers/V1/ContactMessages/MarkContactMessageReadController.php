<?php

namespace Modules\Support\Http\Controllers\V1\ContactMessages;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Support\Models\ContactMessage;
use Modules\Support\UseCases\MarkContactMessageReadUseCase;

final class MarkContactMessageReadController extends BaseController
{
    public function __invoke(ContactMessage $message, MarkContactMessageReadUseCase $useCase): JsonResponse
    {
        $useCase->execute($message);

        return $this->makeSuccess('Contact message marked as read.', null);
    }
}
