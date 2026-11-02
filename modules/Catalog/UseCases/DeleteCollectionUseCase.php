<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\Exceptions\CollectionNotFoundException;

final class DeleteCollectionUseCase
{
    public function __construct(private readonly CollectionRepositoryInterface $collections) {}

    public function execute(int $collectionId): void
    {
        $collection = $this->collections->find($collectionId);

        if ($collection === null) {
            throw new CollectionNotFoundException;
        }

        $this->collections->delete($collection);
    }
}
