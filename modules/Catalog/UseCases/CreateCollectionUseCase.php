<?php

namespace Modules\Catalog\UseCases;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\CreateCollectionInput;
use Modules\Catalog\DTOs\Outputs\CollectionOutput;
use Modules\Catalog\Events\CollectionPublished;
use Modules\Catalog\Exceptions\CollectionSlugAlreadyExistsException;
use Modules\Catalog\Models\Collection;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final readonly class CreateCollectionUseCase
{
    public function __construct(
        private CollectionRepositoryInterface $collections,
        private FileStorageServiceInterface $files,
        private TransactionManagerInterface $transactions,
    ) {}

    public function execute(CreateCollectionInput $input, ?UploadedFile $banner = null): CollectionOutput
    {
        foreach ($input->translations as $translation) {
            if ($translation->slug !== null && $this->collections->slugExists($translation->localeCode, $translation->slug)) {
                throw new CollectionSlugAlreadyExistsException;
            }
        }

        $collection = $this->transactions->run(function () use ($input, $banner): Collection {
            $collection = $this->collections->create($input);

            if ($banner !== null) {
                $this->files->store($banner, new StoreAssetInput('collection', $collection->id, 'collections', 'public', 0, true));
            }

            return $this->collections->find($collection->id) ?? $collection;
        });

        if ($collection->is_active) {
            Event::dispatch(new CollectionPublished($collection->id));
        }

        return CollectionOutput::fromModel($collection);
    }
}
