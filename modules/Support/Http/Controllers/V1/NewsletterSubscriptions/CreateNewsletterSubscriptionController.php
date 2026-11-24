<?php

namespace Modules\Support\Http\Controllers\V1\NewsletterSubscriptions;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Support\Http\Requests\CreateNewsletterSubscriptionRequest;
use Modules\Support\UseCases\CreateNewsletterSubscriptionUseCase;

final class CreateNewsletterSubscriptionController extends BaseController
{
    public function __invoke(CreateNewsletterSubscriptionRequest $request, CreateNewsletterSubscriptionUseCase $useCase): JsonResponse
    {
        $useCase->execute($request->validated(), $request->user('customer')?->id);

        return $this->makeSuccess('You have been added to the newsletter.', null, 201);
    }
}
