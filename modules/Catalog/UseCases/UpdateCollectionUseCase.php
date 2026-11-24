<?php

namespace Modules\Catalog\UseCases;

use Illuminate\Support\Facades\Event;
use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\UpdateCollectionInput;
use Modules\Catalog\DTOs\Outputs\CollectionOutput;
use Modules\Catalog\Events\CollectionPublished;
use Modules\Catalog\Exceptions\CollectionNotFoundException;
use Modules\Catalog\Exceptions\CollectionSlugAlreadyExistsException;

final class UpdateCollectionUseCase
{
    public function __construct(private readonly CollectionRepositoryInterface $collections) {}

    public function execute(int $collectionId, UpdateCollectionInput $input): CollectionOutput
    {
        $collection = $this->collections->find($collectionId);

        if ($collection === null) {
            throw new CollectionNotFoundException;
        }

        foreach ($input->translations as $translation) {
            if ($translation->slug !== null && $this->collections->slugExists($translation->localeCode, $translation->slug, $collection->id)) {
                throw new CollectionSlugAlreadyExistsException;
            }
        }

        $wasInactive = ! $collection->is_active;
        $collection = $this->collections->update($collection, $input);

        if ($wasInactive && $collection->is_active) {
            Event::dispatch(new CollectionPublished($collection->id));
        }

        return CollectionOutput::fromModel($collection);
    }
}
