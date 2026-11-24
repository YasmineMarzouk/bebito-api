<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use Database\Seeders\StaffAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Models\Bestseller;
use Modules\Catalog\Models\Product;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Role;
use Tests\TestCase;

class BestsellersTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_replace_a_bestseller_at_a_position_and_reuse_a_product_at_another_position(): void
    {
        $oldProduct = $this->product();
        $replacementProduct = $this->product();
        $oldBestseller = Bestseller::query()->create(['product_id' => $oldProduct->id, 'position' => 2]);
        Bestseller::query()->create(['product_id' => $replacementProduct->id, 'position' => 3]);

        $this->actingAs($this->ownerStaff(), 'admin')
            ->putJson('/api/v1/admin/homepage/bestsellers', [
                'product_id' => $replacementProduct->id,
                'position' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Bestseller updated.')
            ->assertJsonPath('data.position', 2)
            ->assertJsonPath('data.product.id', $replacementProduct->id);

        $this->assertDatabaseMissing('bestsellers', ['id' => $oldBestseller->id]);
        $this->assertDatabaseHas('bestsellers', ['product_id' => $replacementProduct->id, 'position' => 2]);
        $this->assertDatabaseHas('bestsellers', ['product_id' => $replacementProduct->id, 'position' => 3]);
    }

    public function test_bestseller_update_validates_the_product_and_position_without_modifying_slots(): void
    {
        $product = $this->product();
        Bestseller::query()->create(['product_id' => $product->id, 'position' => 1]);

        $this->actingAs($this->ownerStaff(), 'admin')
            ->putJson('/api/v1/admin/homepage/bestsellers', ['product_id' => 999_999, 'position' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id', 'position']);

        $this->assertDatabaseCount('bestsellers', 1);
        $this->assertDatabaseHas('bestsellers', ['product_id' => $product->id, 'position' => 1]);
    }

    public function test_staff_can_remove_a_bestseller_from_a_position(): void
    {
        $product = $this->product();
        Bestseller::query()->create(['product_id' => $product->id, 'position' => 4]);

        $this->actingAs($this->ownerStaff(), 'admin')
            ->deleteJson('/api/v1/admin/homepage/bestsellers/4')
            ->assertOk()
            ->assertJsonPath('message', 'Bestseller removed.');

        $this->assertDatabaseMissing('bestsellers', ['product_id' => $product->id, 'position' => 4]);
    }

    public function test_unauthenticated_users_cannot_manage_bestsellers(): void
    {
        $this->getJson('/api/v1/admin/homepage/bestsellers')->assertUnauthorized();
        $this->putJson('/api/v1/admin/homepage/bestsellers', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/admin/homepage/bestsellers/1')->assertUnauthorized();
    }

    private function product(): Product
    {
        return Product::query()->create([
            'status' => ProductStatusEnum::Published->value,
            'price_amount' => 10_000,
            'stock_quantity' => 0,
            'low_stock_threshold' => 0,
        ]);
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
