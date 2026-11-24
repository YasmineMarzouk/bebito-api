<?php

namespace Tests\Feature\Core;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\StaffAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Role;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-13 12:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_authorized_staff_receives_real_non_cancelled_order_analytics(): void
    {
        $this->order(OrderStatusEnum::READY, 30000, '2026-03-10 10:00:00', 'Cloud romper', 3, 10000);
        $this->order(OrderStatusEnum::PENDING, 10000, '2026-11-10 10:00:00', 'Bunny bodysuit', 2, 5000);
        $this->order(OrderStatusEnum::CANCELLED, 99000, '2026-11-11 10:00:00', 'Cancelled romper', 20, 4950);
        $this->order(OrderStatusEnum::DELIVERED, 20000, '2025-03-10 10:00:00', 'Last year romper', 1, 20000);

        $this->actingAs($this->ownerStaff(), 'admin')
            ->getJson('/api/v1/admin/analytics')
            ->assertOk()
            ->assertJsonPath('data.revenueAmount', 40000)
            ->assertJsonPath('data.orderCount', 2)
            ->assertJsonPath('data.revenueChange', 100)
            ->assertJsonPath('data.orderCountChange', 100)
            ->assertJsonPath('data.monthly.6.month', 'Nov')
            ->assertJsonPath('data.monthly.6.revenueAmount', 10000)
            ->assertJsonPath('data.monthly.6.orders', 1)
            ->assertJsonPath('data.popularProducts.0.name', 'Cloud romper')
            ->assertJsonPath('data.popularProducts.0.soldQuantity', 3)
            ->assertJsonPath('data.popularProducts.0.unitPriceAmount', 10000);
    }

    public function test_authorized_staff_receives_real_dashboard_overview_statistics(): void
    {
        $this->order(OrderStatusEnum::READY, 15000, '2026-11-13 09:00:00', 'Cloud romper', 2, 7500);
        $this->order(OrderStatusEnum::PENDING, 11000, '2026-11-05 09:00:00', 'Bunny bodysuit', 1, 11000);
        $this->order(OrderStatusEnum::CANCELLED, 90000, '2026-11-12 09:00:00', 'Cancelled romper', 9, 10000);
        User::factory()->create([
            'user_type' => UserTypeEnum::Customer->value,
            'created_at' => '2026-11-10 09:00:00',
            'updated_at' => '2026-11-10 09:00:00',
        ]);

        $this->actingAs($this->ownerStaff(), 'admin')
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.revenue30DaysAmount', 26000)
            ->assertJsonPath('data.ordersToday', 1)
            ->assertJsonPath('data.ordersThisMonth', 2)
            ->assertJsonPath('data.newCustomers', 1)
            ->assertJsonPath('data.recentOrders.0.number', 'BB-000003')
            ->assertJsonPath('data.lowStockProducts', []);
    }

    private function order(OrderStatusEnum $status, int $totalAmount, string $createdAt, string $productName, int $quantity, int $unitPriceAmount): Order
    {
        $order = Order::query()->create([
            'number' => 'BB-'.str_pad((string) (Order::query()->count() + 1), 6, '0', STR_PAD_LEFT),
            'customer_name' => 'Analytics customer',
            'customer_email' => 'analytics@example.test',
            'status' => $status,
            'subtotal_amount' => $totalAmount,
            'shipping_amount' => 0,
            'total_amount' => $totalAmount,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        $order->items()->create([
            'product_name' => $productName,
            'sku' => 'ANALYTICS-'.Order::query()->count(),
            'quantity' => $quantity,
            'unit_price_amount' => $unitPriceAmount,
            'total_amount' => $quantity * $unitPriceAmount,
        ]);

        return $order;
    }

    private function ownerStaff(): User
    {
        $owner = Role::query()->create(['name' => 'owner', 'title' => 'Owner']);
        $this->seed(StaffAccessSeeder::class);
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);
        $staff->roles()->attach($owner);

        return $staff;
    }
}
