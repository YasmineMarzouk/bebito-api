<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Core\DTOs\Outputs\PaginatedOutput;

final readonly class ListProductsUseCase
{
    public function __construct(private ProductRepositoryInterface $products) {}

    public function execute(int $perPage, int $page): PaginatedOutput
    {
        $products = $this->products->paginate($perPage, $page);

        return new PaginatedOutput($products->getCollection()->map(ProductOutput::fromModel(...))->all(), $products->currentPage(), $products->lastPage(), $products->perPage(), $products->total());
    }
}
