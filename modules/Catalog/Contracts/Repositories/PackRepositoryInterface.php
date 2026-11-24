<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Catalog\Models\Pack;
use Modules\Core\Models\Asset;
use Modules\Catalog\DTOs\Inputs\PackComponentInput;
use Modules\Catalog\DTOs\Inputs\PackVariantInput;
use Modules\Catalog\DTOs\Inputs\ProductTranslationInput;

interface PackRepositoryInterface
{
    /** @return Collection<int, Pack> */
    public function all(): Collection;

    /** @return LengthAwarePaginator<int, Pack> */
    public function paginate(int $perPage, int $page): LengthAwarePaginator;

    /** @return Collection<int, Pack> */
    public function shop(): Collection;

    /** @param array<int, int> $packIds
     * @return Collection<int, Pack>
     */
    public function findShopByIds(array $packIds): Collection;

    public function find(int $id): ?Pack;

    public function findForUpdate(int $id): ?Pack;

    public function findShop(string $identifier): ?Pack;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Pack;

    /** @param array<string, mixed> $attributes */
    public function update(Pack $pack, array $attributes): Pack;

    /** @param array<int, ProductTranslationInput> $translations */
    public function syncTranslations(Pack $pack, array $translations): void;

    /** @param array<int, int> $categoryIds @param array<int, int> $collectionIds @param array<int, string> $tags */
    public function syncOrganization(Pack $pack, array $categoryIds, array $collectionIds, array $tags): void;

    /** @param array<int, PackComponentInput> $components @param array<int, PackVariantInput> $variants */
    public function replaceComposition(Pack $pack, array $components, array $variants): void;

    public function markFirstPublished(Pack $pack): Pack;

    public function refreshCompareAtPricesForProduct(int $productId): void;

    public function findAsset(int $packId, int $assetId): ?Asset;

    public function setPrimaryAsset(int $packId, int $assetId): bool;

    public function delete(Pack $pack): void;
}
