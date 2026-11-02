<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\CollectionOutput;
use Modules\Catalog\Exceptions\CollectionNotFoundException;

final class GetCollectionUseCase
{
    public function __construct(private readonly CollectionRepositoryInterface $collections) {}

    public function execute(int $collectionId): CollectionOutput
    {
        $collection = $this->collections->find($collectionId);

        if ($collection === null) {
            throw new CollectionNotFoundException;
        }

        return CollectionOutput::fromModel($collection);
    }
}
