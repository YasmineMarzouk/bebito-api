<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use Database\Seeders\StaffAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\MainCategory;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Role;
use Tests\TestCase;

class MainCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_replace_a_main_category_at_a_position_and_reuse_a_category_at_another_position(): void
    {
        $oldCategory = Category::query()->create(['is_active' => true]);
        $replacementCategory = Category::query()->create(['is_active' => true]);
        $oldMainCategory = MainCategory::query()->create(['category_id' => $oldCategory->id, 'position' => 2]);
        MainCategory::query()->create(['category_id' => $replacementCategory->id, 'position' => 3]);

        $this->actingAs($this->ownerStaff(), 'admin')
            ->putJson('/api/v1/admin/homepage/main-categories', [
                'category_id' => $replacementCategory->id,
                'position' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Main category updated.')
            ->assertJsonPath('data.position', 2)
            ->assertJsonPath('data.category.id', $replacementCategory->id);

        $this->assertDatabaseMissing('main_categories', ['id' => $oldMainCategory->id]);
        $this->assertDatabaseHas('main_categories', ['category_id' => $replacementCategory->id, 'position' => 2]);
        $this->assertDatabaseHas('main_categories', ['category_id' => $replacementCategory->id, 'position' => 3]);
    }

    public function test_main_category_update_validates_the_category_and_position_without_modifying_slots(): void
    {
        $category = Category::query()->create(['is_active' => true]);
        MainCategory::query()->create(['category_id' => $category->id, 'position' => 1]);

        $this->actingAs($this->ownerStaff(), 'admin')
            ->putJson('/api/v1/admin/homepage/main-categories', ['category_id' => 999_999, 'position' => 7])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'position']);

        $this->assertDatabaseCount('main_categories', 1);
        $this->assertDatabaseHas('main_categories', ['category_id' => $category->id, 'position' => 1]);
    }

    public function test_staff_can_remove_a_main_category_from_a_position(): void
    {
        $category = Category::query()->create(['is_active' => true]);
        MainCategory::query()->create(['category_id' => $category->id, 'position' => 4]);

        $this->actingAs($this->ownerStaff(), 'admin')
            ->deleteJson('/api/v1/admin/homepage/main-categories/4')
            ->assertOk()
            ->assertJsonPath('message', 'Main category removed.');

        $this->assertDatabaseMissing('main_categories', ['category_id' => $category->id, 'position' => 4]);
    }

    public function test_unauthenticated_users_cannot_manage_main_categories(): void
    {
        $this->getJson('/api/v1/admin/homepage/main-categories')->assertUnauthorized();
        $this->putJson('/api/v1/admin/homepage/main-categories', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/admin/homepage/main-categories/1')->assertUnauthorized();
    }

    public function test_main_categories_are_returned_in_slot_order_with_all_selectable_categories(): void
    {
        $firstCategory = Category::query()->create(['is_active' => true]);
        $secondCategory = Category::query()->create(['is_active' => false]);
        $firstCategory->translations()->create(['locale_code' => 'fr', 'name' => 'First category', 'slug' => 'first-category']);
        $secondCategory->translations()->create(['locale_code' => 'fr', 'name' => 'Second category', 'slug' => 'second-category']);
        MainCategory::query()->create(['category_id' => $secondCategory->id, 'position' => 6]);
        MainCategory::query()->create(['category_id' => $firstCategory->id, 'position' => 1]);

        $this->actingAs($this->ownerStaff(), 'admin')
            ->getJson('/api/v1/admin/homepage/main-categories')
            ->assertOk()
            ->assertJsonPath('data.slots.0.position', 1)
            ->assertJsonPath('data.slots.0.category.id', $firstCategory->id)
            ->assertJsonPath('data.slots.1.position', 6)
            ->assertJsonPath('data.slots.1.category.id', $secondCategory->id)
            ->assertJsonPath('data.categories.0.translations.0.name', 'First category')
            ->assertJsonCount(2, 'data.categories');
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
