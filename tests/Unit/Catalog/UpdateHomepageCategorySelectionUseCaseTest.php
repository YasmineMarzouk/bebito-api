<?php

namespace Tests\Unit\Catalog;

use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\UpdateHomepageCategorySelectionInput;
use Modules\Catalog\Exceptions\HomepageCategoriesMustBeActiveException;
use Modules\Catalog\Models\Category;
use Modules\Catalog\UseCases\UpdateHomepageCategorySelectionUseCase;
use Tests\TestCase;

class UpdateHomepageCategorySelectionUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_replaces_the_homepage_category_selection(): void
    {
        $category = $this->category(12, true);
        $categories = Mockery::mock(CategoryRepositoryInterface::class);
        $categories->shouldReceive('findByIds')->once()->with([12])->andReturn(new Collection([$category]));
        $categories->shouldReceive('replaceHomepageSelection')->once()->with([12])->andReturn(new Collection([$category]));

        $result = (new UpdateHomepageCategorySelectionUseCase($categories))->execute(
            new UpdateHomepageCategorySelectionInput([12]),
        );

        self::assertSame(12, $result[0]->id);
    }

    public function test_it_rejects_inactive_categories(): void
    {
        $category = $this->category(12, false);
        $categories = Mockery::mock(CategoryRepositoryInterface::class);
        $categories->shouldReceive('findByIds')->once()->with([12])->andReturn(new Collection([$category]));
        $categories->shouldNotReceive('replaceHomepageSelection');

        $this->expectException(HomepageCategoriesMustBeActiveException::class);

        (new UpdateHomepageCategorySelectionUseCase($categories))->execute(
            new UpdateHomepageCategorySelectionInput([12]),
        );
    }

    private function category(int $id, bool $isActive): Category
    {
        $category = new Category;
        $category->setRawAttributes([
            'id' => $id,
            'is_active' => $isActive,
            'is_homepage_featured' => true,
            'homepage_position' => 1,
        ]);
        $category->setRelation('translations', new Collection);
        $category->setRelation('primaryAsset', null);

        return $category;
    }
}
