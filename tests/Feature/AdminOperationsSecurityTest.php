<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Core\Enums\UserTypeEnum;
use Tests\TestCase;

class AdminOperationsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_protected_admin_operation_rejects_an_unauthenticated_direct_request(): void
    {
        foreach ($this->protectedOperations() as [$method, $uri]) {
            $this->request($method, $uri)->assertUnauthorized();
        }
    }

    public function test_every_protected_admin_operation_rejects_staff_without_the_required_permission(): void
    {
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);

        $this->actingAs($staff, 'admin');

        foreach ($this->protectedOperations() as [$method, $uri]) {
            $this->request($method, $uri)->assertForbidden();
        }
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function protectedOperations(): array
    {
        return [
            ['GET', '/api/v1/admin/categories'], ['POST', '/api/v1/admin/categories'], ['GET', '/api/v1/admin/categories/1'], ['PUT', '/api/v1/admin/categories/1'], ['DELETE', '/api/v1/admin/categories/1'], ['POST', '/api/v1/admin/categories/1/banner'],
            ['GET', '/api/v1/admin/collections'], ['POST', '/api/v1/admin/collections'], ['GET', '/api/v1/admin/collections/1'], ['PUT', '/api/v1/admin/collections/1'], ['DELETE', '/api/v1/admin/collections/1'], ['POST', '/api/v1/admin/collections/1/banner'],
            ['GET', '/api/v1/admin/coupons'], ['POST', '/api/v1/admin/coupons'], ['GET', '/api/v1/admin/coupons/1'], ['PUT', '/api/v1/admin/coupons/1'], ['DELETE', '/api/v1/admin/coupons/1'],
            ['GET', '/api/v1/admin/customers'], ['POST', '/api/v1/admin/customers'], ['GET', '/api/v1/admin/customers/1'], ['PUT', '/api/v1/admin/customers/1'], ['DELETE', '/api/v1/admin/customers/1'], ['PUT', '/api/v1/admin/customers/1/block'],
            ['GET', '/api/v1/admin/orders'], ['GET', '/api/v1/admin/orders/1'], ['PUT', '/api/v1/admin/orders/1'], ['PUT', '/api/v1/admin/orders/1/coupon'], ['POST', '/api/v1/admin/orders/1/confirm'], ['POST', '/api/v1/admin/orders/1/preparing'], ['POST', '/api/v1/admin/orders/1/ready-for-delivery'], ['POST', '/api/v1/admin/orders/1/ready'], ['POST', '/api/v1/admin/orders/1/cancel'],
            ['GET', '/api/v1/admin/products'], ['POST', '/api/v1/admin/products'], ['GET', '/api/v1/admin/products/1'], ['PUT', '/api/v1/admin/products/1'], ['DELETE', '/api/v1/admin/products/1'], ['DELETE', '/api/v1/admin/products/1/images/1'], ['PUT', '/api/v1/admin/products/1/primary-image'],
            ['GET', '/api/v1/admin/analytics'], ['GET', '/api/v1/admin/dashboard'], ['GET', '/api/v1/admin/messages'], ['PATCH', '/api/v1/admin/messages/1/read'], ['PATCH', '/api/v1/admin/messages/1/archive'], ['DELETE', '/api/v1/admin/messages/1'], ['PUT', '/api/v1/admin/settings'], ['POST', '/api/v1/admin/settings/homepage-instagram-images'], ['POST', '/api/v1/admin/translations/french-to-english'],
        ];
    }

    private function request(string $method, string $uri): TestResponse
    {
        return $this->json($method, $uri);
    }
}
