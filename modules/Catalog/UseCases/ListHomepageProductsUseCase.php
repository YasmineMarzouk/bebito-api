<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\ProductOutput;

final class ListHomepageProductsUseCase
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    /** @return array<int, ProductOutput> */
    public function execute(): array
    {
        return $this->products->homepage()->map(ProductOutput::fromModel(...))->all();
    }
}
