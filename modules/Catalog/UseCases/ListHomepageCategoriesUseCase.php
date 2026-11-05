<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\CategoryOutput;

final class ListHomepageCategoriesUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    /** @return array<int, CategoryOutput> */
    public function execute(): array
    {
        return $this->categories->homepage()->map(CategoryOutput::fromModel(...))->all();
    }
}
