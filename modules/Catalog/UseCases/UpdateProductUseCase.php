<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\UpdateProductInput;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Catalog\Exceptions\ProductImageNotFoundException;
use Modules\Catalog\Exceptions\ProductNotFoundException;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final readonly class UpdateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private FileStorageServiceInterface $files,
    ) {}

    public function execute(int $id, UpdateProductInput $input, ?int $userId = null): ProductOutput
    {
        $existing = $this->products->find($id);

        if ($existing === null) {
            throw new ProductNotFoundException;
        }

        if ($input->primaryImageId !== null && $this->products->findAsset($id, $input->primaryImageId) === null) {
            throw new ProductImageNotFoundException;
        }

        $product = $this->products->update($existing, $input, $input->translations, $userId);
        $nextSortOrder = $product->assets->count();

        foreach ($input->images as $index => $image) {
            $this->files->store($image, new StoreAssetInput('product', $product->id, 'products', 'public', $nextSortOrder + $index));
        }

        if ($input->primaryImageId !== null && ! $this->products->setPrimaryAsset($product->id, $input->primaryImageId)) {
            throw new ProductImageNotFoundException;
        }

        return ProductOutput::fromModel($this->products->find($product->id) ?? $product);
    }
}
