<?php

namespace Tests\Feature\Promotions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Orders\Models\Order;
use Modules\Promotions\Models\Coupon;
use Tests\TestCase;

class CouponManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_coupon(): void
    {
        $this->withoutMiddleware()
            ->postJson('/api/v1/coupons', [
                'code' => 'welcome10',
                'type' => 'percentage',
                'percentage' => 10,
                'min_order_amount' => 5000,
                'usage_limit' => 100,
                'expires_at' => now()->addMonth()->format('Y-m-d'),
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'WELCOME10')
            ->assertJsonPath('data.percentage', 10);

        $this->assertDatabaseHas('coupons', ['code' => 'WELCOME10', 'min_order_amount' => 5000]);
    }

    public function test_coupon_creation_validates_the_discount_value(): void
    {
        $this->withoutMiddleware()
            ->postJson('/api/v1/coupons', [
                'code' => 'INVALID',
                'type' => 'percentage',
                'is_active' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['percentage']);
    }

    public function test_an_administrator_can_apply_a_coupon_to_an_existing_order(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'SAVE20',
            'type' => 'percentage',
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

        $this->withoutMiddleware()
            ->putJson("/api/v1/orders/{$order->id}/coupon", ['coupon_id' => $coupon->id])
            ->assertOk()
            ->assertJsonPath('data.couponCode', 'SAVE20')
            ->assertJsonPath('data.couponDiscountAmount', 2000)
            ->assertJsonPath('data.totalAmount', 8000);

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'usage_count' => 1]);
    }
}
