<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Country;
use Modules\Core\Models\Currency;
use Modules\Customer\Models\CustomerAddress;
use Modules\Customer\Models\CustomerProfile;
use Modules\Inventory\Enums\InventoryMovementReasonEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Enums\PaymentStatusEnum;
use Modules\Promotions\Models\Coupon;
use Modules\Promotions\Enums\CouponTypeEnum;
use Tests\TestCase;

class CreateCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::query()->create(['code' => 'TND', 'name' => 'Tunisian dinar', 'symbol' => 'TND', 'decimal_places' => 3, 'is_active' => true]);
        Country::query()->create(['code' => 'TN', 'name' => 'Tunisia', 'is_active' => true]);
    }

    public function test_a_guest_can_checkout_available_product_variants(): void
    {
        $product = Product::query()->create(['status' => ProductStatusEnum::Published->value, 'sku' => 'BEB-000001', 'price_amount' => 12500, 'stock_quantity' => 5, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => 'BEB-000001-01', 'attributes' => ['size' => '0-3m', 'color' => 'Bleu'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => 5, 'low_stock_threshold' => 1]);

        $this->postCheckout(['first_name' => 'Marie', 'last_name' => 'Dupont', 'email' => 'marie@example.test', 'phone' => '+21620000000', 'address' => '1 Rue des Fleurs', 'city' => 'Tunis', 'postal_code' => '1000', 'items' => [['variant_id' => $variant->id, 'quantity' => 2]]])
            ->assertCreated()
            ->assertJsonPath('data.totalAmount', 25000)
            ->assertJsonPath('data.currencyCode', 'TND');

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 3]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 3]);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('order_addresses', ['postal_code' => '1000']);
    }

    public function test_checkout_rejects_a_variant_with_insufficient_inventory(): void
    {
        $product = Product::query()->create(['status' => ProductStatusEnum::Published->value, 'sku' => 'BEB-000002', 'price_amount' => 12500, 'stock_quantity' => 1, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => 'BEB-000002-01', 'attributes' => ['size' => '0-3m'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => 1, 'low_stock_threshold' => 1]);

        $this->postCheckout(['first_name' => 'Marie', 'last_name' => 'Dupont', 'email' => 'marie@example.test', 'phone' => '+21620000000', 'address' => '1 Rue des Fleurs', 'city' => 'Tunis', 'items' => [['variant_id' => $variant->id, 'quantity' => 2]]])
            ->assertUnprocessable();

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_redeems_a_coupon_and_records_an_inventory_movement(): void
    {
        $product = Product::query()->create(['status' => ProductStatusEnum::Published->value, 'sku' => 'BEB-000003', 'price_amount' => 12500, 'stock_quantity' => 5, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => 'BEB-000003-01', 'attributes' => ['size' => '3-6m'], 'is_default' => true]);
        $inventory = $variant->inventoryItem()->create(['quantity' => 5, 'low_stock_threshold' => 1]);
        $coupon = Coupon::query()->create(['code' => 'WELCOME10', 'type' => CouponTypeEnum::Percentage->value, 'percentage' => 10, 'min_order_amount' => 0, 'usage_limit' => 1, 'usage_count' => 0, 'is_active' => true]);

        $this->postCheckout($this->checkoutPayload($variant, 2, 'welcome10'))
            ->assertCreated()
            ->assertJsonPath('data.couponCode', 'WELCOME10')
            ->assertJsonPath('data.couponDiscountAmount', 2500)
            ->assertJsonPath('data.totalAmount', 22500);

        $order = Order::query()->sole();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'coupon_id' => $coupon->id, 'coupon_code' => 'WELCOME10', 'coupon_discount_amount' => 2500, 'total_amount' => 22500]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount' => 22500]);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'usage_count' => 1]);
        $this->assertDatabaseHas('inventory_movements', ['inventory_item_id' => $inventory->id, 'quantity_change' => -2, 'reason' => InventoryMovementReasonEnum::OrderPlaced->value, 'reference_type' => Order::class, 'reference_id' => $order->id]);
    }

    public function test_checkout_quote_returns_the_server_calculated_discount_without_reserving_stock(): void
    {
        $product = Product::query()->create(['status' => ProductStatusEnum::Published->value, 'sku' => 'BEB-000006', 'price_amount' => 12500, 'stock_quantity' => 5, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => 'BEB-000006-01', 'attributes' => ['size' => '3-6m'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => 5, 'low_stock_threshold' => 1]);
        $coupon = Coupon::query()->create(['code' => 'QUOTE10', 'type' => CouponTypeEnum::Percentage->value, 'percentage' => 10, 'min_order_amount' => 0, 'usage_count' => 0, 'is_active' => true]);

        $this->postJson('/api/v1/store/checkout/quote', [
            'coupon_code' => strtolower($coupon->code),
            'items' => [['variant_id' => $variant->id, 'quantity' => 2]],
        ])
            ->assertOk()
            ->assertJsonPath('data.couponCode', 'QUOTE10')
            ->assertJsonPath('data.couponDiscountAmount', 2500)
            ->assertJsonPath('data.totalAmount', 22500);

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 5]);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'usage_count' => 0]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_quote_returns_a_safe_error_for_an_expired_or_unknown_coupon(): void
    {
        $product = Product::query()->create(['status' => ProductStatusEnum::Published->value, 'sku' => 'BEB-000007', 'price_amount' => 12500, 'stock_quantity' => 5, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => 'BEB-000007-01', 'attributes' => ['size' => '6-12m'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => 5, 'low_stock_threshold' => 1]);
        Coupon::query()->create(['code' => 'EXPIRED', 'type' => CouponTypeEnum::Percentage->value, 'percentage' => 10, 'min_order_amount' => 0, 'usage_count' => 0, 'expires_at' => now()->subDay()->toDateString(), 'is_active' => true]);

        foreach (['EXPIRED', 'UNKNOWN'] as $couponCode) {
            $this->postJson('/api/v1/store/checkout/quote', [
                'coupon_code' => $couponCode,
                'items' => [['variant_id' => $variant->id, 'quantity' => 1]],
            ])
                ->assertUnprocessable()
                ->assertJsonPath('message', 'This coupon cannot be applied to the order.');
        }

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 5]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rolls_back_stock_and_order_when_a_coupon_has_reached_its_usage_limit(): void
    {
        $product = Product::query()->create(['status' => ProductStatusEnum::Published->value, 'sku' => 'BEB-000004', 'price_amount' => 12500, 'stock_quantity' => 5, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => 'BEB-000004-01', 'attributes' => ['size' => '6-12m'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => 5, 'low_stock_threshold' => 1]);
        $coupon = Coupon::query()->create(['code' => 'ONEUSE', 'type' => CouponTypeEnum::Percentage->value, 'percentage' => 10, 'min_order_amount' => 0, 'usage_limit' => 1, 'usage_count' => 1, 'is_active' => true]);

        $this->postCheckout($this->checkoutPayload($variant, 1, $coupon->code))
            ->assertUnprocessable();

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 5]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 5]);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_checkout_rejects_duplicate_variant_lines_before_reserving_stock(): void
    {
        $product = Product::query()->create(['status' => ProductStatusEnum::Published->value, 'sku' => 'BEB-000005', 'price_amount' => 12500, 'stock_quantity' => 5, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => 'BEB-000005-01', 'attributes' => ['size' => '12-24m'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => 5, 'low_stock_threshold' => 1]);

        $payload = $this->checkoutPayload($variant, 1);
        $payload['items'][] = ['variant_id' => $variant->id, 'quantity' => 1];

        $this->postCheckout($payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.variant_id', 'items.1.variant_id']);

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 5]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_ignores_client_supplied_price_fields_and_uses_the_authoritative_catalog_price(): void
    {
        $product = Product::query()->create(['status' => ProductStatusEnum::Published->value, 'sku' => 'BEB-000008', 'price_amount' => 12500, 'stock_quantity' => 2, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => 'BEB-000008-01', 'attributes' => ['size' => '0-3m'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => 2, 'low_stock_threshold' => 1]);
        $payload = $this->checkoutPayload($variant, 1);
        $payload['items'][0]['price_amount'] = 1;
        $payload['items'][0]['unit_price_amount'] = 1;
        $payload['items'][0]['stock_quantity'] = 999;

        $this->postCheckout($payload)
            ->assertCreated()
            ->assertJsonPath('data.totalAmount', 12500);

        $this->assertDatabaseHas('order_items', [
            'product_variant_id' => $variant->id,
            'unit_price_amount' => 12500,
            'total_amount' => 12500,
        ]);
    }

    public function test_checkout_requires_an_idempotency_key_before_it_can_mutate_inventory(): void
    {
        $variant = $this->sellableVariant('BEB-000009', 1, 12500);

        $this->postJson('/api/v1/store/checkout', $this->checkoutPayload($variant, 1))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['idempotency_key']);

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_retry_with_the_same_key_is_idempotent_and_rejects_different_details(): void
    {
        $variant = $this->sellableVariant('BEB-000010', 3, 12500);
        $payload = $this->checkoutPayload($variant, 1);
        $idempotencyKey = '4b6ec3de-530e-48a4-8e6f-2ab32b7075b7';

        $firstOrderId = $this->postCheckout($payload, $idempotencyKey)
            ->assertCreated()
            ->json('data.id');

        $this->postCheckout($payload, $idempotencyKey)
            ->assertCreated()
            ->assertJsonPath('data.id', $firstOrderId);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 2]);

        $changedPayload = [...$payload, 'city' => 'Sfax'];

        $this->postCheckout($changedPayload, $idempotencyKey)
            ->assertUnprocessable()
            ->assertJsonPath('errors.code', 4201);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 2]);
    }

    public function test_checkout_uses_the_authenticated_customer_and_ignores_client_payment_and_currency_fields(): void
    {
        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        $otherCustomer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        $variant = $this->sellableVariant('BEB-000011', 1, 12500);
        $payload = $this->checkoutPayload($variant, 1);
        $payload = [...$payload, 'user_id' => $otherCustomer->id, 'currency_code' => 'USD', 'payment_method_code' => 'card', 'payment_status' => 'paid', 'paid_at' => now()->toISOString()];

        $this->actingAs($customer, 'customer')
            ->postCheckout($payload)
            ->assertCreated()
            ->assertJsonPath('data.currencyCode', 'TND');

        $order = Order::query()->sole();

        $this->assertSame($customer->id, $order->user_id);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'method' => 'cash_on_delivery', 'status' => PaymentStatusEnum::Pending->value, 'currency_code' => 'TND']);
    }

    public function test_checkout_uses_submitted_contact_details_without_updating_the_customer_profile(): void
    {
        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        CustomerProfile::query()->create(['user_id' => $customer->id, 'phone' => '+216 20 000 000']);
        CustomerAddress::query()->create([
            'user_id' => $customer->id,
            'recipient_name' => $customer->name,
            'phone' => '+216 20 000 000',
            'line_one' => '1 Rue des Fleurs',
            'city' => 'Tunis',
            'postal_code' => '1000',
            'country_code' => 'TN',
            'is_default' => true,
        ]);
        $variant = $this->sellableVariant('BEB-000014', 1, 12500);
        $payload = $this->checkoutPayload($variant, 1);
        $payload = [
            ...$payload,
            'phone' => '+216 21 111 111',
            'address' => '2 Rue des Jasmins',
            'city' => 'Sfax',
            'postal_code' => '3000',
        ];

        $this->actingAs($customer, 'customer')->postCheckout($payload)->assertCreated();

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $customer->id,
            'phone' => '+216 20 000 000',
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'user_id' => $customer->id,
            'line_one' => '1 Rue des Fleurs',
            'city' => 'Tunis',
            'postal_code' => '1000',
        ]);
        $this->assertDatabaseHas('order_addresses', [
            'phone' => '+216 21 111 111',
            'line_one' => '2 Rue des Jasmins',
            'city' => 'Sfax',
            'postal_code' => '3000',
        ]);
    }

    public function test_checkout_rolls_back_when_tunisia_is_inactive(): void
    {
        $variant = $this->sellableVariant('BEB-000012', 1, 12500);
        Country::query()->whereKey('TN')->update(['is_active' => false]);

        $this->postCheckout($this->checkoutPayload($variant, 1))
            ->assertStatus(503)
            ->assertJsonPath('errors.code', 4202);

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_a_cart_with_more_than_twenty_five_lines_without_writes(): void
    {
        $variant = $this->sellableVariant('BEB-000013', 30, 12500);
        $payload = $this->checkoutPayload($variant, 1);
        $payload['items'] = array_fill(0, 26, ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->postCheckout($payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 30]);
        $this->assertDatabaseCount('orders', 0);
    }

    /** @return array<string, mixed> */
    private function checkoutPayload(ProductVariant $variant, int $quantity, ?string $couponCode = null): array
    {
        return [
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie@example.test',
            'phone' => '+21620000000',
            'address' => '1 Rue des Fleurs',
            'city' => 'Tunis',
            'postal_code' => '1000',
            'coupon_code' => $couponCode,
            'items' => [['variant_id' => $variant->id, 'quantity' => $quantity]],
        ];
    }

    private function postCheckout(array $payload, string $idempotencyKey = 'd6f1a6db-1e23-41d7-8c34-5f0b6a8ccfcc'): TestResponse
    {
        return $this->postJson('/api/v1/store/checkout', $payload, ['Idempotency-Key' => $idempotencyKey]);
    }

    private function sellableVariant(string $sku, int $quantity, int $priceAmount): ProductVariant
    {
        $product = Product::query()->create(['status' => ProductStatusEnum::Published->value, 'sku' => $sku, 'price_amount' => $priceAmount, 'stock_quantity' => $quantity, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => $sku.'-01', 'attributes' => ['size' => '0-3m'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => $quantity, 'low_stock_threshold' => 1]);

        return $variant;
    }
}
