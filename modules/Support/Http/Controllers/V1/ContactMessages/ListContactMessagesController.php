<?php

namespace Modules\Support\Http\Controllers\V1\ContactMessages;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Support\UseCases\ListContactMessagesUseCase;

final class ListContactMessagesController extends BaseController
{
    public function __invoke(ListContactMessagesUseCase $useCase): JsonResponse
    {
        return $this->makeSuccess('Contact messages retrieved.', $useCase->execute());
    }
}
