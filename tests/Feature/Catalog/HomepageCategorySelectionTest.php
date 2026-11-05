<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Category;
use Tests\TestCase;

class HomepageCategorySelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_administrator_can_select_up_to_six_homepage_categories(): void
    {
        $categories = collect(range(1, 7))->map(fn (int $number): Category => $this->category($number));
        $selectedCategoryIds = $categories->take(6)->pluck('id')->all();

        $this->actingAs(User::factory()->create())
            ->putJson('/api/v1/categories/homepage-selection', ['category_ids' => $selectedCategoryIds])
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('data.0.id', $selectedCategoryIds[0]);

        $this->assertDatabaseHas('categories', [
            'id' => $selectedCategoryIds[0],
            'is_homepage_featured' => true,
            'homepage_position' => 1,
        ]);

        $this->getJson('/api/v1/homepage/categories')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('data.0.id', $selectedCategoryIds[0]);
    }

    public function test_homepage_category_selection_rejects_more_than_six_categories(): void
    {
        $categoryIds = collect(range(1, 7))->map(fn (int $number): int => $this->category($number)->id)->all();

        $this->actingAs(User::factory()->create())
            ->putJson('/api/v1/categories/homepage-selection', ['category_ids' => $categoryIds])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_ids']);
    }

    public function test_homepage_category_selection_rejects_inactive_categories(): void
    {
        $inactiveCategory = $this->category(1, false);

        $this->actingAs(User::factory()->create())
            ->putJson('/api/v1/categories/homepage-selection', ['category_ids' => [$inactiveCategory->id]])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Only active categories can be displayed on the homepage.');
    }

    private function category(int $number, bool $isActive = true): Category
    {
        $category = Category::query()->create(['is_active' => $isActive]);
        $category->translations()->create([
            'locale_code' => 'fr',
            'name' => "Category {$number}",
            'slug' => "category-{$number}",
        ]);

        return $category;
    }
}
