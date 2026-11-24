<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Catalog\Contracts\Repositories\ShopCatalogRepositoryInterface;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Models\Pack;
use Modules\Catalog\Models\Product;

final class ShopCatalogRepository implements ShopCatalogRepositoryInterface
{
    public function paginate(?int $categoryId, bool $discounted, int $perPage, int $page): LengthAwarePaginator
    {
        $products = Product::query()
            ->where('status', ProductStatusEnum::Published->value)
            ->when($categoryId !== null, fn ($query) => $query->whereHas('categories', fn ($categories) => $categories->whereKey($categoryId)->where('is_active', true)))
            ->when($discounted, fn ($query) => $query->whereNotNull('discount_percentage')->where('discount_percentage', '>', 0))
            ->select(['id', 'created_at'])
            ->selectRaw("'product' as catalog_item_type")
            ->toBase();

        $packs = Pack::query()
            ->where('status', ProductStatusEnum::Published->value)
            ->when($categoryId !== null, fn ($query) => $query->whereHas('categories', fn ($categories) => $categories->whereKey($categoryId)->where('is_active', true)))
            ->when($discounted, fn ($query) => $query->whereColumn('compare_at_price_amount', '>', 'price_amount'))
            ->select(['id', 'created_at'])
            ->selectRaw("'pack' as catalog_item_type")
            ->toBase();

        return $products
            ->unionAll($packs)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
