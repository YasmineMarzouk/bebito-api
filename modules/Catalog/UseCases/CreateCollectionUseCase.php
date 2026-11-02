<?php

namespace Modules\Catalog\UseCases;

use Illuminate\Http\UploadedFile;
use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\Contracts\Services\CatalogTranslationServiceInterface;
use Modules\Catalog\DTOs\Inputs\CreateCollectionInput;
use Modules\Catalog\DTOs\Outputs\CollectionOutput;
use Modules\Catalog\Exceptions\CollectionSlugAlreadyExistsException;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final readonly class CreateCollectionUseCase
{
    public function __construct(
        private CollectionRepositoryInterface $collections,
        private CatalogTranslationServiceInterface $translations,
        private FileStorageServiceInterface $files,
        private TransactionManagerInterface $transactions,
    ) {}

    public function execute(CreateCollectionInput $input, ?UploadedFile $banner = null): CollectionOutput
    {
        $input = new CreateCollectionInput($input->isActive, $input->isFeatured, $input->sortOrder, $this->translations->completeFrenchTranslation($input->translations[0], $input->translations[1] ?? null));

        foreach ($input->translations as $translation) {
            if ($translation->slug !== null && $this->collections->slugExists($translation->localeCode, $translation->slug)) {
                throw new CollectionSlugAlreadyExistsException;
            }
        }

        return $this->transactions->run(function () use ($input, $banner): CollectionOutput {
            $collection = $this->collections->create($input);

            if ($banner !== null) {
                $this->files->store($banner, new StoreAssetInput('collection', $collection->id, 'collections', 'public', 0, true));
            }

            return CollectionOutput::fromModel($this->collections->find($collection->id) ?? $collection);
        });
    }
}
