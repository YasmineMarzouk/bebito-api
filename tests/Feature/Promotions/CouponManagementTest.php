<?php

namespace Tests\Feature\Promotions;

use App\Models\User;
use Database\Seeders\StaffAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Role;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Promotions\Models\Coupon;
use Modules\Promotions\Enums\CouponTypeEnum;
use Tests\TestCase;

class CouponManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_coupon(): void
    {
        $this->actingAs($this->staff(), 'admin')
            ->postJson('/api/v1/admin/coupons', [
                'code' => 'welcome10',
                'type' => CouponTypeEnum::Percentage->value,
                'percentage' => 10,
                'min_order_amount' => 5000,
                'usage_limit' => 100,
                'expires_at' => now()->addMonth()->format('Y-m-d'),
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'WELCOME10')
            ->assertJsonPath('data.type', CouponTypeEnum::Percentage->value)
            ->assertJsonPath('data.percentage', 10);

        $this->assertDatabaseHas('coupons', ['code' => 'WELCOME10', 'min_order_amount' => 5000]);
    }

    public function test_coupon_creation_validates_the_discount_value(): void
    {
        $this->actingAs($this->staff(), 'admin')
            ->postJson('/api/v1/admin/coupons', [
                'code' => 'INVALID',
                'type' => CouponTypeEnum::Percentage->value,
                'is_active' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['percentage']);
    }

    public function test_an_administrator_can_apply_a_coupon_to_an_existing_order(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'SAVE20',
            'type' => CouponTypeEnum::Percentage->value,
            'percentage' => 20,
            'min_order_amount' => 0,
            'usage_count' => 0,
            'is_active' => true,
        ]);
        $order = Order::query()->create([
            'number' => 'BEB-1000',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.test',
            'total_amount' => 10000,
        ]);
        $order->payments()->create([
            'method' => 'cash_on_delivery',
            'status' => OrderStatusEnum::PENDING->value,
            'amount' => 10000,
            'currency_code' => 'TND',
        ]);

        $this->actingAs($this->staff(), 'admin')
            ->putJson("/api/v1/admin/orders/{$order->id}/coupon", ['coupon_id' => $coupon->id])
            ->assertOk()
            ->assertJsonPath('data.couponCode', 'SAVE20')
            ->assertJsonPath('data.couponDiscountAmount', 2000)
            ->assertJsonPath('data.totalAmount', 8000);

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'usage_count' => 1]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount' => 8000]);
    }

    public function test_an_administrator_cannot_change_a_coupon_after_an_order_has_left_the_pending_state(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'DELIVERED20',
            'type' => CouponTypeEnum::Percentage->value,
            'percentage' => 20,
            'min_order_amount' => 0,
            'usage_count' => 0,
            'is_active' => true,
        ]);
        $order = Order::query()->create([
            'number' => 'BEB-DELIVERED-1000',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.test',
            'status' => OrderStatusEnum::DELIVERED->value,
            'total_amount' => 10000,
        ]);
        $order->payments()->create([
            'method' => 'cash_on_delivery',
            'status' => OrderStatusEnum::PENDING->value,
            'amount' => 10000,
            'currency_code' => 'TND',
        ]);

        $this->actingAs($this->staff(), 'admin')
            ->putJson("/api/v1/admin/orders/{$order->id}/coupon", ['coupon_id' => $coupon->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code', 4203);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'coupon_id' => null,
            'coupon_discount_amount' => 0,
            'total_amount' => 10000,
        ]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount' => 10000]);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'usage_count' => 0]);
    }

    public function test_a_customer_cannot_manage_coupons(): void
    {
        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);

        $this->actingAs($customer, 'customer')
            ->postJson('/api/v1/admin/coupons', [
                'code' => 'FORBIDDEN',
                'type' => CouponTypeEnum::Percentage->value,
                'percentage' => 10,
            ])
            ->assertUnauthorized();

        $this->assertDatabaseCount('coupons', 0);
    }

    public function test_staff_without_coupon_write_permission_cannot_create_a_coupon(): void
    {
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);

        $this->actingAs($staff, 'admin')
            ->postJson('/api/v1/admin/coupons', [
                'code' => 'FORBIDDEN',
                'type' => CouponTypeEnum::Percentage->value,
                'percentage' => 10,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('coupons', 0);
    }

    private function staff(): User
    {
        $owner = Role::query()->create(['name' => 'owner', 'title' => 'Owner']);
        $this->seed(StaffAccessSeeder::class);
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);
        $staff->roles()->attach($owner);

        return $staff;
    }
}
