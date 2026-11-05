<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\CategoryOutput;

final class ListShopCategoriesUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    /** @return array<int, CategoryOutput> */
    public function execute(): array
    {
        return $this->categories->shop()->map(CategoryOutput::fromModel(...))->all();
    }
}
