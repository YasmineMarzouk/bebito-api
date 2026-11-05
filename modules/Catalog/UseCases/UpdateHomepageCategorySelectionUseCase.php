<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\UpdateHomepageCategorySelectionInput;
use Modules\Catalog\DTOs\Outputs\CategoryOutput;
use Modules\Catalog\Exceptions\HomepageCategoriesMustBeActiveException;

final class UpdateHomepageCategorySelectionUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    /** @return array<int, CategoryOutput> */
    public function execute(UpdateHomepageCategorySelectionInput $input): array
    {
        $categories = $this->categories->findByIds($input->categoryIds);

        if ($categories->count() !== count($input->categoryIds) || $categories->contains(fn ($category): bool => ! $category->is_active)) {
            throw new HomepageCategoriesMustBeActiveException;
        }

        return $this->categories->replaceHomepageSelection($input->categoryIds)->map(CategoryOutput::fromModel(...))->all();
    }
}
