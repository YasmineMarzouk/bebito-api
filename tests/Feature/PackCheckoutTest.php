<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Country;
use Modules\Core\Models\Currency;
use Modules\Orders\Models\Order;
use Tests\Concerns\CreatesPackFixtures;
use Tests\TestCase;

class PackCheckoutTest extends TestCase
{
    use CreatesPackFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::query()->create([
            'code' => 'TND',
            'name' => 'Tunisian dinar',
            'symbol' => 'TND',
            'decimal_places' => 3,
            'is_active' => true,
        ]);
        Country::query()->create(['code' => 'TN', 'name' => 'Tunisia', 'is_active' => true]);
    }

    public function test_checkout_deducts_all_pack_components_and_persists_immutable_snapshots(): void
    {
        $first = $this->createSellableVariant('CHK-PACK-FIRST', 5, 10000);
        $second = $this->createSellableVariant('CHK-PACK-SECOND', 6, 5000);
        $fixture = $this->createSellablePack('BPK-CHK', 20000, [
            ['variant' => $first, 'quantity' => 2],
            ['variant' => $second, 'quantity' => 3],
        ]);

        $this->postCheckout($this->packPayload($fixture['variant']->id, 2), 'e4c81f02-3169-4f85-9cee-4bd062cdd4f4')
            ->assertCreated()
            ->assertJsonPath('data.totalAmount', 40000);

        $order = Order::query()->sole();
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $first->id, 'quantity' => 1]);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $second->id, 'quantity' => 0]);
        $this->assertDatabaseHas('products', ['id' => $first->product_id, 'stock_quantity' => 1]);
        $this->assertDatabaseHas('products', ['id' => $second->product_id, 'stock_quantity' => 0]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'catalog_item_type' => 'pack',
            'pack_variant_id' => $fixture['variant']->id,
            'quantity' => 2,
            'unit_price_amount' => 20000,
            'total_amount' => 40000,
        ]);
        $this->assertDatabaseHas('order_item_pack_components', [
            'product_variant_id' => $first->id,
            'quantity_per_pack' => 2,
            'quantity_consumed' => 4,
        ]);
        $this->assertDatabaseHas('order_item_pack_components', [
            'product_variant_id' => $second->id,
            'quantity_per_pack' => 3,
            'quantity_consumed' => 6,
        ]);
    }

    public function test_unavailable_pack_checkout_rolls_back_all_component_inventory_changes(): void
    {
        $first = $this->createSellableVariant('CHK-ROLLBACK-FIRST', 1, 10000);
        $second = $this->createSellableVariant('CHK-ROLLBACK-SECOND', 5, 5000);
        $fixture = $this->createSellablePack('BPK-ROLLBACK', 20000, [
            ['variant' => $first, 'quantity' => 1],
            ['variant' => $second, 'quantity' => 1],
        ]);

        $this->postCheckout($this->packPayload($fixture['variant']->id, 2), 'b7eaad86-93ee-4136-ae9b-bdc0c8b9d45e')
            ->assertUnprocessable();

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $first->id, 'quantity' => 1]);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $second->id, 'quantity' => 5]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_mixed_product_and_pack_checkout_aggregates_shared_component_demand(): void
    {
        $first = $this->createSellableVariant('CHK-MIXED-FIRST', 5, 10000);
        $second = $this->createSellableVariant('CHK-MIXED-SECOND', 5, 5000);
        $fixture = $this->createSellablePack('BPK-MIXED', 20000, [
            ['variant' => $first, 'quantity' => 2],
            ['variant' => $second, 'quantity' => 1],
        ]);
        $payload = $this->packPayload($fixture['variant']->id, 1);
        $payload['items'][] = ['catalog_item_type' => 'product', 'variant_id' => $first->id, 'quantity' => 3];

        $this->postCheckout($payload, '73d0fbfd-d035-47d8-9e6e-c0409601bd38')->assertCreated();

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $first->id, 'quantity' => 0]);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $second->id, 'quantity' => 4]);
        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_pack_checkout_retry_with_same_idempotency_key_does_not_deduct_twice(): void
    {
        $first = $this->createSellableVariant('CHK-IDEMPOTENT-FIRST', 5, 10000);
        $second = $this->createSellableVariant('CHK-IDEMPOTENT-SECOND', 5, 5000);
        $fixture = $this->createSellablePack('BPK-IDEMPOTENT', 20000, [
            ['variant' => $first, 'quantity' => 2],
            ['variant' => $second, 'quantity' => 1],
        ]);
        $key = '3384a21d-42d8-4db1-b1b2-bbf5a2a4650d';
        $payload = $this->packPayload($fixture['variant']->id, 1);

        $firstOrderId = $this->postCheckout($payload, $key)->assertCreated()->json('data.id');
        $this->postCheckout($payload, $key)->assertCreated()->assertJsonPath('data.id', $firstOrderId);

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $first->id, 'quantity' => 3]);
        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $second->id, 'quantity' => 4]);
        $this->assertDatabaseCount('orders', 1);
    }

    /** @return array<string, mixed> */
    private function packPayload(int $packVariantId, int $quantity): array
    {
        return [
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie@example.test',
            'phone' => '+21620000000',
            'address' => '1 Rue des Fleurs',
            'city' => 'Tunis',
            'postal_code' => '1000',
            'items' => [[
                'catalog_item_type' => 'pack',
                'pack_variant_id' => $packVariantId,
                'quantity' => $quantity,
            ]],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function postCheckout(array $payload, string $idempotencyKey): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/store/checkout', $payload, ['Idempotency-Key' => $idempotencyKey]);
    }
}
