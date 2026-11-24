<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Catalog\DTOs\Inputs\CreateCategoryInput;
use Modules\Catalog\DTOs\Inputs\UpdateCategoryInput;
use Modules\Catalog\Models\Category;

interface CategoryRepositoryInterface
{
    /** @return Collection<int, Category> */
    public function all(): Collection;

    /** @return LengthAwarePaginator<int, Category> */
    public function paginate(int $perPage, int $page): LengthAwarePaginator;

    /** @return Collection<int, Category> */
    public function homepage(): Collection;

    /** @return Collection<int, Category> */
    public function shop(): Collection;

    public function find(int $id): ?Category;

    /** @param array<int, int> $categoryIds
     * @return Collection<int, Category>
     */
    public function findByIds(array $categoryIds): Collection;

    public function create(CreateCategoryInput $input): Category;

    public function update(Category $category, UpdateCategoryInput $input): Category;

    public function delete(Category $category): void;

    public function slugExists(string $localeCode, string $slug, ?int $excludingCategoryId = null): bool;

    public function frenchNameExists(string $name): bool;
}
