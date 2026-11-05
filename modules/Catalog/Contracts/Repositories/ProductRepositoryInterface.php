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

    /** @return Collection<int, Product> */
    public function homepage(): Collection;

    /** @return Collection<int, Product> */
    public function shop(?int $collectionId = null, bool $discounted = false): Collection;

    public function findShop(string $identifier): ?Product;

    public function find(int $id): ?Product;

    /** @param array<int, int> $productIds
     * @return Collection<int, Product>
     */
    public function findByIds(array $productIds): Collection;

    /** @param array<int, int> $productIds
     * @return Collection<int, Product>
     */
    public function replaceHomepageSelection(array $productIds): Collection;

    public function findAsset(int $productId, int $assetId): ?Asset;

    public function setPrimaryAsset(int $productId, int $assetId): bool;

    public function setFirstAssetAsPrimary(int $productId): void;

    public function create(CreateProductInput $input, array $translations): Product;

    public function update(Product $product, UpdateProductInput $input, array $translations): Product;

    public function delete(Product $product): void;
}
