<?php

namespace Modules\Support\Http\Controllers\V1\ContactMessages;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Support\Models\ContactMessage;
use Modules\Support\UseCases\DeleteContactMessageUseCase;

final class DeleteContactMessageController extends BaseController
{
    public function __invoke(ContactMessage $message, DeleteContactMessageUseCase $useCase): JsonResponse
    {
        $useCase->execute($message);

        return $this->makeSuccess('Contact message deleted.', null);
    }
}
