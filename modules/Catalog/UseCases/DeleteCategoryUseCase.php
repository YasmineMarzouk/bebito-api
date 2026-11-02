<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\Exceptions\CategoryNotFoundException;

final class DeleteCategoryUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    public function execute(int $categoryId): void
    {
        $category = $this->categories->find($categoryId);

        if ($category === null) {
            throw new CategoryNotFoundException;
        }

        $this->categories->delete($category);
    }
}
