<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\UserTypeEnum;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_subscribe_with_a_unique_email_address(): void
    {
        $this->postJson('/api/v1/store/newsletter-subscriptions', [
            'email' => 'NEWSLETTER@example.test',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'You have been added to the newsletter.');

        $this->assertDatabaseHas('newsletter_subscription', [
            'email' => 'newsletter@example.test',
            'user_id' => null,
        ]);
    }

    public function test_subscription_email_must_be_unique(): void
    {
        $this->postJson('/api/v1/store/newsletter-subscriptions', [
            'email' => 'newsletter@example.test',
        ])->assertCreated();

        $this->postJson('/api/v1/store/newsletter-subscriptions', [
            'email' => 'NEWSLETTER@example.test',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('newsletter_subscription', 1);
    }

    public function test_subscription_requires_a_valid_email_address(): void
    {
        $this->postJson('/api/v1/store/newsletter-subscriptions', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->postJson('/api/v1/store/newsletter-subscriptions', [
            'email' => 'not-an-email',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('newsletter_subscription', 0);
    }

    public function test_authenticated_customer_subscription_is_linked_to_the_customer(): void
    {
        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);

        $this->actingAs($customer, 'customer')
            ->postJson('/api/v1/store/newsletter-subscriptions', [
                'email' => 'newsletter@example.test',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('newsletter_subscription', [
            'email' => 'newsletter@example.test',
            'user_id' => $customer->id,
        ]);
    }
}
