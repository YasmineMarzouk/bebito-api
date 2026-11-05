<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Catalog\Exceptions\ProductNotFoundException;

final class GetShopProductUseCase
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function execute(string $identifier): ProductOutput
    {
        $product = $this->products->findShop($identifier);

        if ($product === null) {
            throw new ProductNotFoundException;
        }

        return ProductOutput::fromModel($product);
    }
}
