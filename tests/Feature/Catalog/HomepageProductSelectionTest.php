<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Tests\TestCase;

class HomepageProductSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_administrator_can_select_up_to_four_homepage_products(): void
    {
        $products = collect(range(1, 5))->map(fn (): Product => $this->product());
        $selectedProductIds = $products->take(4)->pluck('id')->all();

        $this->actingAs(User::factory()->create())
            ->putJson('/api/v1/products/homepage-selection', ['product_ids' => $selectedProductIds])
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.id', $selectedProductIds[0]);

        $this->assertDatabaseHas('products', [
            'id' => $selectedProductIds[0],
            'is_homepage_featured' => true,
            'homepage_position' => 1,
        ]);

        $this->getJson('/api/v1/homepage/products')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.id', $selectedProductIds[0]);
    }

    public function test_homepage_product_selection_rejects_more_than_four_products(): void
    {
        $productIds = collect(range(1, 5))->map(fn (): int => $this->product()->id)->all();

        $this->actingAs(User::factory()->create())
            ->putJson('/api/v1/products/homepage-selection', ['product_ids' => $productIds])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_ids']);
    }

    public function test_homepage_product_selection_rejects_draft_products(): void
    {
        $draftProduct = $this->product('draft');

        $this->actingAs(User::factory()->create())
            ->putJson('/api/v1/products/homepage-selection', ['product_ids' => [$draftProduct->id]])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Only published products can be displayed in Bestsellers.');
    }

    private function product(string $status = 'published'): Product
    {
        return Product::query()->create([
            'status' => $status,
            'price_amount' => 1000,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
        ]);
    }
}
