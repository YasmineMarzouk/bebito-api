<?php

namespace Modules\Support\Http\Controllers\V1\ContactMessages;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Support\Http\Requests\CreateContactMessageRequest;
use Modules\Support\UseCases\CreateContactMessageUseCase;

final class CreateContactMessageController extends BaseController
{
    public function __invoke(CreateContactMessageRequest $request, CreateContactMessageUseCase $useCase): JsonResponse
    {
        $useCase->execute($request->validated());

        return $this->makeSuccess('Your message has been sent.', null, 201);
    }
}
