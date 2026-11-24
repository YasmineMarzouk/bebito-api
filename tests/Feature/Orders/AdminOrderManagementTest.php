<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use Database\Seeders\StaffAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Country;
use Modules\Core\Models\Currency;
use Modules\Core\Models\Role;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Enums\PaymentStatusEnum;
use Modules\Inventory\Enums\InventoryMovementReasonEnum;
use Modules\Orders\Listeners\SendOrderManagementEmail;
use Modules\Orders\Models\Order;
use Modules\Promotions\Models\Coupon;
use Modules\Promotions\Enums\CouponTypeEnum;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::query()->create(['code' => 'TND', 'name' => 'Tunisian dinar', 'symbol' => 'TND', 'decimal_places' => 3, 'is_active' => true]);
        Country::query()->create(['code' => 'TN', 'name' => 'Tunisia', 'is_active' => true]);
    }

    public function test_an_authorized_administrator_can_replace_order_lines_and_reconcile_stock_coupon_and_cod_amount(): void
    {
        $staff = $this->ownerStaff();
        [$order, $firstVariant] = $this->checkoutOrder('BEB-ADMIN-1', 2, 12000);
        $secondVariant = $this->sellableVariant('BEB-ADMIN-2', 5, 15000);
        $coupon = Coupon::query()->create(['code' => 'ADMIN10', 'type' => CouponTypeEnum::Percentage->value, 'percentage' => 10, 'min_order_amount' => 0, 'usage_count' => 0, 'is_active' => true]);

        $this->actingAs($staff, 'admin')->putJson("/api/v1/admin/orders/{$order->id}", [
            'items' => [
                ['variant_id' => $firstVariant->id, 'quantity' => 1],
                ['variant_id' => $secondVariant->id, 'quantity' => 2],
            ],
            'coupon_id' => $coupon->id,
        ])->assertOk()
            ->assertJsonPath('data.subtotalAmount', 42000)
            ->assertJsonPath('data.couponDiscountAmount', 4200)
            ->assertJsonPath('data.totalAmount', 37800)
            ->assertJsonPath('data.payment.amount', 37800);

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $firstVariant->id, 'quantity' => 4]);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $secondVariant->id, 'quantity' => 3]);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'usage_count' => 1]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount' => 37800, 'status' => PaymentStatusEnum::Pending->value]);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseHas('inventory_movements', ['reference_id' => $order->id, 'quantity_change' => 1, 'reason' => InventoryMovementReasonEnum::ManualAdjustment->value]);
        $this->assertDatabaseHas('inventory_movements', ['reference_id' => $order->id, 'quantity_change' => -2, 'reason' => InventoryMovementReasonEnum::ManualAdjustment->value]);
    }

    public function test_cancellation_restores_reserved_inventory_releases_the_coupon_and_stops_cod_collection(): void
    {
        $staff = $this->ownerStaff();
        [$order, $variant] = $this->checkoutOrder('BEB-ADMIN-3', 2, 12000, 'CANCEL10');
        $coupon = Coupon::query()->where('code', 'CANCEL10')->sole();

        $this->actingAs($staff, 'admin')->postJson("/api/v1/admin/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatusEnum::CANCELLED->value)
            ->assertJsonPath('data.payment.status', 'cancelled');

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 5]);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'usage_count' => 0]);
        $this->assertDatabaseHas('inventory_movements', ['reference_id' => $order->id, 'quantity_change' => 2, 'reason' => InventoryMovementReasonEnum::ManualAdjustment->value]);

        $this->actingAs($staff, 'admin')->postJson("/api/v1/admin/orders/{$order->id}/cancel")->assertUnprocessable();
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 5]);
    }

    public function test_preparing_an_order_is_silent_and_marking_it_ready_queues_the_confirmation_email(): void
    {
        Queue::fake();
        $staff = $this->ownerStaff();
        [$order] = $this->checkoutOrder('BEB-ADMIN-4', 1, 12000);

        $this->actingAs($staff, 'admin')->postJson("/api/v1/admin/orders/{$order->id}/preparing")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatusEnum::PROCESSING->value);
        Queue::assertNothingPushed();

        Queue::fake();
        $this->actingAs($staff, 'admin')->postJson("/api/v1/admin/orders/{$order->id}/ready")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatusEnum::READY->value);
        Queue::assertPushed(SendOrderManagementEmail::class);
    }

    public function test_a_ready_order_can_be_cancelled(): void
    {
        $staff = $this->ownerStaff();
        [$order] = $this->checkoutOrder('BEB-ADMIN-READY-CANCEL', 1, 12000);

        $this->actingAs($staff, 'admin')->postJson("/api/v1/admin/orders/{$order->id}/preparing")->assertOk();
        $this->actingAs($staff, 'admin')->postJson("/api/v1/admin/orders/{$order->id}/ready")->assertOk();
        $this->actingAs($staff, 'admin')->postJson("/api/v1/admin/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatusEnum::CANCELLED->value);
    }

    public function test_order_details_recover_legacy_zero_amount_snapshots_from_saved_order_items(): void
    {
        $staff = $this->ownerStaff();
        [$order] = $this->checkoutOrder('BEB-ADMIN-LEGACY-AMOUNTS', 1, 12000);

        Order::query()->whereKey($order->id)->update([
            'subtotal_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 13000,
        ]);

        $this->actingAs($staff, 'admin')->getJson("/api/v1/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.subtotalAmount', 12000)
            ->assertJsonPath('data.shippingAmount', 1000)
            ->assertJsonPath('data.totalAmount', 13000);
    }

    public function test_order_details_fall_back_to_linked_variant_attributes_when_legacy_items_have_no_snapshot(): void
    {
        $staff = $this->ownerStaff();
        [$order, $variant] = $this->checkoutOrder('BEB-ADMIN-LEGACY-VARIANT', 1, 12000);

        $variant->update(['attributes' => ['size' => '3-6m', 'color' => 'Sage']]);
        $order->items()->update(['variant_attributes' => null]);

        $this->actingAs($staff, 'admin')->getJson("/api/v1/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.items.0.variantAttributes.size', '3-6m')
            ->assertJsonPath('data.items.0.variantAttributes.color', 'Sage');
    }

    public function test_an_order_cannot_be_edited_after_confirmation(): void
    {
        $staff = $this->ownerStaff();
        [$order, $variant] = $this->checkoutOrder('BEB-ADMIN-5', 1, 12000);
        Order::query()->whereKey($order->id)->update(['status' => OrderStatusEnum::PROCESSING->value]);

        $this->actingAs($staff, 'admin')->putJson("/api/v1/admin/orders/{$order->id}", ['items' => [['variant_id' => $variant->id, 'quantity' => 2]], 'coupon_id' => null])
            ->assertUnprocessable();
    }

    public function test_an_invalid_replacement_does_not_partially_consume_or_restore_stock(): void
    {
        $staff = $this->ownerStaff();
        [$order, $variant] = $this->checkoutOrder('BEB-ADMIN-6', 1, 12000);

        $this->actingAs($staff, 'admin')->putJson("/api/v1/admin/orders/{$order->id}", [
            'items' => [['variant_id' => $variant->id, 'quantity' => 6]],
            'coupon_id' => null,
        ])->assertUnprocessable();

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 4]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->assertDatabaseCount('inventory_movements', 1);

        $this->actingAs($staff, 'admin')->putJson("/api/v1/admin/orders/{$order->id}", ['items' => [], 'coupon_id' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    /** @return array{0: Order, 1: ProductVariant} */
    private function checkoutOrder(string $sku, int $quantity, int $priceAmount, ?string $couponCode = null): array
    {
        $variant = $this->sellableVariant($sku, 5, $priceAmount);
        if ($couponCode !== null) {
            Coupon::query()->create(['code' => $couponCode, 'type' => CouponTypeEnum::Percentage->value, 'percentage' => 10, 'min_order_amount' => 0, 'usage_count' => 0, 'is_active' => true]);
        }

        $this->postJson('/api/v1/store/checkout', [
            'first_name' => 'Marie', 'last_name' => 'Dupont', 'email' => 'marie@example.test', 'phone' => '+21620000000', 'address' => '1 Rue', 'city' => 'Tunis',
            'coupon_code' => $couponCode, 'items' => [['variant_id' => $variant->id, 'quantity' => $quantity]],
        ], ['Idempotency-Key' => (string) str()->uuid()])->assertCreated();

        return [Order::query()->latest('id')->firstOrFail(), $variant];
    }

    private function sellableVariant(string $sku, int $quantity, int $priceAmount): ProductVariant
    {
        $product = Product::query()->create(['status' => ProductStatusEnum::Published->value, 'sku' => $sku, 'price_amount' => $priceAmount, 'stock_quantity' => $quantity, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => $sku.'-01', 'attributes' => ['size' => '0-3m'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => $quantity, 'low_stock_threshold' => 1]);

        return $variant;
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
