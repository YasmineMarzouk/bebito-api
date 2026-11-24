<?php

namespace Modules\Support\UseCases;

use Modules\Support\Models\NewsletterSubscription;

final class CreateNewsletterSubscriptionUseCase
{
    /** @param array{email: string} $data */
    public function execute(array $data, ?int $userId): void
    {
        NewsletterSubscription::query()->create([
            'email' => $data['email'],
            'user_id' => $userId,
        ]);
    }

    public function linkToCustomer(string $email, int $userId): void
    {
        NewsletterSubscription::query()->updateOrCreate(
            ['email' => $email],
            ['user_id' => $userId],
        );
    }
}
