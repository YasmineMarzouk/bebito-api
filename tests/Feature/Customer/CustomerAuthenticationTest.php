<?php

namespace Tests\Feature\Customer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_register_and_access_their_authenticated_profile(): void
    {
        $this->withHeader('Origin', 'http://localhost:4173')->postJson('/api/v1/customer-auth/register', [
            'name' => 'Ada Customer',
            'email' => 'ada@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'phone' => '+216 20 000 000',
            'marketing_opt_in' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ada Customer')
            ->assertJsonPath('data.email', 'ada@example.test');

        $this->withHeader('Origin', 'http://localhost:4173')->getJson('/api/v1/customer-auth/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'ada@example.test');
    }

    public function test_a_customer_can_login_but_an_administrator_cannot_use_customer_authentication(): void
    {
        $this->withHeader('Origin', 'http://localhost:4173')->postJson('/api/v1/customer-auth/register', [
            'name' => 'Ada Customer',
            'email' => 'ada@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertCreated();

        $this->withHeader('Origin', 'http://localhost:4173')->postJson('/api/v1/customer-auth/logout')->assertOk();

        $this->withHeader('Origin', 'http://localhost:4173')->postJson('/api/v1/customer-auth/login', [
            'email' => 'ada@example.test',
            'password' => 'secure-password',
        ])->assertOk()->assertJsonPath('data.email', 'ada@example.test');
    }
}
