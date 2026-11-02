<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\Exceptions\ProductImageNotFoundException;
use Modules\Catalog\Exceptions\ProductNotFoundException;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;

final readonly class DeleteProductImageUseCase
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private FileStorageServiceInterface $files,
    ) {}

    public function execute(int $productId, int $assetId): void
    {
        if ($this->products->find($productId) === null) {
            throw new ProductNotFoundException;
        }

        $asset = $this->products->findAsset($productId, $assetId);

        if ($asset === null) {
            throw new ProductImageNotFoundException;
        }

        $wasPrimary = $asset->is_primary;
        $this->files->delete($asset);

        if ($wasPrimary) {
            $this->products->setFirstAssetAsPrimary($productId);
        }
    }
}
