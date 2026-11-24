<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Enums\UserTypeEnum;
use Tests\TestCase;

class AuthenticationIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_csrf_bootstrap_issues_admin_context_cookies(): void
    {
        $this->withHeader('Origin', 'http://localhost:3000')
            ->getJson('/api/v1/admin/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('bebito-admin-session')
            ->assertCookie('XSRF-ADMIN-TOKEN');
    }

    public function test_customer_csrf_bootstrap_issues_customer_context_cookies(): void
    {
        $this->withHeader('Origin', 'http://localhost:4173')
            ->getJson('/api/v1/customer/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('bebito-customer-session')
            ->assertCookie('XSRF-CUSTOMER-TOKEN');
    }

    public function test_customer_credentials_cannot_authenticate_an_admin_context(): void
    {
        $customer = $this->user(UserTypeEnum::Customer, 'customer@example.test');

        $this->withHeader('Origin', 'http://localhost:3000')
            ->postJson('/api/v1/admin/auth/login', [
                'email' => $customer->email,
                'password' => 'secure-password',
            ])
            ->assertForbidden();
    }

    public function test_staff_credentials_cannot_authenticate_a_customer_context(): void
    {
        $staff = $this->user(UserTypeEnum::Staff, 'staff@example.test');

        $this->withHeader('Origin', 'http://localhost:4173')
            ->postJson('/api/v1/customer/auth/login', [
                'email' => $staff->email,
                'password' => 'secure-password',
            ])
            ->assertForbidden();
    }

    public function test_a_customer_context_cannot_access_an_admin_identity_endpoint(): void
    {
        $customer = $this->user(UserTypeEnum::Customer, 'customer@example.test');

        $this->actingAs($customer, 'customer')
            ->getJson('/api/v1/admin/auth/user')
            ->assertUnauthorized();
    }

    private function user(UserTypeEnum $type, string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => Hash::make('secure-password'),
            'user_type' => $type->value,
        ]);
    }
}
