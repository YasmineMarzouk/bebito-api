<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Modules\Catalog\DTOs\Inputs\CreateCollectionInput;
use Modules\Catalog\DTOs\Inputs\UpdateCollectionInput;
use Modules\Catalog\Models\Collection;

interface CollectionRepositoryInterface
{
    /** @return EloquentCollection<int, Collection> */
    public function all(): EloquentCollection;

    /** @return EloquentCollection<int, Collection> */
    public function shop(): EloquentCollection;

    public function find(int $id): ?Collection;

    public function create(CreateCollectionInput $input): Collection;

    public function update(Collection $collection, UpdateCollectionInput $input): Collection;

    public function delete(Collection $collection): void;

    public function slugExists(string $localeCode, string $slug, ?int $excludingCollectionId = null): bool;
}
