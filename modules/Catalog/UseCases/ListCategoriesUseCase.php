<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\CategoryOutput;
use Modules\Core\DTOs\Outputs\PaginatedOutput;

final class ListCategoriesUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    public function execute(int $perPage, int $page): PaginatedOutput
    {
        $categories = $this->categories->paginate($perPage, $page);

        return new PaginatedOutput($categories->getCollection()->map(CategoryOutput::fromModel(...))->all(), $categories->currentPage(), $categories->lastPage(), $categories->perPage(), $categories->total());
    }
}
