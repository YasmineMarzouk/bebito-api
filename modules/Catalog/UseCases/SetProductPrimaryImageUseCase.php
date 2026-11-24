<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Catalog\Exceptions\ProductImageNotFoundException;
use Modules\Catalog\Exceptions\ProductNotFoundException;

final readonly class SetProductPrimaryImageUseCase
{
    public function __construct(private ProductRepositoryInterface $products) {}

    public function execute(int $productId, int $assetId): ProductOutput
    {
        $product = $this->products->find($productId);
        if ($product === null) {
            throw new ProductNotFoundException;
        }

        if ($this->products->findAsset($productId, $assetId) === null
            || ! $this->products->setPrimaryAsset($productId, $assetId)) {
            throw new ProductImageNotFoundException;
        }

        return ProductOutput::fromModel($this->products->find($productId) ?? $product);
    }
}
