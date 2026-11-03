<?php

namespace Tests\Feature\Customer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Orders\Models\Order;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_and_manage_a_customer(): void
    {
        $customer = $this->withoutMiddleware()
            ->postJson('/api/v1/customers', [
                'name' => 'Ada Customer',
                'email' => 'ada@example.test',
                'password' => 'secure-password',
                'phone' => '+216 20 000 000',
                'manual_badge' => 'vib',
            ])
            ->assertCreated()
            ->assertJsonPath('data.badge', 'vib')
            ->json('data');

        $this->putJson("/api/v1/customers/{$customer['id']}/block", ['is_blocked' => true])
            ->assertOk()
            ->assertJsonPath('data.isBlocked', true);

        $this->putJson("/api/v1/customers/{$customer['id']}", [
            'name' => 'Ada Updated',
            'email' => 'ada@example.test',
            'phone' => '+216 20 000 001',
            'preferred_locale_code' => 'en',
            'marketing_opt_in' => true,
            'manual_badge' => null,
        ])->assertOk()->assertJsonPath('data.name', 'Ada Updated');

        $this->deleteJson("/api/v1/customers/{$customer['id']}")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $customer['id']]);
    }

    public function test_an_uncollected_order_marks_a_customer_high_risk(): void
    {
        $customer = $this->withoutMiddleware()->postJson('/api/v1/customers', [
            'name' => 'Risky Customer',
            'email' => 'risky@example.test',
            'password' => 'secure-password',
        ])->assertCreated()->json('data');

        Order::query()->create([
            'number' => 'BEB-RISK-1',
            'user_id' => $customer['id'],
            'customer_name' => 'Risky Customer',
            'customer_email' => 'risky@example.test',
            'status' => 'not_picked_up',
            'total_amount' => 10000,
        ]);

        $this->getJson("/api/v1/customers/{$customer['id']}")
            ->assertOk()
            ->assertJsonPath('data.badge', 'high_risk');
    }
}
