<?php

namespace Modules\Catalog\DTOs\Outputs;

final readonly class ShopCatalogOutput
{
    /**
     * @param  array<int, CategoryOutput>  $categories
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        public array $categories,
        public array $items,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}
}
