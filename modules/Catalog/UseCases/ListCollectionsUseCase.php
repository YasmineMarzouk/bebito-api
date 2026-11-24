<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\CollectionOutput;
use Modules\Core\DTOs\Outputs\PaginatedOutput;

final class ListCollectionsUseCase
{
    public function __construct(private readonly CollectionRepositoryInterface $collections) {}

    public function execute(int $perPage, int $page): PaginatedOutput
    {
        $collections = $this->collections->paginate($perPage, $page);

        return new PaginatedOutput($collections->getCollection()->map(CollectionOutput::fromModel(...))->all(), $collections->currentPage(), $collections->lastPage(), $collections->perPage(), $collections->total());
    }
}
