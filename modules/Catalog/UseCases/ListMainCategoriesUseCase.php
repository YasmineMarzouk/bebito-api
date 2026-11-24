<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\CategoryOutput;
use Modules\Catalog\DTOs\Outputs\MainCategoryOutput;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\MainCategory;

final class ListMainCategoriesUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    /** @return array{slots: array<int, MainCategoryOutput>, categories: array<int, CategoryOutput>} */
    public function execute(): array
    {
        return [
            'slots' => MainCategory::query()
                ->with(['category' => Category::categoryDetails()])
                ->orderBy('position')
                ->get()
                ->map(MainCategoryOutput::fromModel(...))
                ->all(),
            'categories' => $this->categories->all()
                ->map(CategoryOutput::fromModel(...))
                ->all(),
        ];
    }
}
