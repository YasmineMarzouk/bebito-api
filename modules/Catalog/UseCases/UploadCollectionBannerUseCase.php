<?php

namespace Modules\Catalog\UseCases;

use Illuminate\Http\UploadedFile;
use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\Exceptions\CollectionNotFoundException;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final class UploadCollectionBannerUseCase
{
    public function __construct(private readonly CollectionRepositoryInterface $collections, private readonly FileStorageServiceInterface $files) {}

    public function execute(int $collectionId, UploadedFile $file): string
    {
        if ($this->collections->find($collectionId) === null) {
            throw new CollectionNotFoundException;
        }
        $asset = $this->files->store($file, new StoreAssetInput('collection', $collectionId, 'collections', 'public', 0, true));

        return $this->files->url($asset);
    }
}
