<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\Contracts\Services\CatalogTranslationServiceInterface;
use Modules\Catalog\DTOs\Inputs\UpdateCollectionInput;
use Modules\Catalog\DTOs\Outputs\CollectionOutput;
use Modules\Catalog\Exceptions\CollectionNotFoundException;
use Modules\Catalog\Exceptions\CollectionSlugAlreadyExistsException;

final class UpdateCollectionUseCase
{
    public function __construct(private readonly CollectionRepositoryInterface $collections, private readonly CatalogTranslationServiceInterface $translations) {}

    public function execute(int $collectionId, UpdateCollectionInput $input): CollectionOutput
    {
        $collection = $this->collections->find($collectionId);

        if ($collection === null) {
            throw new CollectionNotFoundException;
        }

        $input = new UpdateCollectionInput($input->isActive, $input->isFeatured, $input->sortOrder, $this->translations->completeFrenchTranslation($input->translations[0], $input->translations[1] ?? null));

        foreach ($input->translations as $translation) {
            if ($translation->slug !== null && $this->collections->slugExists($translation->localeCode, $translation->slug, $collection->id)) {
                throw new CollectionSlugAlreadyExistsException;
            }
        }

        return CollectionOutput::fromModel($this->collections->update($collection, $input));
    }
}
