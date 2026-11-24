<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;

interface ShopCatalogRepositoryInterface
{
    /** @return LengthAwarePaginator<int, object> */
    public function paginate(?int $categoryId, bool $discounted, int $perPage, int $page): LengthAwarePaginator;
}
