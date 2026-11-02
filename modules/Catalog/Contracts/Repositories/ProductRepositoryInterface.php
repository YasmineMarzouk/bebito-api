<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\DTOs\Inputs\CreateProductInput;
use Modules\Catalog\DTOs\Inputs\UpdateProductInput;
use Modules\Catalog\Models\Product;
use Modules\Core\Models\Asset;

interface ProductRepositoryInterface
{
    /** @return Collection<int, Product> */
    public function all(): Collection;

    public function find(int $id): ?Product;

    public function findAsset(int $productId, int $assetId): ?Asset;

    public function setPrimaryAsset(int $productId, int $assetId): bool;

    public function setFirstAssetAsPrimary(int $productId): void;

    public function create(CreateProductInput $input, array $translations): Product;

    public function update(Product $product, UpdateProductInput $input, array $translations): Product;

    public function delete(Product $product): void;
}
