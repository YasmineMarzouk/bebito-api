<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\ProductOutput;

final readonly class ListProductsUseCase
{
    public function __construct(private ProductRepositoryInterface $products) {}

    /** @return array<int, ProductOutput> */
    public function execute(): array
    {
        return $this->products->all()->map(ProductOutput::fromModel(...))->all();
    }
}
