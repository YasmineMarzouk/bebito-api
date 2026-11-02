<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\CategoryOutput;
use Modules\Catalog\Exceptions\CategoryNotFoundException;

final class GetCategoryUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    public function execute(int $categoryId): CategoryOutput
    {
        $category = $this->categories->find($categoryId);

        if ($category === null) {
            throw new CategoryNotFoundException;
        }

        return CategoryOutput::fromModel($category);
    }
}
