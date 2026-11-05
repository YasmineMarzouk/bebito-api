<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Tests\TestCase;

class CreateCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_checkout_available_product_variants(): void
    {
        $product = Product::query()->create(['status' => 'published', 'sku' => 'BEB-000001', 'price_amount' => 12500, 'stock_quantity' => 5, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => 'BEB-000001-01', 'attributes' => ['size' => '0-3m', 'color' => 'Bleu'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => 5, 'low_stock_threshold' => 1]);

        $this->postJson('/api/v1/checkout', ['first_name' => 'Marie', 'last_name' => 'Dupont', 'email' => 'marie@example.test', 'phone' => '+21620000000', 'address' => '1 Rue des Fleurs', 'city' => 'Tunis', 'items' => [['variant_id' => $variant->id, 'quantity' => 2]]])
            ->assertCreated()
            ->assertJsonPath('data.totalAmount', 25000)
            ->assertJsonPath('data.currencyCode', 'TND');

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 3]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 3]);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_checkout_rejects_a_variant_with_insufficient_inventory(): void
    {
        $product = Product::query()->create(['status' => 'published', 'sku' => 'BEB-000002', 'price_amount' => 12500, 'stock_quantity' => 1, 'low_stock_threshold' => 1]);
        $variant = $product->variants()->create(['sku' => 'BEB-000002-01', 'attributes' => ['size' => '0-3m'], 'is_default' => true]);
        $variant->inventoryItem()->create(['quantity' => 1, 'low_stock_threshold' => 1]);

        $this->postJson('/api/v1/checkout', ['first_name' => 'Marie', 'last_name' => 'Dupont', 'email' => 'marie@example.test', 'phone' => '+21620000000', 'address' => '1 Rue des Fleurs', 'city' => 'Tunis', 'items' => [['variant_id' => $variant->id, 'quantity' => 2]]])
            ->assertUnprocessable();

        $this->assertDatabaseHas('inventory_items', ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->assertDatabaseCount('orders', 0);
    }
}
