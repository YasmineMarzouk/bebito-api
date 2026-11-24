<?php

namespace Modules\Support\Http\Controllers\V1\ContactMessages;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Support\Models\ContactMessage;
use Modules\Support\UseCases\ArchiveContactMessageUseCase;

final class ArchiveContactMessageController extends BaseController
{
    public function __invoke(ContactMessage $message, ArchiveContactMessageUseCase $useCase): JsonResponse
    {
        $useCase->execute($message);

        return $this->makeSuccess('Contact message archived.', null);
    }
}
