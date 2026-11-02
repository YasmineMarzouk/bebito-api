<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\CollectionOutput;

final class ListCollectionsUseCase
{
    public function __construct(private readonly CollectionRepositoryInterface $collections) {}

    /** @return array<int, CollectionOutput> */
    public function execute(): array
    {
        return $this->collections->all()->map(CollectionOutput::fromModel(...))->all();
    }
}
