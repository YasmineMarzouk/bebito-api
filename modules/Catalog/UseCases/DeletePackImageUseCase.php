<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Exceptions\PackNotFoundException;
use Modules\Catalog\Exceptions\ProductImageNotFoundException;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;

final readonly class DeletePackImageUseCase
{
    public function __construct(
        private PackRepositoryInterface $packs,
        private FileStorageServiceInterface $files,
    ) {}

    public function execute(int $packId, int $assetId): void
    {
        if ($this->packs->find($packId) === null) {
            throw new PackNotFoundException;
        }

        $asset = $this->packs->findAsset($packId, $assetId);
        if ($asset === null) {
            throw new ProductImageNotFoundException;
        }

        $this->files->delete($asset);
    }
}
