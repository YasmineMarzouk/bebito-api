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
            'shopping_for' => 'my_bebito',
            'gender' => 'mamito',
            'children' => [
                ['name' => 'Lina', 'birth_date' => '2024-02-15', 'gender' => 'female'],
                ['name' => 'Adam', 'birth_date' => '2022-09-01', 'gender' => 'male'],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ada Customer')
            ->assertJsonPath('data.email', 'ada@example.test')
            ->assertJsonPath('data.gender', 'mamito');

        $this->assertDatabaseHas('customer_children', [
            'name' => 'Lina',
            'gender' => 'female',
        ]);

        $this->withHeader('Origin', 'http://localhost:4173')->getJson('/api/v1/customer-auth/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'ada@example.test')
            ->assertJsonPath('data.gender', 'mamito');
    }

    public function test_registration_for_a_customer_own_bebito_requires_gender_and_child_details(): void
    {
        $this->postJson('/api/v1/customer-auth/register', [
            'name' => 'Ada Customer',
            'email' => 'ada@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'shopping_for' => 'my_bebito',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gender', 'children']);
    }

    public function test_registration_requires_each_child_name_birth_date_and_gender(): void
    {
        $this->postJson('/api/v1/customer-auth/register', [
            'name' => 'Ada Customer',
            'email' => 'ada@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'shopping_for' => 'my_bebito',
            'gender' => 'mamito',
            'children' => [[]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['children.0.name', 'children.0.birth_date', 'children.0.gender']);
    }

    public function test_a_customer_can_login_but_an_administrator_cannot_use_customer_authentication(): void
    {
        $this->withHeader('Origin', 'http://localhost:4173')->postJson('/api/v1/customer-auth/register', [
            'name' => 'Ada Customer',
            'email' => 'ada@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'shopping_for' => 'gift',
        ])->assertCreated();

        $this->withHeader('Origin', 'http://localhost:4173')->postJson('/api/v1/customer-auth/logout')->assertOk();

        $this->withHeader('Origin', 'http://localhost:4173')->postJson('/api/v1/customer-auth/login', [
            'email' => 'ada@example.test',
            'password' => 'secure-password',
        ])->assertOk()->assertJsonPath('data.email', 'ada@example.test');
    }

    public function test_a_customer_session_cannot_restore_an_administrator_dashboard_session(): void
    {
        $this->withHeader('Origin', 'http://localhost:4173')->postJson('/api/v1/customer-auth/register', [
            'name' => 'Ada Customer',
            'email' => 'ada@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'shopping_for' => 'gift',
        ])->assertCreated();

        $this->withHeader('Origin', 'http://localhost:4173')
            ->getJson('/api/v1/auth/user')
            ->assertForbidden();
    }
}
