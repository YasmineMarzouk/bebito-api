<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\UpdateCategoryInput;
use Modules\Catalog\DTOs\Outputs\CategoryOutput;
use Modules\Catalog\Exceptions\CategoryNotFoundException;
use Modules\Catalog\Exceptions\CategorySlugAlreadyExistsException;

final class UpdateCategoryUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    public function execute(int $categoryId, UpdateCategoryInput $input): CategoryOutput
    {
        $category = $this->categories->find($categoryId);

        if ($category === null) {
            throw new CategoryNotFoundException;
        }

        foreach ($input->translations as $translation) {
            if ($translation->slug !== null && $this->categories->slugExists($translation->localeCode, $translation->slug, $category->id)) {
                throw new CategorySlugAlreadyExistsException;
            }
        }

        return CategoryOutput::fromModel($this->categories->update($category, $input));
    }
}
