<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\CollectionOutput;

final class ListShopCollectionsUseCase
{
    public function __construct(private readonly CollectionRepositoryInterface $collections) {}

    /** @return array<int, CollectionOutput> */
    public function execute(): array
    {
        return $this->collections->shop()->map(CollectionOutput::fromModel(...))->all();
    }
}
