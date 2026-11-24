<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Catalog\DTOs\Inputs\CreateProductInput;
use Modules\Catalog\DTOs\Inputs\UpdateProductInput;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Models\Product;
use Modules\Core\Models\Asset;

interface ProductRepositoryInterface
{
    /** @return Collection<int, Product> */
    public function all(): Collection;

    /** @return LengthAwarePaginator<int, Product> */
    public function paginate(int $perPage, int $page, ?string $search = null): LengthAwarePaginator;

    /** @return Collection<int, Product> */
    public function homepage(): Collection;

    /** @return Collection<int, Product> */
    public function shop(?int $collectionId = null, bool $discounted = false): Collection;

    /** @param array<int, int> $productIds
     * @return Collection<int, Product>
     */
    public function findShopByIds(array $productIds): Collection;

    public function findShop(string $identifier): ?Product;

    public function find(int $id): ?Product;

    /** @param array<int, int> $productIds
     * @return Collection<int, Product>
     */
    public function findByIds(array $productIds): Collection;

    /** @param array<int, int> $productIds
     * @return Collection<int, Product>
     */
    public function findByIdsWithVariants(array $productIds): Collection;

    public function findAsset(int $productId, int $assetId): ?Asset;

    public function setPrimaryAsset(int $productId, int $assetId): bool;

    public function create(CreateProductInput $input, ?int $userId = null): Product;

    public function update(Product $product, UpdateProductInput $input, array $translations, ?int $userId = null): Product;

    public function updateStatus(Product $product, ProductStatusEnum $status): Product;

    public function delete(Product $product): void;
}
